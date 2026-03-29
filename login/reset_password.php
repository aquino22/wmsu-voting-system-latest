<?php
session_start();
require_once '../includes/conn.php'; // Your database connection file
$email = $_GET['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>WMSU (I-Elect) Voting System - Reset Password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="images/favicon-32x32.png" />
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!--===============================================================================================-->
    <style>
        input {
            color: black !important;
        }

        .reset-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }

        .reset-title {
            text-align: center;
            color: #B22222;
            margin-bottom: 25px;
            font-weight: bold;
        }

        .reset-form .input100 {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .reset-form .input100:focus {
            border-color: #B22222;
            box-shadow: 0 0 5px rgba(178, 34, 34, 0.3);
        }

        .reset-btn {
            background-color: #B22222;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .reset-btn:hover {
            background-color: #950000;
        }

        .message {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .password-rules {
            font-size: 12px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="limiter">
        <div class="container-login100" style="background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');">
            <div class="wrap-login100">
                <div class="reset-container">
                    <span class="login100-form-logo">
                        <img src="../external/img/wmsu-logo.png" class="img-fluid logo">
                    </span>
                    <br>
                    <h2 class="reset-title">Reset Password</h2>

                    <div id="message" class="message"></div>

                    <form class="reset-form" method="POST" id="resetForm">
                        <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

                        <label>New Password:</label>
                        <div class="wrap-input100 validate-input">
                            <input class="input100" type="password" id="new_password" name="new_password" required>
                            <div class="password-rules">Password must be at least 8 characters long</div>
                        </div>

                        <label>Re-confirm New Password:</label>
                        <div class="wrap-input100 validate-input">
                            <input class="input100" type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="container-login100-form-btn">
                            <button type="submit" class="login100-form-btn reset-btn">
                                <i class="bi bi-key-fill"></i> &nbsp;Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const messageDiv = document.getElementById('message');

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                // Validate passwords match
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    showMessage('Passwords do not match!', 'error');
                    return;
                }

                if (newPassword.length < 8) {
                    showMessage('Password must be at least 8 characters long!', 'error');
                    return;
                }

                // Submit form via AJAX
                const formData = new FormData(form);

                fetch('processes/reset.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            form.reset();

                            // Redirect after 2 seconds
                            setTimeout(() => {
                                window.location.href = 'index.php';
                            }, 6000);
                        } else {
                            showMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessage('An error occurred. Please try again.', 'error');
                    });
            });

            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = 'message ' + type;
                messageDiv.style.display = 'block';

                // Hide message after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>

</html>