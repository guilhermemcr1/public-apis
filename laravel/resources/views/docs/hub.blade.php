<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentação das APIs Públicas</title>
    <style>
        html { box-sizing: border-box; }
        *, *::before, *::after { box-sizing: inherit; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.5;
        }
        .topbar {
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px 20px;
        }
        .topbar__inner {
            max-width: 960px;
            margin: 0 auto;
        }
        .topbar__title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .topbar__subtitle {
            margin: 6px 0 0;
            font-size: 0.875rem;
            color: #94a3b8;
        }
        main {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px 20px 48px;
        }
        .intro {
            margin-bottom: 28px;
            font-size: 0.9375rem;
            color: #334155;
        }
        .cards {
            display: grid;
            gap: 16px;
        }
        @media (min-width: 640px) {
            .cards { grid-template-columns: repeat(2, 1fr); }
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }
        .card h2 {
            margin: 0 0 8px;
            font-size: 1.125rem;
            font-weight: 600;
        }
        .card p {
            margin: 0 0 14px;
            font-size: 0.875rem;
            color: #475569;
        }
        .endpoint {
            font-size: 0.8125rem;
            font-family: ui-monospace, monospace;
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 14px;
            color: #0f172a;
        }
        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .links .link {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
        }
        .link--primary {
            background: #2563eb;
            color: #eff6ff;
            border: 1px solid #3b82f6;
        }
        .link--primary:hover {
            background: #1d4ed8;
        }
        .link--secondary {
            background: #fff;
            color: #1e40af;
            border: 1px solid #cbd5e1;
        }
        .link--secondary:hover {
            background: #f8fafc;
        }
        footer {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 20px 32px;
            font-size: 0.8125rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar__inner">
            <h1 class="topbar__title">Documentação das APIs Públicas</h1>
            <p class="topbar__subtitle">Índice das APIs disponíveis e links para a documentação interativa (Swagger) e especificação OpenAPI.</p>
        </div>
    </header>
    <main>
        <p class="intro">
            Escolha uma API abaixo para abrir o Swagger UI ou baixar o JSON OpenAPI. Os endpoints públicos estão listados em cada card.
        </p>
        <div class="cards">
            @foreach (config('public-apis-docs.apis') as $api)
                @php
                    $slug = $api['slug'];
                    $docRoutes = config("l5-swagger.documentations.{$slug}.routes", []);
                    $swaggerPath = $docRoutes['api'] ?? '';
                    $openApiPath = $docRoutes['docs'] ?? '';
                @endphp
                <article class="card">
                    <h2>{{ $api['name'] }}</h2>
                    <p>{{ $api['description'] }}</p>
                    <div class="endpoint">{{ $api['endpoint'] }}</div>
                    <div class="links">
                        @if ($swaggerPath !== '')
                            <a class="link link--primary" href="{{ url($swaggerPath) }}">Swagger UI</a>
                        @endif
                        @if ($openApiPath !== '')
                            <a class="link link--secondary" href="{{ url($openApiPath) }}">OpenAPI JSON</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </main>
    <footer>
        Esta página também está disponível em <a href="{{ url('/docs') }}">/docs</a> e <a href="{{ url('/api/documentation') }}">/api/documentation</a>.
    </footer>
</body>
</html>
