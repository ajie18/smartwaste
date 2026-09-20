<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') | CleanCity</title>
    <style>
        :root {
            --ink: #17231e;
            --muted: #65756d;
            --paper: #f5f8f6;
            --card: #ffffff;
            --line: #dfe9e3;
            --green: #0d9668;
            --green-dark: #086b4c;
            --accent: @yield('accent', '#0d9668');
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--ink);
            background: var(--paper);
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        body::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: radial-gradient(circle at 85% 12%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 28%), linear-gradient(135deg, #ffffff 0%, var(--paper) 55%, #eaf4ef 100%);
            content: '';
        }
        .page { width: min(1120px, calc(100% - 40px)); min-height: 100vh; margin: 0 auto; display: grid; place-items: center; padding: 48px 0; }
        .shell { width: min(760px, 100%); padding: clamp(28px, 6vw, 72px); border: 1px solid var(--line); border-radius: 24px; background: color-mix(in srgb, var(--card) 92%, transparent); box-shadow: 0 24px 70px rgba(29, 68, 50, .10); text-align: center; animation: rise .5s ease both; }
        .brand { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 44px; color: var(--green-dark); font: 700 13px/1 'Consolas', monospace; letter-spacing: .12em; text-transform: uppercase; }
        .brand-mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; color: #fff; background: linear-gradient(135deg, var(--green), var(--green-dark)); font: 700 15px/1 'Segoe UI', sans-serif; }
        .code { margin: 0; color: var(--accent); font: 800 clamp(88px, 18vw, 164px)/.8 'Segoe UI', sans-serif; letter-spacing: -.08em; }
        h1 { margin: 34px 0 12px; font-size: clamp(25px, 4vw, 39px); letter-spacing: -.03em; }
        .message { max-width: 500px; margin: 0 auto; color: var(--muted); font-size: 17px; line-height: 1.65; }
        .rule { width: 72px; height: 4px; margin: 30px auto; border-radius: 99px; background: var(--accent); }
        .actions { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin-top: 34px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 20px; border: 1px solid var(--green); border-radius: 999px; color: #fff; background: var(--green); font-weight: 700; text-decoration: none; transition: transform .2s, box-shadow .2s, background .2s; }
        .button:hover { transform: translateY(-2px); background: var(--green-dark); box-shadow: 0 9px 18px rgba(13, 150, 104, .2); }
        .button.secondary { color: var(--green-dark); background: transparent; border-color: var(--line); }
        .button.secondary:hover { background: #eef7f2; box-shadow: none; }
        .meta { margin-top: 38px; color: #94a39b; font: 12px/1.5 'Consolas', monospace; }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 560px) { .page { width: min(100% - 24px, 760px); padding: 18px 0; } .shell { border-radius: 18px; } .brand { margin-bottom: 32px; } .actions { flex-direction: column; } .button { width: 100%; } }
    </style>
</head>
<body>
    <main class="page">
        <section class="shell" aria-labelledby="error-title">
            <div class="brand"><span class="brand-mark">C</span> CleanCity</div>
            <p class="code" aria-label="Error @yield('code')">@yield('code')</p>
            <div class="rule"></div>
            <h1 id="error-title">@yield('title')</h1>
            <p class="message">@yield('message')</p>
            <div class="actions">
                <a class="button" href="{{ url('/') }}">Back to home</a>
                <a class="button secondary" href="{{ url('/login') }}">Sign in</a>
            </div>
            <p class="meta">SMART WASTE OPERATIONS &middot; {{ config('app.name', 'CleanCity') }}</p>
        </section>
    </main>
</body>
</html>
