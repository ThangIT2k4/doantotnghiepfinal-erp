<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi thanh toán</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('assets/js/notifications.js') }}"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            opacity: 0.9;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="error-title">Lỗi thanh toán</h1>
        <p class="error-message" id="errorMessage">
            {{ $message ?? 'Token không hợp lệ hoặc đã hết hạn' }}
        </p>
        <a href="javascript:history.back()" class="btn-home">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display error notification
            const errorMessage = document.getElementById('errorMessage').textContent.trim();
            if (typeof Notify !== 'undefined') {
                Notify.error(errorMessage, 'Lỗi thanh toán!');
            }
        });
    </script>
</body>
</html>

