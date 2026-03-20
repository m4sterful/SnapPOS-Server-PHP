<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapPOS PHP Server Installer</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f0f0f1;
            --card: #fff;
            --border: #c3c4c7;
            --text: #1d2327;
            --muted: #50575e;
            --primary: #2271b1;
            --primary-dark: #135e96;
            --success: #00a32a;
            --error: #d63638;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
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
            width: min(860px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0,0,0,.06);
        }
        h1, h2, h3 { margin-top: 0; }
        p, li { line-height: 1.55; color: var(--muted); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        label { display: block; font-weight: 700; margin-bottom: 6px; }
        input[type="text"], input[type="password"], input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
        }
        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #f6f7f7;
        }
        button {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { background: var(--primary-dark); }
        .notice {
            border-left: 4px solid var(--primary);
            background: #f6f7f7;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .notice.success { border-left-color: var(--success); }
        .notice.error { border-left-color: var(--error); }
        .lists {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .lists section {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 16px;
            background: #fafafa;
        }
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
