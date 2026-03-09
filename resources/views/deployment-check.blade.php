<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deployment Check</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#0b1020; color:#e5e7eb; margin:0; }
        .wrap { max-width: 720px; margin: 48px auto; padding: 24px; }
        .card { background:#111827; border:1px solid #1f2937; border-radius: 12px; padding: 20px; }
        .ok { color:#34d399; font-weight:700; margin:0 0 12px; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:14px; }
        .muted { color:#9ca3af; }
        code { background:#0f172a; padding:2px 6px; border-radius:6px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1 class="ok">Deploiement reussi</h1>
            <p class="muted">Cette page confirme que l'application est accessible apres deploiement.</p>
            <div class="grid">
                <div>Application</div><div><code>{{ $appName }}</code></div>
                <div>Environnement</div><div><code>{{ $environment }}</code></div>
                <div>PHP</div><div><code>{{ $phpVersion }}</code></div>
                <div>Laravel</div><div><code>{{ $laravelVersion }}</code></div>
                <div>Horodatage</div><div><code>{{ $timestamp }}</code></div>
            </div>
            <p class="muted" style="margin-top:14px;">URL de verification: <code>/deployment-check</code></p>
        </div>
    </div>
</body>
</html>
