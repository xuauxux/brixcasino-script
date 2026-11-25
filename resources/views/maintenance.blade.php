<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance · DonutTrix</title>
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/css/platform.css">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .mt {
            width: 100%;
            max-width: 560px;
            text-align: center;
            background: var(--pf-panel);
            border: 1px solid var(--pf-line);
            border-radius: 20px;
            padding: 40px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .mt__logo { font-size: 22px; font-weight: 900; letter-spacing: -.02em; }
        .mt__logo span { color: var(--pf-lime); }
        .mt__badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 14px; border-radius: 999px;
            background: rgba(255, 196, 83, .12); color: var(--pf-amber);
            font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
        }
        .mt__title { font-size: 28px; font-weight: 900; letter-spacing: -.02em; }
        .mt__text { color: var(--pf-dim); font-size: 14px; line-height: 1.6; max-width: 420px; }
        .mt__actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
        .mt__dot { width: 8px; height: 8px; border-radius: 50%; background: var(--pf-amber); animation: pulse 1.6s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1 } 50% { opacity: .25 } }
    </style>
</head>
<body class="pf">
    <main class="mt">
        <div class="mt__logo">DONUT<span>TRIX</span></div>
        <span class="mt__badge"><span class="mt__dot"></span> Maintenance in progress</span>
        <h1 class="mt__title">We'll be right back</h1>
        <p class="mt__text">{{ $message ?? \App\Services\Platform\Settings::maintenanceMessage() }}</p>
        <p class="mt__text" style="font-size:12px">Your balance, level and event letters are safe — nothing is being reset.</p>
        <div class="mt__actions">
            <a class="pf-btn" href="/">Try again</a>
            <a class="pf-btn pf-btn_ghost" href="https://discord.gg/qKMrjbMWVH" target="_blank" rel="noopener">Join Discord for updates</a>
        </div>
    </main>
</body>
</html>
