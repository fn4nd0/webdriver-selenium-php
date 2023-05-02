<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
        }
        .container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 50px 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        form {
            background-color: #ffffff;
            border-radius: 5px;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #3490dc;
            color: #ffffff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2779bd;
        }
        .error {
            color: #e3342f;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form action="{{ route('login') }}" method="post">
            @csrf
            <div>
                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required>
                @error('email')
                <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="password">Senha:</label>
                <input type="password" name="password" id="password" required>
                @error('password')
                <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
