<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không tìm thấy trang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #2c3e50;
        }

        .error-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(14, 69, 130, 0.1);
            padding: 60px 50px;
            text-align: center;
            max-width: 550px;
            width: 100%;
            border-top: 5px solid #0e4582;
        }

        .error-code {
            font-size: 72px;
            font-weight: 300;
            color: #0e4582;
            margin-bottom: 20px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .error-message {
            font-size: 15px;
            color: #7f8c8d;
            line-height: 1.7;
            margin-bottom: 40px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #0e4582;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0a3464;
        }

        .btn-secondary {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        .btn-secondary:hover {
            background-color: #d5dbdd;
        }

        .divider {
            margin: 35px 0 25px;
            height: 1px;
            background-color: #ecf0f1;
        }

        .help-section {
            font-size: 14px;
            color: #95a5a6;
            line-height: 1.6;
        }

        .help-section strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .contact-info {
            margin-top: 15px;
        }

        .contact-info a {
            color: #0e4582;
            text-decoration: none;
            font-weight: 500;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .error-container {
                padding: 40px 30px;
            }

            .error-code {
                font-size: 60px;
            }

            .error-title {
                font-size: 20px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">

        <div class="error-code">404</div>
        <h1 class="error-title">Không tìm thấy trang</h1>
         <p class="error-message">
            Trang bạn đang truy cập hiện đang được cập nhật hoặc bảo trì. 
            Chúng tôi sẽ sớm hoàn tất và đưa nội dung trở lại. Xin vui lòng quay lại sau.
        </p>

        <div class="btn-group">
            <button onclick="window.history.back()" class="btn btn-primary">
                Quay lại trang trước
            </button>
        </div>

        <div class="divider"></div>

        <div class="help-section">
            <strong>Cần hỗ trợ?</strong>
            <div class="contact-info">
                Điện thoại: <a href="tel:0123456789">(012) 3456 789</a><br>    
                Email: <a href="mailto:nguyenthanhdoi321@gmail.com">nguyenthanhdoi321@gmail.com</a>         
            </div>
        </div>
    </div>
</body>
</html>