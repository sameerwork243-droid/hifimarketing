<?php
// ===== START SESSION =====
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Application Submitted | HIFI Marketing & Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HIFI Marketing & Technologies</title>
    
    <!-- ===== BROWSER TAB ICON (FAVICON) ===== -->
    <link rel="icon" href="/images/fav-icon.png" type="image/png" />
    <link rel="shortcut icon" href="/images/fav-icon.png" type="image/png" />
    
    <!-- Rest of head -->
    <link rel="stylesheet" href="css/style.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .success-box {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e9edf2;
            padding: 48px 56px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.06);
        }
        .success-box .icon {
            font-size: 64px;
            color: #4caf50;
            margin-bottom: 16px;
        }
        .success-box h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1c26;
            margin-bottom: 8px;
        }
        .success-box p {
            color: #3d4452;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 8px;
        }
        .success-box .job-name {
            font-weight: 700;
            color: #4a5cf5;
        }
        .success-box .applicant-name {
            font-weight: 600;
            color: #1a1c26;
        }
        .success-box .divider {
            border: 1px solid #e9edf2;
            margin: 24px 0;
        }
        .success-box .btn-primary {
            background: #4a5cf5;
            color: #fff;
            padding: 14px 40px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            display: inline-block;
            transition: 0.2s;
            border: none;
            box-shadow: 0 4px 20px rgba(74,92,245,0.2);
            text-decoration: none;
            cursor: pointer;
        }
        .success-box .btn-primary:hover {
            background: #3a4be0;
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(74,92,245,0.3);
        }
        .success-box .btn-secondary {
            background: transparent;
            color: #4a5cf5;
            padding: 14px 40px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            display: inline-block;
            transition: 0.2s;
            border: 2px solid #4a5cf5;
            text-decoration: none;
            margin-left: 12px;
        }
        .success-box .btn-secondary:hover {
            background: #4a5cf5;
            color: #fff;
        }
        body.dark-mode {
            background: #0b0d10;
        }
        body.dark-mode .success-box {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .success-box h1 {
            color: #eaeef2;
        }
        body.dark-mode .success-box p {
            color: #b0b8c5;
        }
        @media (max-width: 480px) {
            .success-box { padding: 32px 24px; }
            .success-box h1 { font-size: 24px; }
            .success-box .btn-secondary { margin-left: 0; margin-top: 10px; }
        }
    </style>
</head>
<body>

    <div class="success-box">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <h1>Application Submitted!</h1>
        <p>
            Thank you, <span class="applicant-name"><?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Applicant'; ?></span>!
        </p>
        <p>
            Your application for <span class="job-name"><?php echo isset($_GET['job']) ? htmlspecialchars($_GET['job']) : 'the position'; ?></span> has been received.
        </p>
        <p style="font-size:14px;color:#8a94a0;margin-top:4px;">
            We will review your application and get back to you soon.
        </p>

        <hr class="divider" />

        <div>
            <a href="job.php" class="btn-primary"><i class="fas fa-search"></i> View More Jobs</a>
            <a href="index.php" class="btn-secondary"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <script>
        // ===== LOAD DARK MODE =====
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>

</body>
</html>