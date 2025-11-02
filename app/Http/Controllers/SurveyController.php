<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyToken;
use App\Models\Vote;
use App\Services\VoteFraudDetector;
use App\Services\VoteRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function show(Request $request, $publicSlug)
    {
        // Buscar la encuesta por public_slug (ofuscado)
        $survey = Survey::where('public_slug', $publicSlug)
            ->with('questions.options')
            ->firstOrFail();

        // Incrementar contador de visitas usando sesión para evitar múltiples conteos
        $sessionKey = 'survey_viewed_' . $survey->id;
        if (!session()->has($sessionKey)) {
            $survey->incrementViews();
            session()->put($sessionKey, true);
        }

        // Si la encuesta está terminada, redirigir a resultados finales
        if ($survey->is_finished) {
            return redirect()->route('surveys.finished', $survey->public_slug);
        }

        // Si la encuesta no está activa, mostrar mensaje
        if (!$survey->is_active) {
            return view('surveys.inactive', compact('survey'));
        }

        // OBLIGAR EL USO DE TOKENS: Si no hay token en la URL, redirigir a /t/{publicSlug}
        $tokenString = $request->query('token');
        if (!$tokenString) {
            return redirect()->route('token.redirect', $publicSlug);
        }

        // Verificar que el token existe y obtener su estado
        $tokenRecord = SurveyToken::where('token', $tokenString)
            ->where('survey_id', $survey->id)
            ->first();

        // Si el token no existe, redirigir a /t/{publicSlug} para generar uno nuevo
        if (!$tokenRecord) {
            return redirect()->route('token.redirect', $publicSlug);
        }

        // Si el token ya fue usado, incrementar intentos y mostrar como si ya votó
        // NO redirigir, mantener la misma URL
        if ($tokenRecord->status === 'used') {
            // Incrementar contador de intentos para tracking
            $tokenRecord->incrementAttempt();

            // Mostrar la vista como si ya hubiera votado (sin redirigir)
            $hasVoted = true;
            $token = $tokenString;
            return view('surveys.show', compact('survey', 'hasVoted', 'token'))
                ->with('info', 'Este enlace ya fue utilizado anteriormente.');
        }

        // Verificar si ya votó (solo por fingerprint para permitir múltiples usuarios en la misma red)
        $fingerprint = $request->cookie('survey_fingerprint');

        $hasVoted = false;
        if ($fingerprint) {
            $hasVoted = Vote::where('survey_id', $survey->id)
                ->where('fingerprint', $fingerprint)
                ->exists();
        }

        // Pasar el token a la vista
        $token = $tokenString;

        return view('surveys.show', compact('survey', 'hasVoted', 'token'));
    }

    public function vote(Request $request, $publicSlug)
    {
        $survey = Survey::where('public_slug', $publicSlug)->firstOrFail();

        // Verificar que la encuesta esté activa
        if (!$survey->is_active) {
            return redirect()->route('surveys.show', $publicSlug)
                ->with('error', 'Esta encuesta no está disponible para votar en este momento.');
        }

        $validated = $request->validate([
            'answers' => 'required|array|min:1|max:50',
            'answers.*' => 'required|exists:question_options,id',
            'fingerprint' => 'required|string|max:100',
            'token' => 'nullable|string|max:100',
            'device_data' => 'nullable|array',
            'device_data.user_agent' => 'nullable|string|max:500',
            'device_data.platform' => 'nullable|string|max:100',
            'device_data.screen_resolution' => 'nullable|string|max:50',
            'device_data.hardware_concurrency' => 'nullable|integer',
        ]);

        // Validar que las respuestas correspondan a preguntas de esta encuesta
        foreach ($validated['answers'] as $questionId => $optionId) {
            $validOption = \App\Models\QuestionOption::where('id', $optionId)
                ->whereHas('question', function($q) use ($survey, $questionId) {
                    $q->where('survey_id', $survey->id)
                      ->where('id', $questionId);
                })
                ->exists();

            if (!$validOption) {
                abort(422, 'Respuesta inválida detectada.');
            }
        }

        $ipAddress = $request->ip();
        $fingerprint = $request->input('fingerprint') ?? Str::random(40);
        $deviceData = $request->input('device_data', []);
        $tokenString = $request->input('token');

        // ===================================================================
        // VALIDACIÓN DE TOKEN OBLIGATORIA
        // ===================================================================
        if (!$tokenString) {
            // SIEMPRE mostrar éxito para no revelar el problema
            return redirect()->route('surveys.thanks', $survey->public_slug)
                ->with('success', '¡Gracias por tu participación!');
        }

        $tokenRecord = SurveyToken::where('token', $tokenString)
            ->where('survey_id', $survey->id)
            ->first();

        // ===================================================================
        // VALIDACIÓN PRINCIPAL: 1 TOKEN = 1 VOTO (sin importar el dispositivo)
        // ===================================================================
        // Si el token no existe o ya fue usado, mostrar éxito pero NO contar el voto
        if (!$tokenRecord || !$tokenRecord->isValid()) {
            if ($tokenRecord) {
                // Incrementar intentos para rastreo de actividad sospechosa
                $tokenRecord->incrementAttempt();
            }

            // SIEMPRE mostrar éxito para no revelar que el token es inválido
            return redirect()->route('surveys.thanks', $survey->public_slug)
                ->with('success', '¡Gracias por tu participación!');
        }

        // TOKEN VÁLIDO - Incrementar intentos del token
        $tokenRecord->incrementAttempt();

        // ===================================================================
        // RATE LIMITING: Verificar límites ANTES de procesar el voto
        // ===================================================================
        $rateLimiter = new VoteRateLimiter();

        // Verificar cada respuesta contra el rate limiter
        foreach ($validated['answers'] as $questionId => $optionId) {
            $rateLimitCheck = $rateLimiter->allowVote(
                $survey->id,
                $optionId,
                $ipAddress,
                $fingerprint
            );

            if (!$rateLimitCheck['allowed']) {
                // Rate limit excedido - MOSTRAR ÉXITO pero NO contar el voto
                // Esto previene que atacantes sepan que fueron bloqueados
                return redirect()->route('surveys.thanks', $survey->public_slug)
                    ->with('success', '¡Gracias por tu participación!')
                    ->with('rate_limited', true); // Flag interno (no se muestra al usuario)
            }
        }

        try {
            DB::beginTransaction();

            // Instanciar el detector de fraude
            $fraudDetector = new VoteFraudDetector();

            foreach ($validated['answers'] as $questionId => $optionId) {
                // Preparar datos del voto para análisis de fraude
                $voteData = [
                    'user_agent' => $deviceData['user_agent'] ?? null,
                    'platform' => $deviceData['platform'] ?? null,
                    'screen_resolution' => $deviceData['screen_resolution'] ?? null,
                    'browser_language' => $request->header('Accept-Language'),
                    'hardware_concurrency' => $deviceData['hardware_concurrency'] ?? null,
                ];

                // Analizar el voto para detectar patrones sospechosos
                $fraudAnalysis = $fraudDetector->analyzeVote($voteData, $questionId, $optionId);

                // Crear el voto con los datos de fraude incluidos
                Vote::create([
                    'survey_id' => $survey->id,
                    'survey_token_id' => $tokenRecord->id,
                    'question_id' => $questionId,
                    'question_option_id' => $optionId,
                    'ip_address' => $ipAddress,
                    'fingerprint' => $fingerprint,
                    'user_agent' => $deviceData['user_agent'] ?? null,
                    'platform' => $deviceData['platform'] ?? null,
                    'screen_resolution' => $deviceData['screen_resolution'] ?? null,
                    'hardware_concurrency' => $deviceData['hardware_concurrency'] ?? null,
                    // Campos de detección de fraude
                    'status' => $fraudAnalysis['status'],
                    'fraud_score' => $fraudAnalysis['fraud_score'],
                    'fraud_reasons' => !empty($fraudAnalysis['fraud_reasons'])
                        ? json_encode($fraudAnalysis['fraud_reasons'])
                        : null,
                ]);

                // Registrar el voto en el sistema de rate limiting
                $rateLimiter->recordVote(
                    $survey->id,
                    $optionId,
                    $ipAddress,
                    $fingerprint
                );
            }

            // Marcar el token como usado (ya validamos que es válido arriba)
            $tokenRecord->markAsUsed(
                $fingerprint,
                $request->userAgent() ?? ''
            );

            DB::commit();

            // SIEMPRE mostrar mensaje de éxito al usuario
            $response = redirect()->route('surveys.thanks', $survey->public_slug)
                ->with('success', '¡Gracias por tu participación!');

            // Establecer MÚLTIPLES cookies para máxima persistencia
            return $response
                ->cookie('survey_fingerprint', $fingerprint, 525600) // 1 año
                ->cookie('device_fingerprint', $fingerprint, 525600) // 1 año
                ->cookie('survey_' . $survey->id . '_voted', 'true', 525600) // Cookie específica de encuesta
                ->cookie('survey_' . $survey->id . '_fp', $fingerprint, 525600); // Fingerprint por encuesta

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar tu voto: ' . $e->getMessage());
        }
    }

    public function thanks($publicSlug)
    {
        $survey = Survey::where('public_slug', $publicSlug)
            ->with(['questions.options' => function($query) {
                $query->withCount(['votes' => function($q) {
                    $q->valid();
                }]);
            }])
            ->firstOrFail();

        // Si la encuesta está terminada, redirigir a la página de resultados finales
        if ($survey->is_finished) {
            return redirect()->route('surveys.finished', $survey->public_slug);
        }

        // Si la encuesta está despublicada/inactiva, mostrar página de inactiva
        if (!$survey->is_active) {
            return view('surveys.inactive', compact('survey'));
        }

        // Verificar si se deben mostrar resultados
        $showResults = $survey->show_results;
        $totalVotes = 0;
        $statistics = [];

        if ($showResults) {
            // Calcular estadísticas generales - Solo votos válidos
            $totalVotes = Vote::where('survey_id', $survey->id)
                ->valid()
                ->distinct('fingerprint')
                ->count('fingerprint');

            // Si no hay votos por fingerprint, contar por IP
            if ($totalVotes == 0) {
                $totalVotes = Vote::where('survey_id', $survey->id)
                    ->valid()
                    ->distinct('ip_address')
                    ->count('ip_address');
            }

            // Preparar datos para los gráficos
            foreach ($survey->questions as $question) {
                $questionStats = [
                    'question' => $question->question_text,
                    'type' => $question->question_type,
                    'options' => [],
                    'total_responses' => 0
                ];

                $totalQuestionVotes = $question->options->sum('votes_count');
                $questionStats['total_responses'] = $totalQuestionVotes;

                foreach ($question->options as $option) {
                    $percentage = $totalQuestionVotes > 0
                        ? round(($option->votes_count / $totalQuestionVotes) * 100, 1)
                        : 0;

                    $questionStats['options'][] = [
                        'text' => $option->option_text,
                        'votes' => $option->votes_count,
                        'percentage' => $percentage,
                        'color' => $option->color ?? null
                    ];
                }

                $statistics[] = $questionStats;
            }
        }

        return view('surveys.thanks', compact('survey', 'totalVotes', 'statistics', 'showResults'));
    }

    public function finished($publicSlug)
    {
        $survey = Survey::where('public_slug', $publicSlug)
            ->where('is_finished', true)
            ->with(['questions.options' => function($query) {
                $query->withCount(['votes' => function($q) {
                    $q->valid();
                }]);
            }])
            ->firstOrFail();

        // Calcular estadísticas generales - Solo votos válidos
        $uniqueVoters = Vote::where('survey_id', $survey->id)
            ->valid()
            ->distinct('ip_address')
            ->count('ip_address');

        // El total de votos es la suma de votos de la primera pregunta
        $firstQuestion = $survey->questions->first();
        $totalVotes = $firstQuestion ? $firstQuestion->options->sum('votes_count') : 0;

        // Preparar datos para los gráficos
        $statistics = [];
        foreach ($survey->questions as $question) {
            $questionStats = [
                'question' => $question->question_text,
                'type' => $question->question_type,
                'options' => [],
                'total_responses' => 0
            ];

            $totalQuestionVotes = $question->options->sum('votes_count');
            $questionStats['total_responses'] = $totalQuestionVotes;

            foreach ($question->options as $option) {
                $percentage = $totalQuestionVotes > 0
                    ? round(($option->votes_count / $totalQuestionVotes) * 100, 1)
                    : 0;

                $questionStats['options'][] = [
                    'text' => $option->option_text,
                    'votes' => $option->votes_count,
                    'percentage' => $percentage,
                    'color' => $option->color ?? null
                ];
            }

            $statistics[] = $questionStats;
        }

        return view('surveys.finished', compact('survey', 'uniqueVoters', 'totalVotes', 'statistics'));
    }

    public function checkVote($publicSlug)
    {
        $survey = Survey::where('public_slug', $publicSlug)->where('is_active', true)->firstOrFail();

        $fingerprint = request()->input('fingerprint');

        $hasVoted = false;
        if ($fingerprint) {
            $hasVoted = Vote::where('survey_id', $survey->id)
                ->where('fingerprint', $fingerprint)
                ->exists();
        }

        return response()->json(['has_voted' => $hasVoted]);
    }
}

