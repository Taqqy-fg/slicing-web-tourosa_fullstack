<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — API Docs</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .card h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .card p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 24px;
        }
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .field input:focus { border-color: #3b82f6; }
        .btn {
            width: 100%;
            padding: 10px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: #2563eb; }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        .error {
            background: #fef2f2;
            color: #dc2626;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .error.show { display: block; }
    </style>
</head>
<body>
    <div class="card">
        <h1>API Documentation</h1>
        <p>Masuk sebagai Super Admin untuk mengakses dokumentasi API.</p>

        @error('email')
            <div class="error show">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ url('/docs/login') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="admin@tourosa.com" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="field" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="remember" name="remember" value="1" style="width:auto;">
                <label for="remember" style="margin:0;font-weight:400;color:#64748b;">Ingat saya</label>
            </div>
            <button type="submit" class="btn">Masuk</button>
        </form>
    </div>
</body>
</html>
