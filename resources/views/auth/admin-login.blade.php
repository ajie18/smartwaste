<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in</title>
    <style>
        body { align-items: center; background: #f3f6f4; color: #17231d; display: flex; font: 16px system-ui, sans-serif; justify-content: center; margin: 0; min-height: 100vh; }
        main { background: white; border: 1px solid #dce5df; border-radius: 14px; box-shadow: 0 12px 35px #173a2612; max-width: 380px; padding: 32px; width: calc(100% - 40px); }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        p { color: #5e6f64; margin: 0 0 24px; }
        label { display: block; font-size: .9rem; font-weight: 600; margin: 16px 0 6px; }
        input { border: 1px solid #cad6ce; border-radius: 8px; box-sizing: border-box; font: inherit; padding: 11px 12px; width: 100%; }
        button { background: #167447; border: 0; border-radius: 8px; color: white; cursor: pointer; font: inherit; font-weight: 700; margin-top: 22px; padding: 12px; width: 100%; }
        .error { color: #b42318; margin-top: 8px; }
    </style>
</head>
<body>
<main>
    <h1>Admin sign in</h1>
    <p>Sign in with an existing administrator account to open the operations map.</p>
    <form method="post" action="{{ route('admin.login.store') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
