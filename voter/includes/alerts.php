<?php
if (isset($_SESSION['STATUS'])) {
    $alert_type = $_SESSION['STATUS']; // Example: 'LOGIN_SUCCESSFUL'
        echo  $_SESSION['STATUS'];
    switch ($alert_type) {
        case 'LOGIN_SUCCESSFUL':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome back!',
                    timer: 2000,
                    showConfirmButton: false
                })
            </script>";
            break;

        case 'LOGIN_FAILED':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid email or password.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'LOGOUT_SUCCESS':
            echo "<script>
                Swal.fire({
                    icon: 'info',
                    title: 'Logged Out',
                    text: 'You have been logged out successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'login.php';
                });
            </script>";
            break;

        case 'ACCOUNT_BLOCKED':
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Blocked',
                    text: 'Please contact support.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'PASSWORD_CHANGED':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Password Updated',
                    text: 'You have successfully changed your password.',
                    timer: 2000,
                    showConfirmButton: false
                });
            </script>";
            break;

        case 'SESSION_EXPIRED':
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Session Expired',
                    text: 'Please log in again.',
                    showConfirmButton: true
                }).then(() => {
                    window.location.href = 'login.php';
                });
            </script>";
            break;

        default:
            echo "<script>
                Swal.fire({
                    icon: 'info',
                    title: 'Notice',
                    text: 'Something happened!',
                    showConfirmButton: true
                });
            </script>";
            break;
    }

    unset($_SESSION['STATUS']); // Remove session variable after showing alert
}
?>
