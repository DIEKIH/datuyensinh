<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTUET|ADMIN</title>
    <link rel="icon" href="img/CTUT_logo_nen.png" type="image/x-icon">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900');

        * {
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
            background: url("/images/system/CTUT_background.jpg") no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-box {
            width: 400px;
            background: rgba(0, 0, 0, 0.56);
            border: 2px solid #fff;
            border-radius: 10px;
            padding: 40px 30px;
            box-sizing: border-box;
        }

        .form-box img {
            display: block;
            margin: 0 auto 30px auto;
            width: 40%;
        }

        .input-box {
            position: relative;
            margin-bottom: 25px;
        }

        .input-box label {
            position: absolute;
            top: 50%;
            left: 5px;
            color: #fff;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 1rem;
            transition: 0.5s;
        }

        .input-box input {
            width: 100%;
            height: 35px;
            background: transparent;
            outline: none;
            border: none;
            border-bottom: 2px solid #fff;
            color: #fff;
            padding: 0 0 0 6px;
            font-size: 1rem;
        }

        input:focus~label,
        input:valid~label {
            top: -10px;
        }

        .input-box ion-icon {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2em;
            color: #fff;
        }

        .f-password {
            margin: 15px 0;
            font-size: 0.9em;
            color: #fff;
            display: flex;
            justify-content: flex-start;
        }

        .f-password label input {
            margin-right: 5px;
        }

        .f-password a {
            margin-left: auto;
            color: #fff;
            text-decoration: none;
        }

        .f-password a:hover {
            text-decoration: underline;
        }

        button {
            width: 100%;
            height: 50px;
            border-radius: 0 10px 0 10px;
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 20px;
            background: #fff;
            color: #000;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: transparent;
            color: #fff;
            border: 2px solid #fff;
        }

        .validate_login {
            font-size: 13px;
            color: red;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <div class="form-box">
        <img src="/images/system/CTUT_logo_nen.png" alt="Logo">
        <!-- Hiển thị lỗi nếu có (giữ nguyên logic Blade) -->


        <form method="POST" action="/login">
            @csrf
            <div class="input-box">
                <ion-icon name="mail-outline"></ion-icon>
                <input type="text" name="email" id="email" value="{{ $email ?? '' }}" required>
                <label>Email</label>
            </div>
            <div class="input-box">
                <ion-icon name="lock-closed-outline"></ion-icon>
                <input type="password" name="password" id="password" value="{{ $password ?? '' }}" required>
                <label>Password</label>
            </div>
            <div class="f-password">
                <label>
                    <input type="checkbox" name="remember_account" {{ $email ? 'checked' : '' }}>
                    Remember Me
                </label>
            </div>
            <button type="submit">Đăng nhập</button>
            @if (session('error'))
                <div class="validate_login">{{ session('error') }}</div>
            @endif
        </form>

    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>
