@extends('layouts.app')

@section('title', $survey->title)

@section('meta_description', $survey->description ?? 'Participa en esta encuesta y comparte tu opinión')

@section('og_image_full', $survey->og_image ? asset('storage/' . $survey->og_image) : ($survey->banner ? asset('storage/' . $survey->banner) : url('images/default-survey-preview.jpg')))

@section('og_title', $survey->title)
@section('og_description', $survey->description ?? 'Participa en esta encuesta y comparte tu opinión')

@section('content')
<div class="min-vh-100 d-flex align-items-center position-relative" style="background: #0a0a0a;">
    <!-- Efecto difuminado de fondo - Rojo y Negro elegante -->
    <div class="position-absolute w-100 h-100" style="overflow: hidden; z-index: 0;">
        <div class="blur-circle" style="position: absolute; top: -10%; left: -5%; width: 700px; height: 700px; background: radial-gradient(circle, rgba(220, 20, 60, 0.25) 0%, transparent 70%); filter: blur(80px);"></div>
        <div class="blur-circle" style="position: absolute; bottom: -15%; right: -5%; width: 650px; height: 650px; background: radial-gradient(circle, rgba(139, 0, 0, 0.3) 0%, transparent 70%); filter: blur(90px);"></div>
        <div class="blur-circle" style="position: absolute; top: 30%; right: 10%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255, 0, 0, 0.2) 0%, transparent 70%); filter: blur(70px);"></div>
        <div class="blur-circle" style="position: absolute; top: 50%; left: 20%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(178, 34, 34, 0.18) 0%, transparent 70%); filter: blur(65px);"></div>
        <div class="blur-circle" style="position: absolute; bottom: 30%; left: 40%; width: 450px; height: 450px; background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, transparent 70%); filter: blur(60px);"></div>
    </div>

    <div class="container survey-container position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <!-- Card principal con diseño rojo y negro -->
                <div class="card border-0 rounded-4 overflow-hidden" style="background: #ffffff; box-shadow: 0 20px 60px rgba(220, 20, 60, 0.3), 0 0 80px rgba(255, 0, 0, 0.1); border: 2px solid rgba(220, 20, 60, 0.3);">
                    <!-- Banner -->
                    @if($survey->banner)
                        <div class="banner-wrapper-form" style="background: linear-gradient(135deg, #1a1a1a 0%, #2d0000 100%); border-bottom: 2px solid rgba(220, 20, 60, 0.3); height: 400px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="{{ asset('storage/' . $survey->banner) }}"
                                 alt="Banner de {{ $survey->title }}"
                                 class="banner-img-form"
                                 style="width: 100%; height: 100%; object-fit: cover; opacity: 0.9;">
                        </div>
                    @else
                        <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center"
                             style="height: 200px; background: linear-gradient(135deg, #8B0000 0%, #DC143C 50%, #000000 100%); border-bottom: 2px solid rgba(220, 20, 60, 0.5);">
                            <i class="bi bi-clipboard-data" style="font-size: 4rem; color: #ffffff; text-shadow: 0 0 20px rgba(220, 20, 60, 0.8), 2px 2px 4px rgba(0,0,0,0.5);"></i>
                        </div>
                    @endif

                    <div class="card-body survey-card-body">
                        <!-- Título y descripción con tema rojo y negro -->
                        <div class="text-center survey-header-section">
                            <h1 class="survey-title">{{ $survey->title }}</h1>
                            @if($survey->description)
                                <p class="survey-description">{{ $survey->description }}</p>
                            @endif
                            <hr class="survey-divider">
                        </div>

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('no_tokens'))
                            <!-- No hay tokens disponibles -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="bi bi-exclamation-circle text-warning" style="font-size: 5rem;"></i>
                                </div>
                                <h2 class="h3 fw-bold text-dark mb-3">Encuesta No Disponible</h2>
                                <p class="lead text-muted mb-4">Esta encuesta ha alcanzado su límite de participantes.</p>
                                <p class="text-muted">
                                    <i class="bi bi-info-circle"></i> No hay cupos disponibles en este momento.
                                </p>
                            </div>
                        @elseif($hasVoted)
                            <!-- Ya votó -->
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                                </div>
                                <h2 class="h3 fw-bold text-dark mb-3">¡Gracias por participar!</h2>
                                <p class="lead text-muted mb-4">Ya has votado en esta encuesta anteriormente.</p>
                                <p class="text-muted">
                                    <i class="bi bi-info-circle"></i> Solo se permite un voto por persona.
                                </p>
                            </div>
                        @else
                            <!-- Formulario de votación -->
                            <form method="POST" action="{{ route('surveys.vote', $survey->public_slug) }}" id="voteForm">
                                @csrf
                                <input type="hidden" name="fingerprint" id="fingerprint">

                                @if(isset($token) && $token)
                                    <input type="hidden" name="token" value="{{ $token }}">
                                @endif

                                <!-- Datos del dispositivo para detección inteligente de fraude -->
                                <input type="hidden" name="device_data[user_agent]" id="device_user_agent">
                                <input type="hidden" name="device_data[platform]" id="device_platform">
                                <input type="hidden" name="device_data[screen_resolution]" id="device_resolution">
                                <input type="hidden" name="device_data[hardware_concurrency]" id="device_cpu">

                                <!-- Honeypot fields - campos trampa para bots (invisibles) -->
                                <input type="text" name="website" id="website" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off">
                                <input type="text" name="url_field" id="url_field" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off">

                                @foreach($survey->questions as $question)
                                    <div class="question-container">
                                        <div class="question-header">
                                            <div class="question-number">
                                                <span>{{ $loop->iteration }}</span>
                                            </div>
                                            <div class="grow">
                                                <h5 class="question-text">{{ $question->question_text }}</h5>

                                                @if($question->question_type === 'single_choice')
                                                    <!-- Radio buttons para selección única -->
                                                    @php
                                                        $hasImages = $question->options->contains(fn($opt) => !empty($opt->image));
                                                    @endphp

                                                    @if($hasImages)
                                                        <!-- Vista con imágenes en línea horizontal -->
                                                        @foreach($question->options->shuffle() as $option)
                                                            <style>
                                                                #option{{ $option->id }}:checked ~ .option-label-{{ $option->id }} {
                                                                    border-color: {{ $option->color ?? '#0d6efd' }} !important;
                                                                    background-color: {{ $option->color ?? '#0d6efd' }}11 !important;
                                                                }
                                                                .option-label-{{ $option->id }}:hover {
                                                                    border-color: {{ $option->color ?? '#0d6efd' }}66 !important;
                                                                    background-color: {{ $option->color ?? '#0d6efd' }}08 !important;
                                                                }
                                                            </style>
                                                            <div class="mb-3 option-label-{{ $option->id }} option-item">
                                                                <label class="option-label-inner" for="option{{ $option->id }}">
                                                                    <input class="form-check-input option-radio" type="radio"
                                                                           name="answers[{{ $question->id }}]"
                                                                           value="{{ $option->id }}"
                                                                           id="option{{ $option->id }}"
                                                                           style="accent-color: {{ $option->color ?? '#DC143C' }};"
                                                                           required>
                                                                    @if($option->image)
                                                                        <div class="option-image-container" style="border-color: {{ $option->color ?? '#DC143C' }};">
                                                                            <img src="{{ asset('storage/' . $option->image) }}"
                                                                                 alt="{{ $option->option_text }}"
                                                                                 class="option-image"
                                                                                 loading="lazy"
                                                                                 width="80"
                                                                                 height="80">
                                                                        </div>
                                                                    @endif
                                                                    <div class="option-text-container">
                                                                        <strong class="option-text">{{ $option->option_text }}</strong>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <!-- Vista tradicional sin imágenes -->
                                                        @foreach($question->options->shuffle() as $option)
                                                            <style>
                                                                #option{{ $option->id }}:checked {
                                                                    background-color: {{ $option->color ?? '#0d6efd' }};
                                                                    border-color: {{ $option->color ?? '#0d6efd' }};
                                                                }
                                                            </style>
                                                            <div class="form-check mb-3" style="background: #ffffff; padding: 1rem; border-radius: 10px; border: 2px solid #e0e0e0; transition: all 0.3s ease;">
                                                                <input class="form-check-input" type="radio"
                                                                       name="answers[{{ $question->id }}]"
                                                                       value="{{ $option->id }}"
                                                                       id="option{{ $option->id }}"
                                                                       style="accent-color: {{ $option->color ?? '#DC143C' }};"
                                                                       required>
                                                                <label class="form-check-label fw-medium" for="option{{ $option->id }}" style="color: #1a1a1a; cursor: pointer;">
                                                                    {{ $option->option_text }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                @else
                                                    <!-- Checkboxes para selección múltiple -->
                                                    @php
                                                        $hasImages = $question->options->contains(fn($opt) => !empty($opt->image));
                                                    @endphp

                                                    @if($hasImages)
                                                        <!-- Vista con imágenes en línea horizontal -->
                                                        @foreach($question->options as $option)
                                                            <style>
                                                                #option{{ $option->id }}:checked ~ .option-label-{{ $option->id }} {
                                                                    border-color: {{ $option->color ?? '#0d6efd' }} !important;
                                                                    background-color: {{ $option->color ?? '#0d6efd' }}11 !important;
                                                                }
                                                                .option-label-{{ $option->id }}:hover {
                                                                    border-color: {{ $option->color ?? '#0d6efd' }}66 !important;
                                                                    background-color: {{ $option->color ?? '#0d6efd' }}08 !important;
                                                                }
                                                            </style>
                                                            <div class="mb-3 option-label-{{ $option->id }} option-item">
                                                                <label class="option-label-inner" for="option{{ $option->id }}">
                                                                    <input class="form-check-input option-radio" type="checkbox"
                                                                           name="answers[{{ $question->id }}][]"
                                                                           value="{{ $option->id }}"
                                                                           id="option{{ $option->id }}"
                                                                           style="accent-color: {{ $option->color ?? '#DC143C' }};">
                                                                    @if($option->image)
                                                                        <div class="option-image-container" style="border-color: {{ $option->color ?? '#DC143C' }};">
                                                                            <img src="{{ asset('storage/' . $option->image) }}"
                                                                                 alt="{{ $option->option_text }}"
                                                                                 class="option-image"
                                                                                 loading="lazy"
                                                                                 width="80"
                                                                                 height="80">
                                                                        </div>
                                                                    @endif
                                                                    <div class="option-text-container">
                                                                        <strong class="option-text">{{ $option->option_text }}</strong>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <!-- Vista tradicional sin imágenes -->
                                                        @foreach($question->options as $option)
                                                            <style>
                                                                #option{{ $option->id }}:checked {
                                                                    background-color: {{ $option->color ?? '#0d6efd' }};
                                                                    border-color: {{ $option->color ?? '#0d6efd' }};
                                                                }
                                                            </style>
                                                            <div class="form-check mb-3" style="background: #ffffff; padding: 1rem; border-radius: 10px; border: 2px solid #e0e0e0; transition: all 0.3s ease;">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="answers[{{ $question->id }}][]"
                                                                       value="{{ $option->id }}"
                                                                       id="option{{ $option->id }}"
                                                                       style="accent-color: {{ $option->color ?? '#DC143C' }};">
                                                                <label class="form-check-label fw-medium" for="option{{ $option->id }}" style="color: #1a1a1a; cursor: pointer;">
                                                                    {{ $option->option_text }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Botón de envío -->
                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" class="btn btn-lg text-white fw-bold shadow"
                                            style="background: linear-gradient(135deg, #DC143C 0%, #8B0000 100%); padding: 1.2rem; border: 2px solid rgba(220, 20, 60, 0.5); text-shadow: 0 2px 4px rgba(0,0,0,0.5); box-shadow: 0 10px 30px rgba(220, 20, 60, 0.4), 0 0 40px rgba(255, 0, 0, 0.2);">
                                        <i class="bi bi-send-fill"></i> Enviar mi voto
                                    </button>
                                </div>

                                <div class="text-center mt-4">
                                    <small style="color: #b0b0b0; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);">
                                        <i class="bi bi-shield-check"></i> Tu voto es anónimo y seguro
                                    </small>
                                </div>
                            </form>
                        @endif
                    </div>

                    <!-- Footer del card -->
                    <div class="card-footer text-center py-3" style="background: rgba(10, 10, 10, 0.8); border-top: 1px solid rgba(220, 20, 60, 0.3);">
                        <small style="color: #808080;">
                            <i class="bi bi-clipboard-data"></i> Sistema de Encuestas
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// SISTEMA AVANZADO DE FINGERPRINTING
// ============================================

// 1. Canvas Fingerprinting
function getCanvasFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 200;
        canvas.height = 50;

        // Dibujar texto con diferentes fuentes y tamaños
        ctx.textBaseline = 'top';
        ctx.font = '14px "Arial"';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('Survey Fingerprint 🎨', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('Canvas FP', 4, 17);

        return canvas.toDataURL();
    } catch (e) {
        return 'canvas-not-supported';
    }
}

// 2. WebGL Fingerprinting
function getWebGLFingerprint() {
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

        if (!gl) return 'webgl-not-supported';

        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        return JSON.stringify({
            vendor: gl.getParameter(gl.VENDOR),
            renderer: gl.getParameter(gl.RENDERER),
            version: gl.getParameter(gl.VERSION),
            shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
            unmaskedVendor: debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : 'unknown',
            unmaskedRenderer: debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'unknown'
        });
    } catch (e) {
        return 'webgl-error';
    }
}

// 3. Detectar fuentes instaladas
function getFontsFingerprint() {
    const baseFonts = ['monospace', 'sans-serif', 'serif'];
    const testFonts = [
        'Arial', 'Verdana', 'Times New Roman', 'Courier New', 'Georgia',
        'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS', 'Trebuchet MS',
        'Impact', 'Lucida Sans', 'Tahoma', 'Calibri', 'Cambria',
        'Segoe UI', 'Helvetica', 'Geneva', 'Monaco', 'Consolas'
    ];

    const testString = 'mmmmmmmmmmlli';
    const testSize = '72px';
    const h = document.getElementsByTagName('body')[0];

    const s = document.createElement('span');
    s.style.fontSize = testSize;
    s.innerHTML = testString;
    const defaultWidth = {};
    const defaultHeight = {};

    for (const baseFont of baseFonts) {
        s.style.fontFamily = baseFont;
        h.appendChild(s);
        defaultWidth[baseFont] = s.offsetWidth;
        defaultHeight[baseFont] = s.offsetHeight;
        h.removeChild(s);
    }

    const detected = [];
    for (const font of testFonts) {
        let detected_font = false;
        for (const baseFont of baseFonts) {
            s.style.fontFamily = font + ',' + baseFont;
            h.appendChild(s);
            const matched = (s.offsetWidth !== defaultWidth[baseFont] || s.offsetHeight !== defaultHeight[baseFont]);
            h.removeChild(s);
            if (matched) {
                detected_font = true;
                break;
            }
        }
        if (detected_font) {
            detected.push(font);
        }
    }

    return detected.join(',');
}

// 4. Detectar plugins del navegador
function getPluginsFingerprint() {
    try {
        const plugins = [];
        for (let i = 0; i < navigator.plugins.length; i++) {
            const plugin = navigator.plugins[i];
            plugins.push(plugin.name + '::' + plugin.description);
        }
        return plugins.join('|');
    } catch (e) {
        return 'plugins-not-available';
    }
}

// 5. Información avanzada de hardware
function getHardwareFingerprint() {
    const nav = window.navigator;
    return JSON.stringify({
        cpuCores: nav.hardwareConcurrency || 'unknown',
        deviceMemory: nav.deviceMemory || 'unknown', // GB de RAM
        platform: nav.platform,
        oscpu: nav.oscpu || 'unknown',
        vendor: nav.vendor || 'unknown',
        maxTouchPoints: nav.maxTouchPoints || 0
    });
}

// 6. Audio Fingerprinting
function getAudioFingerprint() {
    try {
        const audioContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (!audioContext) return 'audio-not-supported';

        const context = new audioContext(1, 44100, 44100);
        const oscillator = context.createOscillator();
        oscillator.type = 'triangle';
        oscillator.frequency.value = 10000;

        const compressor = context.createDynamicsCompressor();
        oscillator.connect(compressor);
        compressor.connect(context.destination);
        oscillator.start(0);
        context.startRendering();

        return 'audio-context-created';
    } catch (e) {
        return 'audio-error';
    }
}

// Función para leer cookies
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Función para establecer cookies de larga duración
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Strict";
}

// Generar fingerprint avanzado único con TRIPLE PROTECCIÓN
function generateFingerprint() {
    // 1. Intentar recuperar de COOKIE (persiste incluso en incógnito si no se cierra)
    let fingerprint = getCookie('device_fingerprint');

    // 2. Si no hay en cookie, intentar desde localStorage
    if (!fingerprint) {
        fingerprint = localStorage.getItem('survey_fingerprint');
    }

    // 3. Si aún no hay fingerprint, generarlo desde cero
    if (!fingerprint) {
        const nav = window.navigator;
        const screen = window.screen;

        // Recopilar TODOS los datos del dispositivo
        const basicData = [
            nav.userAgent,
            nav.language,
            nav.languages ? nav.languages.join(',') : '',
            nav.platform,
            nav.hardwareConcurrency || 'unknown',
            screen.colorDepth,
            screen.width + 'x' + screen.height,
            screen.availWidth + 'x' + screen.availHeight,
            screen.pixelDepth,
            new Date().getTimezoneOffset(),
            !!window.sessionStorage,
            !!window.localStorage,
            !!window.indexedDB,
            typeof(window.openDatabase) !== 'undefined',
            nav.cookieEnabled,
            nav.doNotTrack || 'unknown',
            nav.maxTouchPoints || 0,
            window.devicePixelRatio || 1
        ].join('|');

        // Datos avanzados
        const canvasHash = hashString(getCanvasFingerprint());
        const webglHash = hashString(getWebGLFingerprint());
        const fontsHash = hashString(getFontsFingerprint());
        const pluginsHash = hashString(getPluginsFingerprint());
        const hardwareHash = hashString(getHardwareFingerprint());
        const audioHash = hashString(getAudioFingerprint());

        // Combinar todos los datos
        const combinedData = basicData + '|' + canvasHash + '|' + webglHash + '|' +
                           fontsHash + '|' + pluginsHash + '|' + hardwareHash + '|' + audioHash;

        // Generar hash final
        const finalHash = hashString(combinedData);
        fingerprint = 'fp_' + finalHash + '_' + Date.now().toString(36);
    }

    // GUARDAR EN TRIPLE UBICACIÓN para máxima persistencia
    // 1. LocalStorage (se borra en incógnito al cerrar)
    try {
        localStorage.setItem('survey_fingerprint', fingerprint);
    } catch (e) {
        console.log('LocalStorage no disponible');
    }

    // 2. Cookie de 365 días (MUY PERSISTENTE - incluso sobrevive a limpiezas parciales)
    setCookie('device_fingerprint', fingerprint, 365);

    // 3. Cookie específica de esta encuesta
    setCookie('survey_{{ $survey->id }}_fp', fingerprint, 365);

    return fingerprint;
}

// Función auxiliar para generar hash de strings
function hashString(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return Math.abs(hash).toString(36);
}

// Verificar si ya votó y redirigir
async function checkIfAlreadyVoted(fingerprint) {
    try {
        const response = await fetch('{{ route('surveys.check-vote', $survey->public_slug) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ fingerprint: fingerprint })
        });

        const data = await response.json();

        if (data.has_voted) {
            // Redirigir a la página de resultados
            window.location.href = '{{ route('surveys.thanks', $survey->public_slug) }}';
        }
    } catch (error) {
        console.error('Error verificando voto:', error);
    }
}

// Verificación del lado del cliente antes de enviar
function validateBeforeSubmit(event) {
    // Verificar si existe cookie específica de esta encuesta
    const surveyVoteCookie = getCookie('survey_{{ $survey->id }}_voted');

    if (surveyVoteCookie) {
        event.preventDefault();
        alert('Ya has votado en esta encuesta anteriormente. Solo se permite un voto por dispositivo.');
        window.location.href = '{{ route('surveys.thanks', $survey->public_slug) }}';
        return false;
    }

    return true;
}

// Establecer fingerprint y datos del dispositivo al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const fingerprint = generateFingerprint();
    const fingerprintInput = document.getElementById('fingerprint');

    if (fingerprintInput) {
        fingerprintInput.value = fingerprint;

        // Guardar datos del dispositivo para detección inteligente
        const nav = window.navigator;
        const screen = window.screen;

        document.getElementById('device_user_agent').value = nav.userAgent || '';
        document.getElementById('device_platform').value = nav.platform || '';
        document.getElementById('device_resolution').value = screen.width + 'x' + screen.height;
        document.getElementById('device_cpu').value = nav.hardwareConcurrency || 0;

        // Verificar si ya votó y redirigir automáticamente
        checkIfAlreadyVoted(fingerprint);

        // Agregar validación al formulario
        const voteForm = document.getElementById('voteForm');
        if (voteForm) {
            voteForm.addEventListener('submit', validateBeforeSubmit);
        }
    }

    // Animación suave al hacer scroll a preguntas
    const formChecks = document.querySelectorAll('.form-check-input');
    formChecks.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.form-check').style.transform = 'scale(1.02)';
            this.closest('.form-check').style.transition = 'transform 0.2s';
        });

        input.addEventListener('blur', function() {
            this.closest('.form-check').style.transform = 'scale(1)';
        });
    });
});
</script>

<style>
/* Estilos del banner del formulario */
.banner-wrapper-form {
    overflow: hidden;
    background: #f8f9fa;
    min-height: 200px;
    max-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banner-img-form {
    object-fit: contain !important;
    width: 100%;
    height: auto;
}

.form-check {
    transition: all 0.3s ease;
    padding: 0.75rem;
    border-radius: 0.5rem;
}

.form-check:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.15rem;
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
    font-size: 1.05rem;
}

.card {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estilos base para elementos */
.survey-container {
    padding: 1.5rem 0.75rem;
}

.survey-card-body {
    padding: 2rem 2.5rem;
}

.survey-header-section {
    background: #ffffff;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    margin-bottom: 2.5rem;
}

.survey-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    color: #DC143C;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.5px;
}

.survey-description {
    font-size: 1.25rem;
    color: #1a1a1a;
    margin-bottom: 0;
}

.survey-divider {
    margin: 1.5rem 0;
    border-color: rgba(220, 20, 60, 0.3);
    opacity: 0.5;
}

.question-container {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(220, 20, 60, 0.3);
    margin-bottom: 2rem;
}

.question-header {
    display: flex;
    align-items: start;
    background: #ffffff;
    padding: 1rem;
    border-radius: 10px;
    border: 2px solid rgba(220, 20, 60, 0.4);
    margin-bottom: 1.5rem;
}

.question-number {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #DC143C 0%, #8B0000 100%);
    box-shadow: 0 0 20px rgba(220, 20, 60, 0.4), inset 0 2px 4px rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(220, 20, 60, 0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-right: 1rem;
}

.question-number span {
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.question-text {
    color: #1a1a1a;
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 0;
}

.option-item {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #ffffff;
}

.option-label-inner {
    display: flex;
    align-items: center;
    width: 100%;
    cursor: pointer;
    margin: 0;
}

.option-radio {
    width: 22px;
    height: 22px;
    margin: 0;
    flex-shrink: 0;
}

.option-image-container {
    width: 80px;
    height: 80px;
    overflow: hidden;
    border-radius: 8px;
    background: #f0f0f0;
    border: 2px solid;
    flex-shrink: 0;
    margin: 0 1rem;
}

.option-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.option-text-container {
    flex: 1;
}

.option-text {
    display: block;
    font-size: 1.05rem;
    color: #1a1a1a;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .survey-container {
        padding: 1rem 0.5rem;
    }

    .survey-card-body {
        padding: 1.25rem;
    }

    .survey-header-section {
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .survey-title {
        font-size: 1.75rem;
    }

    .survey-description {
        font-size: 1rem;
    }

    .question-container {
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .question-header {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .question-number {
        width: 38px;
        height: 38px;
        margin-right: 0.75rem;
    }

    .question-number span {
        font-size: 1rem;
    }

    .question-text {
        font-size: 1rem;
    }

    .option-item {
        padding: 0.75rem;
    }

    .option-radio {
        width: 20px;
        height: 20px;
    }

    .option-image-container {
        width: 70px;
        height: 70px;
        margin: 0 0.75rem;
    }

    .option-text {
        font-size: 0.95rem;
    }

    /* Banner móvil */
    .banner-wrapper-form {
        height: 250px !important;
    }
}

@media (max-width: 576px) {
    .survey-container {
        padding: 0.5rem 0.25rem;
    }

    .survey-card-body {
        padding: 1rem;
    }

    .survey-header-section {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .survey-title {
        font-size: 1.5rem;
    }

    .survey-description {
        font-size: 0.9rem;
    }

    .question-container {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .question-header {
        padding: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .question-number {
        width: 32px;
        height: 32px;
        margin-right: 0.5rem;
    }

    .question-number span {
        font-size: 0.9rem;
    }

    .question-text {
        font-size: 0.9rem;
        line-height: 1.3;
    }

    .option-item {
        padding: 0.6rem;
    }

    .option-radio {
        width: 18px;
        height: 18px;
    }

    .option-image-container {
        width: 60px;
        height: 60px;
        margin: 0 0.5rem;
    }

    .option-text {
        font-size: 0.85rem;
        line-height: 1.3;
    }

    .card-img-top {
        height: 150px !important;
    }

    /* Banner extra pequeño */
    .banner-wrapper-form {
        height: 200px !important;
    }
}
</style>
@endsection

 