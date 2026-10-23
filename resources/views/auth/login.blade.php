<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - SIOPS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
            color: #000;
            /* gradasi kuning: terang di bagian bawah-tengah, oranye kekuningan di tepi */
            background: radial-gradient(circle at 40% 80%, #fbf71e 0%, #fde410 35%, #fdb90b 80%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 2rem 9vw 2rem 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 390px;
            background: #efefef;
            border-radius: 14px;
            padding: 2rem 1.75rem 1.6rem;
        }

        .login-card h1 {
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .login-card .subtitle {
            margin-top: 1.1rem;
            margin-bottom: 1.6rem;
            font-size: 0.92rem;
        }

        .field { margin-bottom: 1.1rem; }

        .field input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            font-family: inherit;
            font-size: 0.85rem;
            color: #000;
            background: transparent;
            border: 1px solid #333;
            border-radius: 12px;
            outline: none;
        }

        .field input::placeholder { color: #777; }

        .field input:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.12);
        }

        .field input.is-invalid { border-color: #c62828; }

        .error-text {
            margin-top: 0.35rem;
            font-size: 0.78rem;
            color: #c62828;
        }

        .btn-signin {
            width: 100%;
            height: 46px;
            margin-top: 0.5rem;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: #2b2b2b;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .btn-signin:hover { background: #000; }

        .forgot {
            display: block;
            margin-top: 1.1rem;
            text-align: center;
            font-size: 0.75rem;
            color: #4a7fc1;
            text-decoration: underline;
        }

        /* Layar kecil (HP/tablet): kartu di tengah */
        @media (max-width: 768px) {
            body {
                justify-content: center;
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <h1>Sign In</h1>
    <p class="subtitle">Sign into your account</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <input type="text" name="username" value="{{ old('username') }}"
                   placeholder="Username" autocomplete="username" autofocus required
                   class="@error('username') is-invalid @enderror">
            @error('username')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <input type="password" name="password"
                   placeholder="Password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn-signin">Sign In</button>

        <a href="#" class="forgot"
           onclick="alert('Silakan hubungi Owner untuk mereset password Anda.'); return false;">
            Forgot Password?
        </a>
    </form>
</div>

</body>
</html>