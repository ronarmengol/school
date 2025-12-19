<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth_functions.php';
require_once '../includes/load_settings.php';

$error = "";

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'timeout') {
        $error = "Your session has expired due to inactivity. Please login again.";
    } elseif ($_GET['error'] == 'session_expired') {
        $error = "Session invalid. Please login again.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $login_result = attempt_login($conn, $username, $password);
        if ($login_result === true) {
            // Check for Parent Portal restrictions
            if ($_SESSION['role'] == 'parent') {
                if (get_setting('enable_parent_portal') != '1') {
                    // Portal closed for parents
                    session_destroy(); // Log them out immediately
                    header("Location: parent_closed.php");
                    exit();
                } else {
                    // Portal open, redirect to parents module
                    header("Location: " . BASE_URL . "modules/parents/index.php");
                    exit();
                }
            }

            // Standard redirect for other roles
            header("Location: " . BASE_URL . "modules/dashboard/index.php");
            exit();
        } else {
            $error = $login_result;
        }
    }
}

// Get school settings
$school_name = get_setting('school_name', 'School Management System');
$school_motto = get_setting('school_motto', 'Excellence in Education');
$school_logo = get_setting('school_logo', '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            height: 100vh;
            overflow: hidden;
            background: #ffffff;
        }

        .login-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* LEFT PANEL - Visual/Branding */
        .visual-panel {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .visual-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 500px;
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 120px;
            max-height: 120px;
            filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.2));
            background: white;
            padding: 20px;
            border-radius: 20px;
        }

        .school-name {
            font-size: 42px;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .school-motto {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 40px;
        }

        .visual-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 50px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .feature-title {
            font-size: 14px;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        .feature-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.4;
        }

        /* RIGHT PANEL - Login Form */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: #f8fafc;
        }

        .login-card {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 480px;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 15px;
            color: #64748b;
            font-weight: 500;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-family: inherit;
            background: #ffffff;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-control:hover {
            border-color: #cbd5e1;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        .login-footer {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .login-footer-text {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }

        .role-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            justify-content: center;
        }

        .role-badge {
            padding: 6px 12px;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .visual-panel {
                padding: 40px;
            }

            .visual-features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }

            .visual-panel {
                min-height: 300px;
                padding: 30px 20px;
            }

            .school-name {
                font-size: 28px;
            }

            .school-motto {
                font-size: 14px;
            }

            .visual-features {
                display: none;
            }

            .form-panel {
                padding: 30px 20px;
            }

            .login-card {
                padding: 35px 25px;
            }

            .login-title {
                font-size: 26px;
            }
        }

        /* Accessibility */
        .form-control:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }

        button:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- LEFT PANEL - Visual/Branding -->
        <div class="visual-panel">
            <div class="branding-content">
                <div class="logo-container">
                    <?php if (!empty($school_logo)): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . $school_logo; ?>"
                            alt="<?php echo htmlspecialchars($school_name); ?>">
                    <?php else: ?>
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                            <rect width="120" height="120" rx="20" fill="white" />
                            <path d="M60 30L35 45V75L60 90L85 75V45L60 30Z" fill="#667eea" />
                            <path d="M60 50L45 58V70L60 78L75 70V58L60 50Z" fill="#764ba2" />
                        </svg>
                    <?php endif; ?>
                </div>

                <h1 class="school-name"><?php echo htmlspecialchars($school_name); ?></h1>
                <p class="school-motto"><?php echo htmlspecialchars($school_motto); ?></p>

                <div class="visual-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="feature-title">Students</div>
                        <div class="feature-desc">Access your grades and attendance</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="feature-title">Teachers</div>
                        <div class="feature-desc">Manage classes and assessments</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div class="feature-title">Parents</div>
                        <div class="feature-desc">Monitor your child's progress</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                            </svg>
                        </div>
                        <div class="feature-title">Administrators</div>
                        <div class="feature-desc">Complete system control</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL - Login Form -->
        <div class="form-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="login-title">Welcome Back</h2>
                    <p class="login-subtitle">Sign in to access your school portal</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Admission Number</label>
                        <input type="text" name="username" id="username" class="form-control" required autofocus
                            autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn-login">Sign In</button>
                </form>

                <div class="forgot-password">
                    <a href="#"
                        onclick="alert('Please contact your school administrator to reset your password.'); return false;">Forgot
                        password?</a>
                </div>

                <div class="login-footer">
                    <p class="login-footer-text">Authorized users only</p>
                    <div class="role-badges">
                        <span class="role-badge">Student</span>
                        <span class="role-badge">Teacher</span>
                        <span class="role-badge">Parent</span>
                        <span class="role-badge">Admin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>