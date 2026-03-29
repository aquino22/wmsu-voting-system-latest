<?php
date_default_timezone_set('Asia/Manila');
session_start();

include('includes/conn.php');

try {

	// Get ongoing election with connected academic year
	$sql = "SELECT 
            id AS academic_year_id,
            year_label AS school_year,
            semester,
            custom_voter_option
        FROM academic_years
        ORDER BY id DESC
        LIMIT 1";

	$stmt = $pdo->query($sql);
	$electionData = $stmt->fetch(PDO::FETCH_ASSOC);

	$schoolYear        = $electionData['school_year'] ?? '';
	$semester          = $electionData['semester'] ?? '';
	$voterCustomFields = $electionData['custom_voter_option'] ?? '';
} catch (Exception $e) {
	echo $e->getMessage();
	error_log($e->getMessage());

	$electionData = [
		'school_year' => '',
		'semester' => '',
		'start_period' => '',
		'end_period' => ''
	];
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

	<!-- Bootstrap 5 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- Bootstrap 5 JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
	<link rel="stylesheet" type="text/css" href="css/main_1.css">
	<!--===============================================================================================-->
</head>




<body>

	<div class="limiter">
		<div class="container-login100"
			style="background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');">
			<div class="wrap-login100">
				<style>
					input {
						background-color: #e7e1e1 !important;
					}


					.field-container {
						margin: 15px 0;
					}

					.required::after {
						content: '*';
						color: red;
						margin-left: 5px;
					}

					.error {
						color: red;
					}

					.custom-file {
						margin-top: 10px;
					}

					input[type="file"] {
						background-color: transparent;
						width: 75% !important;
						cursor: pointer;
					}

					.custom-file-input {
						display: none;
					}

					.file-label {
						background-color: grey;
						color: white;
						padding: 8px 16px;
						border-radius: 4px;
						cursor: pointer;
						font-size: 14px;
					}

					.file-label:hover {
						background-color: #170B86FF;
						color: white !important;
					}

					.custom-file {
						display: block;


					}

					.card {
						background-color: white !important;
						color: black !important;
					}

					label {
						color: black !important;
					}

					input {
						color: white;
					}

					select {
						color: black !important;
					}

					.file-preview {
						display: block;
					}

					.input100 {
						width: 100% !important;
						background-color: #e7e1e1;
						/* Default background */
						padding: 10px;
						/* Optional: for better appearance */
						border: none;
						/* Optional: adjust as needed */
					}

					/* When the input is focused */
					.input100:focus {
						background-color: #e7e1e1;
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

					.container-login100 {
						background-size: cover;
						background-position: center;
						position: relative;
					}

					/* Create the glass effect overlay */
					.container-login100::before {
						content: '';
						position: absolute;
						top: 0;
						left: 0;
						right: 0;
						bottom: 0;
						background: rgba(255, 255, 255, 0.1);
						backdrop-filter: blur(10px);
						-webkit-backdrop-filter: blur(10px);
					}

					/* Form container styling */
					.wrap-login100 {
						position: relative;
						/* Needed for z-index */
						z-index: 1;
						background: rgba(255, 255, 255, 0.15);
						border-radius: 20px;
						border: 1px solid rgba(255, 255, 255, 0.2);
						box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
						backdrop-filter: blur(8px);
						-webkit-backdrop-filter: blur(8px);
						padding: 40px;
						width: 100%;
						max-width: 2000px;
					}

					/* Modify the input field styling */
					.wrap-input100 {
						background: rgba(255, 255, 255, 0.1);
						border: 1px solid #ff4d4d;
						/* Red border */
						border-radius: 12px;
						margin-bottom: 20px;
						backdrop-filter: blur(5px);
						-webkit-backdrop-filter: blur(5px);
						box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.3);
						/* Optional subtle glow */
					}

					/* Focus state for better interaction */
					.wrap-input100:focus-within {
						border-color: #ff1a1a;
						/* Darker red on focus */
						box-shadow: 0 0 0 2px rgba(255, 77, 77, 0.4);
					}

					.input100 {
						background: transparent !important;

					}

					select {
						color: white !important;
					}


					option {
						color: black !important;
					}

					.input100::placeholder {
						color: rgba(255, 255, 255, 0.7) !important;
					}

					/* Button styling */
					.login100-form-btn {
						background: rgba(255, 255, 255, 0.2);
						border: 1px solid rgba(255, 255, 255, 0.3);
						border-radius: 12px;
						color: white;
						backdrop-filter: blur(5px);
						-webkit-backdrop-filter: blur(5px);
						transition: all 0.3s ease;
					}

					.login100-form-btn:hover {
						background: rgba(255, 255, 255, 0.3);
						transform: translateY(-2px);
						box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
					}

					/* File input styling */
					.custom-file-input {
						background: rgba(255, 255, 255, 0.1);
						border: 1px solid rgba(255, 255, 255, 0.2);
						border-radius: 12px;
						color: white;
						padding: 10px;
						width: 100%;
						backdrop-filter: blur(5px);
						-webkit-backdrop-filter: blur(5px);
					}

					.file-label {
						display: block;
						margin-top: 8px;
						color: rgba(255, 255, 255, 0.8);
						font-size: 14px;
					}

					/* Select dropdown styling */
					.input100 select {
						background: rgba(255, 255, 255, 0.1) !important;
						color: white !important;
						border: none !important;
					}

					.input100 select option {
						background: rgba(0, 0, 0, 0.7);
						color: white;
					}

					/* Text color adjustments */
					.text-white {
						color: white !important;
						text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
					}

					.login100-form-title h5 {
						color: white;
						font-weight: 600;
						text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
					}

					/* Back link styling */
					.g-back {
						color: white;
						text-decoration: none;
						display: inline-block;
						margin-bottom: 20px;
						backdrop-filter: blur(5px);
						-webkit-backdrop-filter: blur(5px);
						padding: 8px 15px;
						border-radius: 8px;
						background: rgba(255, 255, 255, 0.1);
						border: 1px solid rgba(255, 255, 255, 0.2);
					}

					.g-back:hover {
						background: rgba(255, 255, 255, 0.2);
						color: white;
					}

					/* Logo styling */
					.login100-form-logo {
						filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
					}

					/* Default styles */
					.container-login100-form-btn {
						margin-top: 150px;
					}



					/* Mobile devices (portrait phones, less than 576px) */
					@media (max-width: 575px) {
						.container-login100-form-btn {
							margin-top: 400px !important;
						}


					}

					/* Small devices (landscape phones, 576px and up) */
					@media (min-width: 576px) and (max-width: 767px) {
						.container-login100-form-btn {
							margin-top: 400px !important;
						}
					}

					/* Optional: Adjust error text positioning if needed */
					.error-text {
						text-align: left;
						padding-left: 20px;
					}
				</style>


				<a href="index.php" class="g-back">
					<i class="bi bi-arrow-left"></i>   Go back
				</a>
				<br>

				<span class="login100-form-logo">
					<img src="../external/img/wmsu-logo.png" class="img-fluid logo">
				</span>

				<span class="login100-form-title p-b-34 p-t-27">
					<h5>WMSU Student Account Registration</h5>
				</span>

				<?php

				if ($voterCustomFields == 0) { ?>
					<form action="processes/create_account.php" method="POST" enctype="multipart/form-data">
						<div class="row justify-content-center">
							<div class="col md-3">

								<!-- Student ID -->
								<p class="text-white">Student ID: <span style="color: red;">*</span></p>
								<div class="wrap-input100 validate-input" data-validate="Enter student ID">
									<input class="input100" type="text" name="student_id" id="student_id"
										placeholder="e.g. 2021-00001"
										pattern="\d{4}-\d{5}"
										title="Format: YYYY-XXXXX (e.g., 2020-12345)" required>
								</div>

								<!-- Name Fields -->
								<p class="text-white">First Name: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<input class="input100" type="text" name="first_name" placeholder="First Name" required>
								</div>

								<p class="text-white">Middle Name:</p>
								<div class="wrap-input100">
									<input class="input100" type="text" name="middle_name" placeholder="Middle Name">
								</div>

								<p class="text-white">Last Name: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<input class="input100" type="text" name="last_name" placeholder="Last Name" required>
								</div>







								<!-- Hidden inputs for election data -->
								<input type="hidden" name="school_year" value="<?= htmlspecialchars($electionData['school_year']) ?>">
								<input type="hidden" name="semester" value="<?= htmlspecialchars($electionData['semester']) ?>">
								<!-- <input type="hidden" name="election_start" value="<?= htmlspecialchars($electionData['start_period']) ?>">
								<input type="hidden" name="election_end" value="<?= htmlspecialchars($electionData['end_period']) ?>"> -->

								<br>
								<!-- College -->
								<!-- Question Type -->
								<p class="text-white">College: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<select class="input100" name="college" id="college" required>
										<option value="" disabled selected>Select College</option>

									</select>
								</div>


								<!-- Course -->
								<p class="text-white">Course: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<select class="input100" name="course" id="course" required>
										<option value="" disabled selected>Select Course</option>
									</select>
								</div>

								<div id="major-container" style="display: none;">
									<p class="text-white">Major:</p>
									<div class="wrap-input100">
										<select class="input100" name="major" id="major">
											<option value="" disabled selected>None</option>

										</select>
									</div>
								</div>

								<!-- Department -->
								<p class="text-white">Department: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<select class="input100" name="department" id="department" required>
										<option value="" disabled selected>Select Department</option>
									</select>
								</div>

								<!-- Year Level -->
								<p class="text-white">Year Level: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<select class="input100" id="year_level" name="year_level" required>
										<option value="" disabled selected>Select Year Level</option>

									</select>
								</div>

								<!-- Campus Info -->
								<p class="text-white">WMSU Campus: <span style="color: red;">*</span></p>
								<div class="wrap-input100">
									<select id="wmsu_campus" class=" input100" name="wmsu_campus" id="wmsu_campus" required>
										<option value="" disabled selected>Select Campus</option>
									</select>
								</div>





								<p class="text-white" id="external-campus-label" style="display:none;">
									External Campus:
								</p>

								<div class="wrap-input100" id="external-campus-wrapper" style="display:none;">
									<select class="input100" name="external_campus" id="external_campus">
										<option value="" disabled selected>Select External Campus</option>
									</select>
								</div>


								<script>
									const college = document.getElementById("college");
									const course = document.getElementById("course");
									const department = document.getElementById("department");
									const major = document.getElementById("major");
									const majorContainer = document.getElementById("major-container");
									const yearLevel = document.getElementById("year_level");

									const campus = document.getElementById("wmsu_campus");
									const externalCampus = document.getElementById("external_campus");
									const externalWrapper = document.getElementById("external-campus-wrapper");
									const externalLabel = document.getElementById("external-campus-label");

									document.addEventListener("DOMContentLoaded", () => {
										loadColleges();
										loadMainCampuses();
									});

									function loadColleges() {
										fetch("get_colleges.php")
											.then(res => res.json())
											.then(data => {
												college.innerHTML = `<option value="" disabled selected>Select College</option>`;
												data.forEach(c => {
													college.innerHTML += `<option value="${c.college_id}">${c.college_name}</option>`;
												});
											});
									}

									function loadCourses(collegeId) {
										fetch("get_courses.php?college_id=" + collegeId)
											.then(res => res.json())
											.then(data => {
												course.innerHTML = `<option value="" disabled selected>Select Course</option>`;
												data.forEach(row => {
													course.innerHTML += `<option value="${row.id}">${row.course_name}</option>`;
												});
											});
									}

									function loadDepartments(collegeId) {
										fetch("get_departments.php?college_id=" + collegeId)
											.then(res => res.json())
											.then(data => {
												department.innerHTML = `<option value="" disabled selected>Select Department</option>`;
												data.forEach(row => {
													department.innerHTML += `<option value="${row.department_id}">${row.department_name}</option>`;
												});
											});
									}

									function loadMajors(courseId = null) {
										if (!courseId) {
											majorContainer.style.display = "none";
											yearLevel.innerHTML = `<option disabled selected>Select Year Level</option>`;
											return;
										}

										fetch("get_majors.php?course_id=" + courseId)
											.then(res => res.json())
											.then(data => {

												major.innerHTML = `
				<option value="" disabled selected>Select Major</option>
				<option value="">None</option>
			`;

												if (data.length > 0) {
													majorContainer.style.display = "block";

													data.forEach(m => {
														major.innerHTML += `<option value="${m.major_id}">${m.major_name}</option>`;
													});

													yearLevel.innerHTML = `<option disabled selected>Select Year Level</option>`;
												} else {
													majorContainer.style.display = "none";
													loadYearLevels(courseId, null);
												}
											});
									}

									function loadMainCampuses() {
										fetch("get_main_campuses.php")
											.then(res => res.json())
											.then(data => {
												campus.innerHTML = `<option value="" disabled selected>Select Campus</option>`;
												data.forEach(row => {
													campus.innerHTML += `<option value="${row.campus_id}">${row.campus_name}</option>`;
												});
											});
									}

									function loadExternalCampuses(parentId) {
										fetch("get_external_campuses.php?parent_id=" + parentId)
											.then(res => res.json())
											.then(data => {
												externalCampus.innerHTML = `<option value="" disabled selected>Select External Campus</option>`;
												data.forEach(row => {
													externalCampus.innerHTML += `<option value="${row.campus_id}">${row.campus_name}</option>`;
												});
											});
									}

									function loadYearLevels(courseId = null, majorId = null) {
										let url = "get_year_levels.php?";
										if (majorId) url += "major_id=" + majorId;
										else if (courseId) url += "course_id=" + courseId;

										fetch(url)
											.then(res => res.json())
											.then(data => {
												yearLevel.innerHTML = `<option disabled selected>Select Year Level</option>`;
												data.forEach(row => {
													yearLevel.innerHTML += `<option value="${row.id}">Year ${row.year_level}</option>`;
												});
											});
									}

									// =========================
									// EVENT LISTENERS
									// =========================

									college.addEventListener("change", function() {
										const collegeId = this.value;
										loadCourses(collegeId);
										loadDepartments(collegeId);
										loadMajors(null); // Reset majors
										yearLevel.innerHTML = `<option disabled selected>Select Year Level</option>`; // Reset year levels
									});

									course.addEventListener("change", function() {
										const courseId = this.value;
										loadMajors(courseId);
										loadYearLevels(courseId, null);
									});

									campus.addEventListener("change", function() {
										const campusId = this.value;
										const campusName = this.options[this.selectedIndex].text;

										if (campusName === "WMSU ESU") {
											externalWrapper.style.display = "block";
											externalLabel.style.display = "block";
											loadExternalCampuses(campusId);
										} else {
											externalWrapper.style.display = "none";
											externalLabel.style.display = "none";
										}
									});

									major.addEventListener("change", function() {
										const majorId = this.value;
										const courseId = course.value;
										loadYearLevels(courseId, majorId);
									});
								</script>
							</div>

							<div class="col-md-4">



								<!-- Email -->
								<p class="text-white"> Email: <span style="color: red;">*</span></p>
								<div class="wrap-input100 validate-input" data-validate="Enter email">
									<input class="input100" type="email" name="email" id="email"
										placeholder="Enter your WMSU Email (e.g., xt202012345@wmsu.edu.ph)"

										title="Format: xt<StudentID>@wmsu.edu.ph (e.g., xt202012345@wmsu.edu.ph)" required>
								</div>
								<div id="email-error" class="error-text" style="color: white; font-size: 14px; margin-top: -20px; margin-bottom: 20px;"></div>

								<br>

								<!-- Password -->
								<p class="text-white">Password: <span style="color: red;">*</span></p>
								<div class="wrap-input100 validate-input" data-validate="Enter password">
									<input class="input100" type="password" name="password" id="password" placeholder="Password" required>
								</div>
								<div id="password-error" class="error-text" style="color:white;font-size:14px;margin-top:-20px;margin-bottom:20px;"></div>

								<br>

								<!-- Confirm Password -->
								<p class="text-white">Confirm Password: <span style="color: red;">*</span></p>
								<div class="wrap-input100 validate-input" data-validate="Enter password again">
									<input class="input100" type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
								</div>
								<div id="confirm-password-error" class="error-text" style="color:white;font-size:14px;margin-top:-20px;margin-bottom:20px;"></div>

								<!-- Show Password -->
								<div style="margin-top:10px; margin-left:10px; display:flex; gap:10px;">
									<input type="checkbox" id="showPassword">
									<label for="showPassword" class="text-white">Show Password</label>
								</div>
							</div>

							<div class="col-md-4">
								<!-- COR from WMSU Portal -->
								<div class="wrap-input100 validate-input custom-file mt-10">

									<label class="text-white fs-15">Attach the Certificate of Registration (COR) from WMSU Portal <span style="color: red;">*</span></label>
									<br>
									<input class="custom-file-input" type="file" id="cor_1" name="cor_1" accept=" .jpg, .png" required>
									<label class="file-label" for="cor_1">Choose File</label>
									<div id="preview_cor_1" class="mt-2"></div>
									<button type="button" class="btn btn-link text-white p-0 mt-2" data-bs-toggle="modal" data-bs-target="#cor1Modal">
										View sample here
									</button>
								</div>

								<br>

								<!-- COR from Student Affairs -->
								<div class="wrap-input100 validate-input custom-file" style="margin-top: 70%">
									<label class="text-white fs-15">Attach the validated Certificate of Registration (COR) from Student Affairs <span style="color: red;">*</span></label>
									<br>
									<input class="custom-file-input" type="file" id="cor_2" name="cor_2" accept=" .jpg, .png" required>
									<label class="file-label" for="cor_2">Choose File</label>
									<div id="preview_cor_2" class="mt-2"></div>
									<button type="button" class="btn btn-link text-white p-0 mt-2" data-bs-toggle="modal" data-bs-target="#cor2Modal">
										View sample here
									</button>
								</div>
							</div>
						</div>
						<div class="container-login100-form-btn" style="margin-top: 150px;">
							<button class="login100-form-btn" type="submit">
								<i class="bi bi-person-fill-add"></i> Register
							</button>
						</div>
					</form>
			</div>
		</div>
	</div>

	<div class="modal fade" id="cor1Modal" tabindex="-1" aria-labelledby="cor1ModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="cor1ModalLabel">Sample: Certificate of Registration (WMSU Portal)</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body text-center">
					<img src="images/COR - 1 .png" class="img-fluid border">
				</div>
			</div>
		</div>
	</div>
	</div>


	<!-- Modal for COR 2 -->
	<div class="modal fade" id="cor2Modal" tabindex="-1" aria-labelledby="cor2ModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="cor2ModalLabel">Sample: Validated COR (Student Affairs)</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body text-center">
					<img src="images/COR - 2.png" class="img-fluid border">
				</div>
			</div>
		</div>
	</div>

	<script>
		// Student ID validation
		function validateStudentId(studentId) {
			const pattern = /^\d{4}-\d{5}$/;
			return pattern.test(studentId);
		}

		// Email validation
		function validateEmail(email, studentId) {
			// Check if email ends with @wmsu.edu.ph
			if (!email.endsWith('@wmsu.edu.ph')) {
				return false;
			}

			// Extract the numeric part from student ID (2020-12345 → 202012345)
			const studentIdNumeric = studentId.replace('-', '');

			// Check if email starts with the student ID (xt202012345@wmsu.edu.ph)
			const emailPrefix = email.split('@')[0];
			return emailPrefix.endsWith(studentIdNumeric);
		}

		const password = document.getElementById("password");
		const confirmPassword = document.getElementById("confirm_password");
		const showPassword = document.getElementById("showPassword");

		showPassword.addEventListener("change", function() {
			const type = this.checked ? "text" : "password";
			password.type = type;
			confirmPassword.type = type;
		});

		// Form validation
		function validateForm() {
			const studentId = document.getElementById("student_id").value;
			const email = document.getElementById("email").value;
			const password = document.getElementById("password").value;
			const confirmPassword = document.getElementById("confirm_password").value;
			const passwordError = document.getElementById("password-error");
			const emailError = document.getElementById("email-error");

			// Reset errors
			passwordError.textContent = "";
			emailError.textContent = "";

			// Validate Student ID format
			if (!validateStudentId(studentId)) {
				emailError.textContent = "Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)";
				return false;
			}

			// Validate Email matches Student ID
			if (!validateEmail(email, studentId)) {
				emailError.textContent = "Email must be in format xt<StudentID>@wmsu.edu.ph (e.g., xt202012345@wmsu.edu.ph)";
				return false;
			}

			// Password validation
			if (password !== confirmPassword) {
				passwordError.textContent = "Passwords do not match!";
				return false;
			}

			if (password.length < 8) {
				passwordError.textContent = "Password must be at least 8 characters long!";
				return false;
			}

			return true;
		}

		// Add event listeners for real-time validation
		document.getElementById("student_id").addEventListener("input", function() {
			// Automatic formatting: YYYY-XXXXX
			let value = this.value.replace(/[^0-9]/g, '');
			if (value.length > 4) {
				value = value.slice(0, 4) + '-' + value.slice(4, 9);
			}
			this.value = value;

			const studentId = this.value;
			const errorElement = document.getElementById("email-error");

			if (!validateStudentId(studentId)) {
				errorElement.textContent = "Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)";
			} else {
				errorElement.textContent = "";
			}
		});

		document.getElementById("email").addEventListener("input", function() {
			const email = this.value;
			const studentId = document.getElementById("student_id").value;
			const errorElement = document.getElementById("email-error");

			if (studentId && !validateEmail(email, studentId)) {
				errorElement.textContent = "Email must be in format xt<StudentID>@wmsu.edu.ph (e.g., xt202012345@wmsu.edu.ph)";
			} else {
				errorElement.textContent = "";
			}
		});

		document.addEventListener('DOMContentLoaded', function() {
			const fileInputs = [{
					id: 'cor_1',
					previewId: 'preview_cor_1'
				},
				{
					id: 'cor_2',
					previewId: 'preview_cor_2'
				}
			];

			fileInputs.forEach(input => {
				const fileInput = document.getElementById(input.id);
				const preview = document.getElementById(input.previewId);

				if (fileInput && preview) {
					fileInput.addEventListener('change', function() {
						preview.innerHTML = ''; // Clear previous preview
						const file = this.files[0];
						if (file) {
							const fileName = file.name;
							const fileType = file.type;
							const fileSize = (file.size / 1024).toFixed(2); // KB



							if (fileType === 'image/jpeg' || fileType === 'image/png') {
								const img = document.createElement('img');
								img.src = URL.createObjectURL(file);
								img.classList = 'img-fluid img_cor';
								img.style.height = '200px'
								img.style.marginTop = '10px';
								img.style.display = 'block';
								img.style.border = '1px solid #ccc';
								img.style.borderRadius = '4px';
								preview.appendChild(img);
							} else if (fileType === 'application/pdf') {
								const pdfNote = document.createElement('p');
								pdfNote.innerHTML = '<em style="color: #666;">PDF preview not available in browser</em>';
								preview.appendChild(pdfNote);
							} else {
								const note = document.createElement('p');
								note.innerHTML = '<em style="color: #666;">Preview not available for this file type</em>';
								preview.appendChild(note);
							}
						}
					});
				}
			});
		});
	</script>
<?php
				} else {
					echo "<script>window.location.href='register_custom.php';</script>";
					echo '<meta http-equiv="refresh" content="0;url=register_custom.php">';
					exit;
				}
?>


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