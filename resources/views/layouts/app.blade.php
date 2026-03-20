<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SnapPOS Server')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f0f4f8;
            --card: #fff;
            --border: #d0d7de;
            --text: #111827;
            --muted: #4b5563;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #15803d;
            --error: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: min(960px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .08);
        }
        h1, h2, h3 { margin-top: 0; }
        p, li { line-height: 1.6; color: var(--muted); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        label { display: block; font-weight: 700; margin-bottom: 6px; }
        input[type="text"], input[type="password"], input[type="number"], select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
        }
        button {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: var(--primary-dark); }
        .notice {
            border-left: 4px solid var(--primary);
            background: #eff6ff;
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 6px;
        }
        .notice.success { border-left-color: var(--success); background: #f0fdf4; }
        .notice.error { border-left-color: var(--error); background: #fef2f2; }
        .mono {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            color: var(--text);
        }
    </style>
</head>
<body>
<div class="page">
    <main class="card">
        @yield('content')
    </main>
</div>
</body>
</html>
