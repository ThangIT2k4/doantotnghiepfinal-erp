<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Simple MVC'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .nav {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .nav a {
            color: #667eea;
            text-decoration: none;
            padding: 8px 16px;
            margin-right: 10px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .nav a:hover {
            background: #667eea;
            color: white;
        }
        
        .content {
            padding: 40px;
            min-height: 300px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        
        .card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 0.85em;
            margin: 5px 5px 0 0;
        }
        
        .user-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #667eea;
        }
        
        .user-info .label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .user-info .value {
            color: #333;
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        
        ul {
            margin-left: 20px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Simple MVC Framework</h1>
            <p>A lightweight PHP MVC for testing and learning</p>
        </div>
        
        <div class="nav">
            <?php
            $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            ?>
            <a href="<?php echo $basePath; ?>/">🏠 Home</a>
            <a href="<?php echo $basePath; ?>/about">ℹ️ About</a>
            <a href="<?php echo $basePath; ?>/user/123">👤 User Profile</a>
            <a href="<?php echo $basePath; ?>/api/test">📡 API Test</a>
        </div>
        
        <div class="content">
            <?php echo $content; ?>
        </div>
        
        <div class="footer">
            <p><strong>Simple MVC v1.0</strong> | PHP <?php echo PHP_VERSION; ?></p>
            <p style="margin-top: 10px;">
                Request URI: <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></code>
            </p>
        </div>
    </div>
</body>
</html>

