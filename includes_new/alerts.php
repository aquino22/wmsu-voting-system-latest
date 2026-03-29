<?php
if (isset($_SESSION['STATUS'])) {
    $alert_type = $_SESSION['STATUS']; // Example: 'EVENT_SUCCESS_ADDED'

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

        case 'SUCCESS_CANDIDACY':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Election Added',
                    text: 'The election has been successfully added.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'ELECT_NAME_CONFLICT':
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Election Name Conflict',
                    text: 'An election with this name already exists. Please choose a different name.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'ELECT_PERIOD_CONFLICT':
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Election Period Conflict',
                    text: 'The election period overlaps with an existing election. Please choose a different period.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'ERROR_CANDIDACY':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again later.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'EVENT_SUCCESS_ADDED':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Event Added Successfully!',
                    text: 'The event has been successfully added.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'CANDIDATE_ACCEPTED':
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidate Acceptance Success!',
                        text: 'The candidate has been successfully accepted.',
                        showConfirmButton: true
                    });
                </script>";
            break;

        case 'CANDIDATE_ACCEPTED_ERROR':
            echo "<script>
                        Swal.fire({
                            icon: 'error',
                           title: 'Candidate Acceptance Error!',
                        text: 'The candidate has been rejected.',
                        showConfirmButton: true
                        });
                    </script>";
            break;

        case 'EVENT_SUCCESS_FAILED':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Event Addition Failed',
                    text: 'There was an issue adding the event. Please try again.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'DUPLICATE_EVENT':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Event Addition Failed',
                    text: 'The event already exists! Please try a new name!',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'SUCCESS_STATUS_UPDATE':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated',
                    text: 'The participant status has been updated successfully.',
                    showConfirmButton: true
                });
            </script>";
            break;

        case 'ERROR_STATUS_UPDATE':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'There was an error updating the participant status. Please try again.',
                    showConfirmButton: true
                });
            </script>";
            break;

            case 'CANDIDATE_UPDATED':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidates Updated',
                        text: '{$_SESSION['MESSAGE']}',
                        showConfirmButton: true
                    });
                </script>";
                break;
    
            case 'CANDIDATE_NO_CHANGE':
                echo "<script>
                    Swal.fire({
                        icon: 'info',
                        title: 'No Updates Made',
                        text: '{$_SESSION['MESSAGE']}',
                        showConfirmButton: true
                    });
                </script>";
                break;
    
            case 'CANDIDATE_UPDATE_ERROR':
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: '{$_SESSION['ERROR_MESSAGE']}',
                        showConfirmButton: true
                    });
                </script>";
                break;

        // default:
        //     echo "<script>
        //         Swal.fire({
        //             icon: 'info',
        //             title: 'Notice',
        //             text: 'Something happened!',
        //             showConfirmButton: true
        //         });
        //     </script>";
        //     break;
    }

    unset($_SESSION['STATUS']); // Remove session variable after showing alert
}
