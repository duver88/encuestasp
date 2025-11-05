<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'restrict_voting',
    ];

    protected $casts = [
        'restrict_voting' => 'boolean',
    ];

    /**
     * Obtener todas las encuestas de este grupo
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Verificar si un usuario (por fingerprint) ya votó en alguna encuesta del grupo
     */
    public function hasVotedInGroup(string $fingerprint): bool
    {
        if (!$this->restrict_voting) {
            return false;
        }

        // Optimizado: usar JOIN directo para mejor rendimiento
        return Vote::join('questions', 'votes.question_id', '=', 'questions.id')
            ->join('surveys', 'questions.survey_id', '=', 'surveys.id')
            ->where('surveys.survey_group_id', $this->id)
            ->where('votes.fingerprint', $fingerprint)
            ->where('votes.is_valid', true)
            ->exists();
    }

    /**
     * Obtener la encuesta en la que el usuario ya votó (si existe)
     */
    public function getVotedSurvey(string $fingerprint): ?Survey
    {
        if (!$this->restrict_voting) {
            return null;
        }

        // Optimizado: usar JOIN directo en lugar de whereHas para mejor rendimiento
        $vote = Vote::join('questions', 'votes.question_id', '=', 'questions.id')
            ->join('surveys', 'questions.survey_id', '=', 'surveys.id')
            ->where('surveys.survey_group_id', $this->id)
            ->where('votes.fingerprint', $fingerprint)
            ->where('votes.is_valid', true)
            ->select('votes.*', 'surveys.id as survey_id', 'surveys.title as survey_title')
            ->first();

        if (!$vote) {
            return null;
        }

        // Crear objeto Survey con los datos obtenidos
        $survey = new Survey();
        $survey->id = $vote->survey_id;
        $survey->title = $vote->survey_title;
        $survey->exists = true;

        return $survey;
    }
}
