<?php
/**
 * views/login.view.php — HTML del formulario de login.
 *
 * Variables disponibles (inyectadas por LoginController):
 *   $ctrl->error   string  — mensaje de error, vacío si no hubo intento fallido
 *   $ctrl->correo  string  — valor del correo para repoblar el campo tras error
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>FORTRESS8 — Cotizador Profesional</title>
    <link rel="icon" href="assets/img/favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <!-- PWA -->
    <link rel="manifest" href="/cotizador/manifest.json">
    <meta name="theme-color" content="#F67C01">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Cotizador">
    <link rel="apple-touch-icon" href="/cotizador/assets/icons/icon-192.png">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        /* ── Animaciones ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes logoIn {
            from { opacity: 0; transform: scale(.8); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes hexSpin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes hexPulse {
            0%, 100% { opacity: .06; }
            50%       { opacity: .13; }
        }
        @keyframes orb1 {
            0%, 100% { transform: translate(0, 0); }
            50%       { transform: translate(28px, -18px); }
        }
        @keyframes orb2 {
            0%, 100% { transform: translate(0, 0); }
            50%       { transform: translate(-22px, 26px); }
        }
        @keyframes orb3 {
            0%, 100% { transform: translate(0, 0); }
            50%       { transform: translate(18px, 22px); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25%       { transform: translateX(-5px); }
            75%       { transform: translateX(5px); }
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Base ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #080808;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-x: hidden;
            position: relative;
        }

        /* ── Gradientes de fondo ── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 55% 45% at 15% 10%,  rgba(246,124,1,.18)  0%, transparent 65%),
                radial-gradient(ellipse 45% 55% at 88% 90%,  rgba(226,71,27,.16)  0%, transparent 65%),
                radial-gradient(ellipse 35% 40% at 80% 15%,  rgba(246,124,1,.08)  0%, transparent 60%),
                radial-gradient(ellipse 40% 35% at 20% 85%,  rgba(226,71,27,.10)  0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }

        /* ── Orbes ambientales ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(70px);
        }
        .orb-1 { width:360px;height:360px; background:rgba(246,124,1,.13); top:-80px;left:-90px;  animation:orb1  9s ease-in-out infinite; }
        .orb-2 { width:300px;height:300px; background:rgba(226,71,27,.12);  bottom:-60px;right:-70px; animation:orb2 11s ease-in-out infinite; }
        .orb-3 { width:220px;height:220px; background:rgba(246,124,1,.07);  bottom:20%;left:10%;  animation:orb3 13s ease-in-out infinite; }

        /* ── Hexágono de fondo ── */
        .hex-bg {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
            animation: hexPulse 5s ease-in-out infinite;
        }

        /* ── Tarjeta ── */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(10,10,10,.88);
            border: 1px solid rgba(246,124,1,.18);
            border-radius: 20px;
            padding: 2.75rem 2.5rem;
            position: relative;
            z-index: 2;
            animation: fadeUp .6s cubic-bezier(.22,1,.36,1) both;
            backdrop-filter: blur(12px);
            box-shadow: 0 0 0 1px rgba(226,71,27,.08),
                        inset 0 1px 0 rgba(246,124,1,.06);
        }

        /* ── Logo ── */
        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            animation: logoIn .5s cubic-bezier(.22,1,.36,1) .1s both;
        }

        .logo-ring {
            width: 80px; height: 80px;
            border-radius: 20px;
            background: rgba(14,14,14,.9);
            border: 1px solid rgba(246,124,1,.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            position: relative;
            padding: 10px;
        }

        /* Anillo giratorio naranja → rojo */
        .logo-ring::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 21px;
            background: conic-gradient(from 0deg, transparent 50%, #F67C01 70%, #E2471B 82%, transparent 100%);
            animation: hexSpin 3s linear infinite;
            z-index: -1;
        }

        .logo-ring img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .brand-sub {
            font-size: 11px;
            letter-spacing: 2.5px;
            color: #F67C01;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* ── Separador ── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(246,124,1,.25), rgba(226,71,27,.2), transparent);
            margin: 0 0 1.75rem;
        }

        /* ── Encabezado del form ── */
        .form-label-top {
            font-size: 11px;
            letter-spacing: 2px;
            color: rgba(246,124,1,.6);
            text-transform: uppercase;
            margin-bottom: 6px;
            display: block;
        }

        .form-title {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 4px;
        }

        .form-subtitle {
            font-size: 13px;
            color: #444;
            margin: 0 0 2rem;
        }

        /* ── Campos ── */
        .field-group {
            margin-bottom: 1.25rem;
            animation: fadeUp .5s cubic-bezier(.22,1,.36,1) both;
        }
        .field-group:nth-of-type(1) { animation-delay: .15s; }
        .field-group:nth-of-type(2) { animation-delay: .22s; }

        .field-label {
            font-size: 11px;
            letter-spacing: 1px;
            color: #3a3a3a;
            text-transform: uppercase;
            display: block;
            margin-bottom: 9px;
        }

        .input-line {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            transition: border-color .25s;
        }
        .input-line:focus-within { border-color: #F67C01; }

        .input-line i {
            font-size: 17px;
            color: #2e2e2e;
            flex-shrink: 0;
            transition: color .25s;
        }
        .input-line:focus-within i { color: #F67C01; }

        .input-line input {
            background: transparent;
            border: none;
            outline: none;
            font-size: 14px;
            color: #ffffff;
            font-family: inherit;
            width: 100%;
        }
        .input-line input::placeholder { color: #222; }

        .toggle-pw {
            cursor: pointer;
            color: #222;
            font-size: 17px;
            flex-shrink: 0;
            transition: color .2s;
        }
        .toggle-pw:hover { color: #F67C01; }

        /* ── Opciones ── */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0 1.75rem;
            animation: fadeUp .5s cubic-bezier(.22,1,.36,1) .28s both;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #3a3a3a;
            cursor: pointer;
            user-select: none;
        }
        .remember-label input {
            accent-color: #F67C01;
            width: 14px; height: 14px;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: #F67C01;
            text-decoration: none;
            font-weight: 500;
            transition: color .2s;
        }
        .forgot-link:hover { color: #E2471B; }

        /* ── Botón ── */
        .login-btn {
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(100deg, #F67C01, #E2471B);
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: .3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            position: relative;
            overflow: hidden;
            animation: fadeUp .5s cubic-bezier(.22,1,.36,1) .34s both;
        }
        .login-btn:hover {
            opacity: .9;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(226,71,27,.35);
        }
        .login-btn:active { transform: translateY(0); box-shadow: none; }

        .login-btn.loading { pointer-events: none; opacity: .8; }
        .login-btn.loading .btn-text { opacity: 0; }
        .login-btn.loading .btn-icon { display: none; }
        .login-btn.loading::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        /* ── Error ── */
        .error-msg {
            margin-top: 1.25rem;
            padding: 11px 14px;
            background: rgba(226,71,27,.08);
            border-left: 3px solid #E2471B;
            border-radius: 0 6px 6px 0;
            color: #f0907a;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake .35s ease;
        }

        /* ── Footer seguridad ── */
        .security-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(246,124,1,.08);
            display: flex;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .security-badge {
            font-size: 11px;
            color: #2a2a2a;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .security-badge i { font-size: 13px; }

        .support-line {
            margin-top: 1rem;
            text-align: center;
            font-size: 12px;
            color: #242424;
        }
        .support-line a {
            color: #383838;
            text-decoration: none;
            transition: color .2s;
        }
        .support-line a:hover { color: #F67C01; }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            body { padding: 1.25rem; align-items: flex-start; padding-top: 3rem; }
            .login-card { padding: 2rem 1.5rem; border-radius: 16px; }
            .options-row { flex-direction: column; align-items: flex-start; gap: 12px; }
            .logo-ring { width: 68px; height: 68px; }
        }
    </style>
</head>
<body>

<!-- Orbes ambientales -->
<div class="orb orb-1" aria-hidden="true"></div>
<div class="orb orb-2" aria-hidden="true"></div>
<div class="orb orb-3" aria-hidden="true"></div>

<!-- Hexágono decorativo de fondo -->
<svg class="hex-bg" width="580" height="580" viewBox="0 0 580 580" aria-hidden="true">
    <polygon points="290,16 536,154 536,426 290,564 44,426 44,154"
             fill="none" stroke="#F67C01" stroke-width="44"/>
    <polygon points="290,70 496,186 496,394 290,510 84,394 84,186"
             fill="none" stroke="#E2471B" stroke-width="3"/>
    <polygon points="290,128 458,218 458,362 290,452 122,362 122,218"
             fill="none" stroke="#F67C01" stroke-width="1.5" opacity=".4"/>
</svg>

<!-- Tarjeta principal -->
<div class="login-card">

    <!-- Logo -->
    <div class="logo-wrap">
        <div class="logo-ring">
            <img
                src="uploads/logos/logos.png"
                alt="Logo Fortress8"
                onerror="this.style.display='none'; this.parentElement.innerHTML += '<span style=\'color:#F67C01;font-size:11px;font-weight:600;\'>F8</span>';"
            >
        </div>
        <p class="brand-name">FORTRESS8</p>
        <p class="brand-sub">Cotizador Profesional</p>
    </div>

    <div class="divider"></div>

    <!-- Encabezado -->
    <span class="form-label-top">Bienvenido de nuevo</span>
    <h1 class="form-title">Inicia sesión</h1>
    <p class="form-subtitle">Ingresa tus credenciales para continuar</p>

    <!-- Formulario -->
    <form method="POST" action="login.php" id="loginForm" novalidate>

        <!-- Correo -->
        <div class="field-group">
            <label class="field-label" for="correo">Correo electrónico</label>
            <div class="input-line">
                <i class="ti ti-mail" aria-hidden="true"></i>
                <input
                    type="email"
                    name="correo"
                    id="correo"
                    placeholder="correo@empresa.com"
                    required
                    autocomplete="email"
                    value="<?= htmlspecialchars($ctrl->correo) ?>"
                >
            </div>
        </div>

        <!-- Contraseña -->
        <div class="field-group">
            <label class="field-label" for="password">Contraseña</label>
            <div class="input-line">
                <i class="ti ti-lock" aria-hidden="true"></i>
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
                <i class="ti ti-eye toggle-pw" id="togglePw" role="button" aria-label="Mostrar contraseña"></i>
            </div>
        </div>

        <!-- Recordar / Olvidé -->
        <div class="options-row">
            <label class="remember-label">
                <input type="checkbox" name="remember">
                Recordar sesión
            </label>
            <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
        </div>

        <!-- Botón -->
        <button type="submit" class="login-btn" id="loginBtn">
            <i class="ti ti-arrow-right btn-icon" aria-hidden="true"></i>
            <span class="btn-text">Acceder al sistema</span>
        </button>

    </form>

    <!-- Mensaje de error -->
    <?php if ($ctrl->error !== ''): ?>
    <div class="error-msg" role="alert">
        <i class="ti ti-alert-circle" aria-hidden="true"></i>
        <?= htmlspecialchars($ctrl->error) ?>
    </div>
    <?php endif; ?>

    <!-- Badges de seguridad -->
    <div class="security-footer">
        <span class="security-badge">
            <i class="ti ti-lock" aria-hidden="true"></i> Cifrado SSL
        </span>
        <span class="security-badge">
            <i class="ti ti-shield-check" aria-hidden="true"></i> Acceso seguro
        </span>
        <span class="security-badge">
            <i class="ti ti-building" aria-hidden="true"></i> Uso interno
        </span>
    </div>

    <p class="support-line">
        ¿Problemas para acceder? &nbsp;<a href="#">Soporte técnico</a>
    </p>

    <button id="btn-instalar-app" type="button" style="display:none;margin:14px auto 0;padding:9px 18px;
        background:transparent;border:1px solid rgba(246,124,1,.4);color:#F67C01;border-radius:20px;
        font-size:12.5px;font-weight:600;cursor:pointer;align-items:center;gap:7px;font-family:inherit;">
        <i class="ti ti-download" aria-hidden="true"></i> Instalar app en este dispositivo
    </button>

</div>

<script>
    // Toggle contraseña
    const toggleBtn = document.getElementById('togglePw');
    const pwField   = document.getElementById('password');

    if (toggleBtn && pwField) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = pwField.type === 'password';
            pwField.type = isPassword ? 'text' : 'password';
            toggleBtn.className = isPassword ? 'ti ti-eye-off toggle-pw' : 'ti ti-eye toggle-pw';
            toggleBtn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    }

    // Spinner al enviar
    document.getElementById('loginForm')?.addEventListener('submit', function () {
        const btn    = document.getElementById('loginBtn');
        const correo = document.getElementById('correo')?.value;
        const pw     = document.getElementById('password')?.value;
        if (btn && correo && pw) {
            btn.classList.add('loading');
        }
    });

    // Service Worker (PWA)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/cotizador/service-worker.js')
                .then(r => console.log('SW registrado:', r.scope))
                .catch(e => console.error('SW error:', e));
        });
    }

    // Botón "Instalar app" (PWA)
    let deferredInstallPrompt = null;
    const btnInstalar = document.getElementById('btn-instalar-app');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        if (btnInstalar) btnInstalar.style.display = 'inline-flex';
    });

    btnInstalar?.addEventListener('click', async () => {
        if (!deferredInstallPrompt) return;
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        btnInstalar.style.display = 'none';
    });

    window.addEventListener('appinstalled', () => {
        if (btnInstalar) btnInstalar.style.display = 'none';
    });
</script>

</body>
</html>