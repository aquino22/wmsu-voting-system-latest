<?php
date_default_timezone_set('Asia/Manila');
session_start();
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
include('includes/conn.php');

// Check for ongoing election
$hasOngoingElection = false;
$registrationLink = '#';

$query = "SELECT id, custom_voter_option 
          FROM academic_years 
          WHERE status = 'Ongoing' 
          LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->execute();
$ay = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ay) {
	$hasOngoingElection = true;
	$registrationLink = ($ay['custom_voter_option'] == 1)
		? 'register_custom.php'
		: 'register.php';
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
	<title>WMSU (I-Elect) Voting System</title>
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
</head>




<body>

	<div class="limiter">
		<div class="container-login100"
			style="background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');">
			<div class="wrap-login100">

				<style>
					.disabled-link {
						cursor: not-allowed;
						opacity: 0.6;
						pointer-events: auto;
						/* allow JS click */
						text-decoration: none;
					}

					.input100 {
						background-color: rgb(245, 52, 52);
						/* Default background */
						padding: 10px;
						/* Optional: for better appearance */
						border: none;
						/* Optional: adjust as needed */
					}

					/* When the input is focused */
					.input100:focus {
						background-color: rgb(30, 60, 88);
						/* Light blue on focus */
						outline: none;
						/* Remove default outline */
					}

					/* When the placeholder is shown */
					.input100:placeholder-shown {
						background-color: #f9f9f9;
						/* Slightly different when empty */
					}

					/* Placeholder text color */
					.input100::placeholder {
						color: #888;
					}
				</style>

				<form class="login100-form validate-form" id="loginForm" method="POST" action="processes/login.php" onsubmit="return validateForm()">
					<input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
					<a href="../index.php" class="g-back">
						<i class="bi bi-arrow-left"></i>   Go back
					</a>
					<br>
					<span class="login100-form-logo">
						<img src="../external/img/wmsu-logo.png" class="img-fluid logo">
					</span>

					<span class="login100-form-title p-b-34 p-t-27">
						<h5>Welcome to <br> WMSU - Voting System</h5>
					</span>




					<p class="text-white">Email:</p>
					<div class="wrap-input100 validate-input" data-validate="Enter email">
						<input class="input100" type="text" name="email" id="email" placeholder="Email" required>

					</div>
					<!-- Error message placeholder for email -->
					<div id="email-error" class="error-text" style="color: white; font-size: 14px; margin-top: -20px; margin-bottom: 20px;"></div>

					<div class="spacer" style="margin: 25px;"></div>
					<p class="text-white">Password:</p>
					<div class="wrap-input100 validate-input" data-validate="Enter password">
						<input class="input100" type="password" name="password" id="password" placeholder="Password">

					</div>

					<div style=" margin-left:10px; display:flex; gap:10px;">
						<input type="checkbox" id="showPassword">
						<label for="showPassword" class="text-white">Show Password</label>
					</div>

					<!-- Error message placeholder for password -->
					<div id="password-error" class="error-text" style="color: white; font-size: 14px; margin-top: -20px; margin-bottom: 20px;"></div>

					<!-- Uncomment if needed -->
					<!-- <div class="text-center pt-3 pb-5">
        <div class="row">
            <div class="col">
                <a class="txt1" href="candidacy.php">
                    <i class="bi bi-person-vcard"></i>   File Candidacy
                </a>
            </div>
            <div class="col">
                <a class="txt1" href="#">
                    <i class="bi bi-question-circle-fill"></i>   Forgot Password?
                </a>
            </div>
        </div>
    </div> -->

					<div class="container-login100-form-btn mt-5">

						<input type="hidden" id="location" name="location" value="N/A">

						<!-- Removed redundant <input type="submit"> -->
						<button class="login100-form-btn" type="submit">
							<i class="bi bi-universal-access"></i> Login
						</button>



					</div>

				</form>

				<div style="display: flex; justify-content: center; margin-top: 10px">
					<a href="#" class="text-white" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
						<i class="bi bi-lock-fill"></i> Forgot Password?
					</a>

				</div>

				<div style="display: flex; justify-content: flex-end; margin-top: 10px;">
					<a
						href="<?= $hasOngoingElection ? $registrationLink : '#' ?>"
						class="text-white <?= !$hasOngoingElection ? 'disabled-link' : '' ?>"
						id="registerBtn">
						<i class="bi bi-person-fill"></i> Register
					</a>
				</div>
			</div>
		</div>


		<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content bg-white">
					<div class="modal-header">
						<h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<label for="forgotEmail" class="form-label">Enter your email address:</label>
						<input type="email" class="form-control" id="forgotEmail" placeholder="you@email.com" required>
					</div>
					<div class="modal-footer">
						<button type="button" id="submitForgotPassword" class="btn btn-primary w-100">Send Reset Link</button>
					</div>
				</div>
			</div>
		</div>



		<script>
			async function validateForm() {
				const email = document.getElementById('email').value.trim();
				const password = document.getElementById('password').value.trim();

				const emailError = document.getElementById('email-error');
				const passwordError = document.getElementById('password-error');
				emailError.textContent = '';
				passwordError.textContent = '';

				let isValid = true;

				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!email || !emailPattern.test(email)) {
					emailError.textContent = 'Invalid email.';
					isValid = false;
				}
				if (!password || password.length < 6) {
					passwordError.textContent = 'Invalid password.';
					isValid = false;
				}
				if (!isValid) return false;

				// Hash the password after validation
				const hashedPassword = sha256(password);
				document.getElementById('password').value = hashedPassword;

				// Allow form submission
				return true;
			}

			// SHA-256 function using CryptoJS
			function sha256(str) {
				return CryptoJS.SHA256(str).toString();
			}
		</script>


		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>






		<style>
			/* Optional: Adjust error text positioning if needed */
			.error-text {
				text-align: left;
				padding-left: 20px;
			}
		</style>
	</div>
	</div>
	</div>

	<script>
		const password = document.getElementById("password");
		const confirmPassword = document.getElementById("confirm_password");
		const showPassword = document.getElementById("showPassword");

		showPassword.addEventListener("change", function() {
			const type = this.checked ? "text" : "password";
			password.type = type;
			confirmPassword.type = type;
		});


		function validateEmail() {
			// Get the email input value
			const email = document.getElementById('email').value.trim();

			// Simple email regex for validation
			const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if (!email) {
				// Empty email
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: 'Please enter your email!',
					confirmButtonColor: '#d33'
				});
				return false; // Prevent form submission
			} else if (!emailPattern.test(email)) {
				// Invalid email format
				Swal.fire({
					icon: 'error',
					title: 'Invalid Email',
					text: 'Please enter a valid email address (e.g., user@example.com)!',
					confirmButtonColor: '#d33'
				});
				return false; // Prevent form submission
			}

			// If email is valid, allow form submission
			return true;
		}
	</script>

	<div id="dropDownSelect1"></div>

	<!--===============================================================================================-->
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
	<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
	<!--===============================================================================================-->
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
	<!--===============================================================================================-->
	<script src="vendor/countdowntime/countdowntime.js"></script>
	<!--===============================================================================================-->
	<script src="js/main.js"></script>

	<script>
		document.getElementById('loginForm').addEventListener('submit', function(e) {
			e.preventDefault(); // Prevent immediate submission



			// Request geolocation
			if (navigator.geolocation) {
				navigator.geolocation.getCurrentPosition(
					(position) => {
						const location = `${position.coords.latitude},${position.coords.longitude}`;
						document.getElementById('location').value = location;
						this.submit(); // Submit form with location
					},
					(error) => {
						console.log('Geolocation declined or unavailable:', error.message);
						document.getElementById('location').value = 'N/A';
						this.submit(); // Submit with N/A
					}, {
						timeout: 10000
					} // 10-second timeout
				);
			} else {
				document.getElementById('location').value = 'N/A';
				this.submit(); // Submit with N/A if geolocation unsupported
			}
		});
	</script>

</body>

<?php
if (isset($_SESSION['error'])) {
	echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '" . $_SESSION['error'] . "'
            });
          </script>";
	unset($_SESSION['error']); // Remove error message after displaying
}
?>

<?php
if (isset($_SESSION['STATUS'])) {
	$status = $_SESSION['STATUS'];
	$message = $_SESSION['MESSAGE'];

	echo $status;

	echo $message;

	echo "<script>
        Swal.fire({
            icon: '$status',
            title: '$status',
            text: '$message',
            showConfirmButton: true,
        });
    </script>";

	unset($_SESSION['STATUS']);
	unset($_SESSION['MESSAGE']);
}
?>

<script>
	$('#submitForgotPassword').on('click', function() {
		const email = $('#forgotEmail').val().trim();

		if (!email) {
			Swal.fire('Oops!', 'Please enter your email address.', 'warning');
			return;
		}

		Swal.fire({
			title: 'Processing...',
			html: 'Please wait while we verify your email.',
			didOpen: () => {
				Swal.showLoading();
			},
			allowOutsideClick: false
		});

		$.post('processes/send_reset_link.php', {
			email: email
		}, function(response) {
			if (response.success) {
				Swal.fire('Success!', response.message, 'success');
				$('#forgotPasswordModal').modal('hide');
			} else {
				Swal.fire('Error!', response.message, 'error');
			}
		}, 'json').fail(function() {
			Swal.fire('Error!', 'Server error. Please try again later.', 'error');
		});
	});

	document.getElementById("registerBtn").addEventListener("click", function(e) {
		<?php if (!$hasOngoingElection): ?>
			e.preventDefault(); // Stop the default link behavior
			Swal.fire({
				icon: 'info',
				title: 'Registration Closed',
				text: 'There is currently no ongoing academic semester. Please check back soon.',
				confirmButtonColor: '#3085d6',
			});
		<?php endif; ?>
	});
</script>
<?php if (isset($_GET['require']) && $_GET['require'] === 'login'): ?>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			Swal.fire({
				icon: 'warning',
				title: 'Login Required',
				text: 'Filing for candidacy requires you to be logged in. Please login first.',
				confirmButtonText: 'OK'
			});
		});
	</script>
<?php endif; ?>

</html>

<?php
// Function to reset capacity for a new day
function resetCapacity($pdo)
{
	// Get the current date
	$currentDate = date('Y-m-d');

	// Query to check if any records have a date_added from a previous day
	$sql = "SELECT id, DATE(date_added) AS added_date FROM email WHERE DATE(date_added) < :currentDate";
	$stmt = $pdo->prepare($sql);
	$stmt->execute(['currentDate' => $currentDate]);
	$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// If there are records with an older date, reset their capacity
	if (!empty($records)) {
		// Update capacity to 0 (or your desired default value)
		$updateSql = "UPDATE email SET capacity = 0 WHERE DATE(date_added) < :currentDate";
		$updateStmt = $pdo->prepare($updateSql);
		$updateStmt->execute(['currentDate' => $currentDate]);

		// Optionally, update date_added to the current date
		$updateDateSql = "UPDATE email SET date_added = NOW() WHERE DATE(date_added) < :currentDate";
		$updateDateStmt = $pdo->prepare($updateDateSql);
		$updateDateStmt->execute(['currentDate' => $currentDate]);
	} else {
	}
}

// Run the reset function
resetCapacity($pdo);
?>