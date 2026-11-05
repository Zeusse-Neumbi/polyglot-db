<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Polyglot DB') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b1220;
            --panel: #111c2e;
            --line: #1e3a5f;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --accent-2: #34d399;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 20% 0%, rgba(56, 189, 248, .16), transparent 35%),
                radial-gradient(circle at 90% 20%, rgba(52, 211, 153, .10), transparent 30%),
                var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: grid;
            place-items: center;
            padding: 2rem;
        }
        main {
            width: min(920px, 100%);
            background: rgba(17, 28, 46, .86);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: clamp(2rem, 6vw, 4rem);
            box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
        }
        .eyebrow {
            color: var(--accent);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        h1 { font-size: clamp(2.4rem, 6vw, 4.4rem); margin: .5rem 0 1rem; line-height: 1; }
        p { color: var(--muted); line-height: 1.7; max-width: 62ch; }
        .stack { display: flex; flex-wrap: wrap; gap: .6rem; margin: 1.5rem 0 2rem; }
        .chip {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .4rem .8rem;
            font-size: .85rem;
            color: var(--text);
            background: rgba(56, 189, 248, .06);
        }
        .links { display: flex; flex-wrap: wrap; gap: .8rem; }
        a {
            color: var(--accent);
            text-decoration: none;
            border-bottom: 1px solid transparent;
        }
        a:hover { border-bottom-color: var(--accent); }
        .primary {
            background: var(--accent);
            color: #06121f;
            padding: .7rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            border: 0;
        }
        .primary:hover { background: #7dd3fc; border: 0; }
        footer { margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--line); color: var(--muted); font-size: .85rem; }
    </style>
</head>
<body>
    <main>
        <div class="eyebrow">Polyglot persistence lab</div>
        <h1>{{ config('app.name', 'Polyglot DB') }}</h1>
        <p>
            A Laravel social-network workload running across MariaDB, MongoDB, and Redis,
            with a Docker stack, Prometheus metrics, and Grafana dashboards. The goal is
            to make the tradeoffs between relational, document, and cache stores concrete
            and measurable.
        </p>

        <div class="stack" aria-label="Stack">
            <span class="chip">Laravel</span>
            <span class="chip">MariaDB primary/replica</span>
            <span class="chip">MongoDB replica set</span>
            <span class="chip">Redis cache/queue</span>
            <span class="chip">Prometheus</span>
            <span class="chip">Grafana</span>
        </div>

        <div class="links">
            <a class="primary" href="http://localhost:3000">Open Grafana</a>
            <a href="http://localhost:9099">Prometheus</a>
            <a href="http://localhost:8081">Adminer</a>
            <a href="http://localhost:8082">Mongo Express</a>
            <a href="https://github.com/Zeusse-Neumbi/polyglot-db">Source and README</a>
        </div>

        <footer>
            Health check: <a href="/health">/health</a>.
            The application is a research environment, not a production deployment.
        </footer>
    </main>
</body>
</html>
