<?php
date_default_timezone_set('Asia/Manila');
session_start();

include('includes/conn.php');

// Fetch Academic Data for Dropdowns
$campuses = $pdo->query("SELECT * FROM campuses ORDER BY campus_name")->fetchAll(PDO::FETCH_ASSOC);
$colleges = $pdo->query("SELECT * FROM colleges ORDER BY college_name")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$majors = $pdo->query("SELECT * FROM majors ORDER BY major_name")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name")->fetchAll(PDO::FETCH_ASSOC);
$yearLevels = $pdo->query("SELECT * FROM actual_year_levels ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);



// Separate Campuses
$mainCampuses = [];
$externalCampuses = [];
foreach ($campuses as $campus) {
	if (empty($campus['parent_id'])) {
		$mainCampuses[] = $campus;
	} else {
		$externalCampuses[] = $campus;
	}
}

// Prepare data for JS
$jsColleges = json_encode($colleges);
$jsDepartments = json_encode($departments);
$jsMajors = json_encode($majors);
$jsCourses = json_encode($courses);
$jsExternalCampuses = json_encode($externalCampuses);
$jsYears = json_encode($yearLevels);

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
	$academicYearId    = $electionData['academic_year_id'] ?? 0;

	$customFields = [];
	$numColumns = 1; // Default column count
	if ($voterCustomFields == 1 && $academicYearId) {
		// Fetch custom fields
		$cfSql = "SELECT * FROM voter_custom_fields WHERE academic_year_id = :ay_id AND is_visible = 1 ORDER BY sort_order ASC";
		$cfStmt = $pdo->prepare($cfSql);
		$cfStmt->execute(['ay_id' => $academicYearId]);
		$customFields = $cfStmt->fetchAll(PDO::FETCH_ASSOC);

		// Fetch number of columns for the layout from voter_columns table
		$vcSql = "SELECT number FROM voter_columns WHERE academic_year_id = :ay_id LIMIT 1";
		$vcStmt = $pdo->prepare($vcSql);
		$vcStmt->execute(['ay_id' => $academicYearId]);
		if ($columnData = $vcStmt->fetch(PDO::FETCH_ASSOC)) {
			$numColumns = (int)$columnData['number'] > 0 ? (int)$columnData['number'] : 1;
		}
	}

	// if (!$electionData) {
	// 	throw new Exception("No ongoing election found.");
	// }
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
						margin-top: 0px !important;
						border-radius: 8px !important;
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

				<?php if ($voterCustomFields == 0) { ?>

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

<?php } else { ?>
	<form action="processes/create_account_custom.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
		<div class="row justify-content-center">
			<?php if (!empty($customFields)): ?>
				<?php
						// Calculate the Bootstrap column width based on the number of columns from voter_columns
						$bsColWidth = 12 / $numColumns;
						// Ensure it's an integer and a valid Bootstrap column size (1-12)
						$bsColWidth = max(1, min(12, floor($bsColWidth)));
						$label = '';
						$wrapperId = '';
						if ($label === 'External Campus') {
							$wrapperId = 'external-campus-wrapper';
						} elseif ($label === 'Major') {
							$wrapperId = 'major-wrapper';
						}
				?>
				<?php foreach ($customFields as $field): ?>
					<?php
							$inputType = $field['field_type'];
							$label = $field['field_label'];
							$name = strtolower(str_replace(' ', '_', $label));

							$isRequired = $field['is_required'] ? 'required' : '';
							$validateClass = $field['is_required'] ? 'validate-input' : '';

							// Dynamic wrapper ID for fields that need JS toggling
							$wrapperId = '';
							if ($label === 'External Campus') {
								$wrapperId = 'external-campus-wrapper';
							} elseif ($label === 'Major') {
								$wrapperId = 'major-wrapper';
							}

							// Placeholder logic
							$placeholder = $label;
							$hasSample = false;
							$sampleFile = trim($field['field_sample'] ?? '');
							if (!empty($sampleFile)) {
								$samplePath = __DIR__ . '/../admin/uploads/field_samples/' . $sampleFile;
								if (file_exists($samplePath)) {
									$hasSample = true;
									$sampleExt = strtolower(pathinfo($sampleFile, PATHINFO_EXTENSION));
								} else {
									$placeholder = $sampleFile;
								}
								$sampleUrl = '../admin/uploads/field_samples/' . $sampleFile;
							}
					?>
					<div class="col-md-<?= $bsColWidth ?>" <?= $wrapperId ? 'id="' . $wrapperId . '"' : '' ?>>
						<label class="text-white">
							<?= htmlspecialchars($label) ?><?= $isRequired ? ' <span style="color: red;">*</span>' : '' ?>:
						</label>

						<?php if ($inputType === 'select'): ?>
							<div class="wrap-input100 <?= $validateClass ?>" data-validate="Select <?= htmlspecialchars($label) ?>">
								<select class="input100" name="<?= $name ?>" id="<?= $name ?>" <?= $isRequired ?>>
									<option value="" disabled selected>Select <?= htmlspecialchars($label) ?></option>
									<?php
									// Dynamic population for specific fields
									if ($label === 'College') {
										foreach ($colleges as $college) {
											echo '<option value="' . htmlspecialchars($college['college_id']) . '" data-id="' . $college['college_id'] . '">' . htmlspecialchars($college['college_name']) . '</option>';
										}
									} elseif ($label === 'WMSU Campus') {
										foreach ($mainCampuses as $campus) {
											echo '<option value="' . htmlspecialchars($campus['campus_id']) . '" data-id="' . $campus['campus_id'] . '">' . htmlspecialchars($campus['campus_name']) . '</option>';
										}
									} elseif ($label === 'Year Level') {
										foreach ($yearLevels as $yl) {
											$val = $yl['name'];
											$suffix = 'th';
											if (!in_array(($val % 100), [11, 12, 13])) {
												switch ($val % 10) {
													case 1:
														$suffix = 'st';
														break;
													case 2:
														$suffix = 'nd';
														break;
													case 3:
														$suffix = 'rd';
														break;
												}
											}
											echo '<option value="' . htmlspecialchars($val) . '">' . htmlspecialchars($val . $suffix . " Year") . '</option>';
										}
									} elseif ($label === 'Department' || $label === 'Course' || $label === 'External Campus' || $label === 'Major') {
										echo '<input type="hidden" name="' . $name . '_id" id="' . $name . '_id">';
									} else {
										// Default behavior for other select fields
										$options = explode(',', $field['options']);
										foreach ($options as $option) {
											$opt = trim($option);
											echo '<option value="' . htmlspecialchars($opt) . '">' . htmlspecialchars($opt) . '</option>';
										}
									}
									?>
								</select>
							</div>
						<?php elseif ($inputType === 'file'): ?>
							<?php
								$isCor1 = ($label === 'COR from WMSU Portal');
								$isCor2 = ($label === 'Validated COR from Student Affairs');
								$fileId = $name;
								$accept = '';
								if ($isCor1) {
									$fileId = 'cor_1';
									$name = 'cor_1';
									$accept = 'accept=".pdf, .jpg, .png"';
								} elseif ($isCor2) {
									$fileId = 'cor_2';
									$name = 'cor_2';
									$accept = 'accept=".pdf, .jpg, .png"';
								}
							?>
							<div class="wrap-input100 <?= $validateClass ?> custom-file mt-2" data-validate="Upload <?= htmlspecialchars($label) ?>">

								<!-- File input -->
								<input class="custom-file-input"
									type="file"
									name="<?= $name ?>"
									id="<?= $fileId ?>"
									<?= $isRequired ?>
									<?= $accept ?>
									onchange="handleFileSelectModal(this, 'previewBtn_<?= $fileId ?>')">
								<label class="file-label" for="<?= $fileId ?>">Choose File</label>



								<a id="previewBtn_<?= $fileId ?>"
									type="button" class="btn btn-outline btn-link text-white p-0 mt-2"
									style="display:none; text-decoration: underline;"
									onclick="previewFileModal(document.getElementById('<?= $fileId ?>'))">
									[Preview Uploaded File]
								</a>

								<!-- View Sample buttons -->
								<?php if ($hasSample): ?>
									<button type="button" class="btn btn-link text-white p-0 mt-2" data-bs-toggle="modal" data-bs-target="#sampleModal_<?= $field['id'] ?>">
										View sample here
									</button>
								<?php elseif ($isCor1): ?>
									<button type="button" class="btn btn-link text-white p-0 mt-2" data-bs-toggle="modal" data-bs-target="#cor1Modal">
										View sample here
									</button>
								<?php elseif ($isCor2): ?>
									<button type="button" class="btn btn-link text-white p-0 mt-2" data-bs-toggle="modal" data-bs-target="#cor2Modal">
										View sample here
									</button>
								<?php endif; ?>

							</div>

							<!-- Bootstrap Modal for Preview -->


						<?php elseif ($inputType === 'textarea'): ?>
							<div class="wrap-input100 <?= $validateClass ?>" data-validate="Enter <?= htmlspecialchars($label) ?>">
								<textarea class="input100" name="<?= $name ?>" id="<?= $name ?>" placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $isRequired ?>></textarea>
							</div>
						<?php else: ?>
							<div class="wrap-input100 <?= $validateClass ?>" data-validate="Enter <?= htmlspecialchars($label) ?>">
								<input class="input100" type="<?= htmlspecialchars($inputType) ?>" name="<?= $name ?>" id="<?= $name ?>" placeholder="<?= htmlspecialchars($placeholder) ?>" <?= $isRequired ?>>
							</div>

						<?php endif; ?>

						<?php if (!empty($field['field_description'])): ?>
							<small class="text-white" style="opacity: 0.8;"><?= htmlspecialchars($field['field_description']) ?></small>
						<?php endif; ?>

						<?php if ($hasSample): ?>
						<?php endif; ?>
						<br>
					</div>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="col-12 text-center">
					<p class="text-white">No registration fields configured for this election.</p>
				</div>
			<?php endif; ?>

			<!-- Hidden inputs for election data -->
			<input type="hidden" name="school_year" value="<?= htmlspecialchars($electionData['school_year']) ?>">
			<input type="hidden" name="semester" value="<?= htmlspecialchars($electionData['semester']) ?>">
			<!-- <input type="hidden" name="election_start" value="<?= htmlspecialchars($electionData['start_period']) ?>">
			<input type="hidden" name="election_end" value="<?= htmlspecialchars($electionData['end_period']) ?>"> -->
		</div>

		<div class="container-login100-form-btn d-flex" style="margin-top: 50px; flex-direction: column; align-items: center;">

			<div style="margin-bottom: 15px;">
				<input type="checkbox" id="toggleCheck" onclick="toggleAllPasswords()" style="cursor: pointer;"> Show Passwords

			</div>

			<button class="login100-form-btn" type="submit">
				<i class="bi bi-person-fill-add"></i> Register
			</button>
		</div>
	</form>
<?php } ?>

<!-- Custom Field Sample Modals (Moved outside form to prevent backdrop issues) -->
<?php if (!empty($customFields)): ?>
	<?php foreach ($customFields as $field): ?>
		<?php
		$sampleFile = trim($field['field_sample'] ?? '');
		if (!empty($sampleFile)) {
			$samplePath = __DIR__ . '/../admin/uploads/field_samples/' . $sampleFile;
			if (file_exists($samplePath)) {
				$sampleExt = strtolower(pathinfo($sampleFile, PATHINFO_EXTENSION));
				$sampleUrl = '../admin/uploads/field_samples/' . $sampleFile;
				$label = $field['field_label'];
		?>
				</div>
				</div>
				<div class="modal fade" id="sampleModal_<?= $field['id'] ?>" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title text-dark">Sample: <?= htmlspecialchars($label) ?></h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body text-center">
								<?php if (in_array($sampleExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
									<img src="<?= $sampleUrl ?>" class="img-fluid border" style="max-height: 80vh;">
								<?php elseif ($sampleExt === 'pdf'): ?>
									<iframe src="<?= $sampleUrl ?>" style="width:100%; height:500px;" frameborder="0"></iframe>
								<?php else: ?>
									<p class="text-dark">
										File type not supported for preview.
										<a href="<?= $sampleUrl ?>" target="_blank">Download</a>
									</p>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
		<?php
			}
		}
		?>
	<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content bg-dark text-white">
			<div class="modal-header">
				<h5 class="modal-title" id="filePreviewModalLabel">File Preview</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="modalPreviewContainer" style="text-align:center;">
				<!-- File will appear here -->
			</div>
		</div>
	</div>
</div>

<script>
	function toggleAllPasswords() {
		// Grab both elements by their specific IDs
		const passInput = document.getElementById('password');
		const confirmPassInput = document.getElementById('confirm_password');
		const checkbox = document.getElementById('toggleCheck');

		// Check the state of the checkbox
		if (checkbox.checked) {
			passInput.type = 'text';
			confirmPassInput.type = 'text';
		} else {
			passInput.type = 'password';
			confirmPassInput.type = 'password';
		}
	}
</script>

<script>
	function handleFileSelectModal(input, previewBtnId) {
		const btn = document.getElementById(previewBtnId);
		if (input.files && input.files[0]) {
			btn.style.display = 'inline'; // show the preview link
		} else {
			btn.style.display = 'none'; // hide if no file selected
		}
	}

	// Modal preview function (same as before)
	function previewFileModal(input) {
		const file = input.files[0];
		if (!file) return;

		const container = document.getElementById("modalPreviewContainer");
		container.innerHTML = ""; // clear previous preview

		const reader = new FileReader();
		reader.onload = function(e) {
			const url = e.target.result;
			const type = file.type;

			if (type.startsWith("image/")) {
				container.innerHTML = `<img src="${url}" class="img-fluid rounded" style="max-height:500px;">`;
			} else if (type === "application/pdf") {
				container.innerHTML = `<iframe src="${url}" width="100%" height="500px"></iframe>`;
			} else {
				container.innerHTML = `
                <p>Preview not available for this file type.</p>
                <a href="${url}" download style="color:#0d6efd;">Download File</a>
            `;
			}

			const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
			modal.show();
		};

		reader.readAsDataURL(file);
	}
</script>

<!-- Unified Script for Dynamic Dropdowns (Applies to both Default and Custom forms) -->
<script>
	document.addEventListener('DOMContentLoaded', function() {
		const colleges = <?= $jsColleges ?>;
		const departments = <?= $jsDepartments ?>;
		const majors = <?= $jsMajors ?>;
		const courses = <?= $jsCourses ?>;
		console.log(courses);
		const externalCampuses = <?= $jsExternalCampuses ?>;
		const actual_year_levels = <?= $jsYears ?>;

		const collegeSelect = document.querySelector('select[name="college"]');
		const deptSelect = document.querySelector('select[name="department"]');
		const courseSelect = document.querySelector('select[name="course"]');
		const campusSelect = document.querySelector('select[name="wmsu_campus"]');
		const externalSelect = document.querySelector('select[name="external_campus"]');
		const externalWrapper = document.getElementById('external-campus-wrapper');
		const externalLabel = document.getElementById('external-campus-label');
		const yearSelect = document.querySelector('select[name="year_level"]');

		const majorSelect = document.querySelector('select[name="major"]');



		if (yearSelect) {
			yearSelect.innerHTML = '<option value="" disabled selected>Select Year Level</option>';
		}

		// 1. College -> Department
		if (collegeSelect && deptSelect) {
			collegeSelect.addEventListener('change', function() {
				const selectedOption = this.options[this.selectedIndex];
				const collegeId = selectedOption.getAttribute('data-id');

				// Reset department and course selects
				deptSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';
				if (courseSelect) courseSelect.innerHTML = '<option value="" disabled selected>Select Course</option>';

				if (collegeId) {
					// Filter and populate departments
					const filteredDepts = departments.filter(d => d.college_id == collegeId);
					filteredDepts.forEach(dept => {
						const option = new Option(dept.department_name, dept.department_id); // name as value
						option.setAttribute('data-id', dept.department_id); // id as data-id
						deptSelect.add(option);
					});

					// Filter and populate courses
					if (courseSelect) {
						const filteredCourses = courses.filter(course => course.college_id == collegeId);
						filteredCourses.forEach(course => {
							const option = new Option(course.course_name, course.id); // name as value
							option.setAttribute('data-id', course.id); // id as data-id
							courseSelect.add(option);
						});
					}
				}
			});
		}
		if (courseSelect && majorSelect) {
			courseSelect.addEventListener('change', function() {
				const selectedCourseId = this.options[this.selectedIndex].getAttribute('data-id');

				// Reset majors
				majorSelect.innerHTML = '';

				// Default placeholder
				majorSelect.add(new Option('Select Major', '', true, true));
				majorSelect.options[0].disabled = true;

				// None option
				majorSelect.add(new Option('None', ''));

				if (selectedCourseId) {
					// Filter majors by course_id
					const filteredMajors = majors.filter(m => m.course_id == selectedCourseId);

					filteredMajors.forEach(m => {
						const option = new Option(m.major_name, m.major_id);
						option.setAttribute('data-id', m.major_id);
						majorSelect.add(option);
					});
				}
			});
		}

		// 3. WMSU Campus -> External Campus
		if (campusSelect && externalSelect) {
			campusSelect.addEventListener('change', function() {
				const selectedOption = this.options[this.selectedIndex];
				const campusId = selectedOption.getAttribute('data-id');

				externalSelect.innerHTML = '<option value="" disabled selected>Select External Campus</option>';

				// Check if this campus has children (external campuses)
				const children = externalCampuses.filter(c => c.parent_id == campusId);

				if (children.length > 0) {
					if (externalWrapper) externalWrapper.style.display = 'block';
					if (externalLabel) externalLabel.style.display = 'block';

					children.forEach(camp => {
						const option = new Option(camp.campus_name, camp.campus_id); // name as value
						option.setAttribute('data-id', camp.campus_id); // id as data-id
						externalSelect.add(option);
					});
				} else {
					if (externalWrapper) externalWrapper.style.display = 'none';
					if (externalLabel) externalLabel.style.display = 'none';
				}
			});
		}



		// Function to populate year levels based on course and major
		function populateYearLevels(courseId, majorId) {
			if (!yearSelect) return;

			// Reset dropdown
			yearSelect.innerHTML = '<option value="" disabled selected>Select Year Level</option>';

			if (!courseId) return; // can't filter without course

			const filteredYears = actual_year_levels.filter(y => {
				if (y.course_id != courseId) return false;

				if (majorId) {
					// Major selected → only include year levels for that major
					return y.major_id == majorId;
				} else {
					// No major selected → only include year levels with no major
					return !y.major_id || y.major_id === null || y.major_id === '';
				}
			});

			filteredYears.forEach(y => {
				const option = new Option(y.year_level, y.id); // name as value
				option.setAttribute('data-id', y.id); // id as data-id
				yearSelect.add(option);
			});
		}

		// Example usage:
		// When course changes
		courseSelect.addEventListener('change', function() {
			const selectedCourseId = this.options[this.selectedIndex].getAttribute('data-id');
			const selectedMajorId = majorSelect.options[majorSelect.selectedIndex]?.getAttribute('data-id');

			populateYearLevels(selectedCourseId, selectedMajorId);
		});

		// When major changes
		majorSelect.addEventListener('change', function() {
			const selectedCourseId = courseSelect.options[courseSelect.selectedIndex]?.getAttribute('data-id');
			const selectedMajorId = this.options[this.selectedIndex].getAttribute('data-id');

			populateYearLevels(selectedCourseId, selectedMajorId);
		});
	});

	const wmsuCampusSelect = document.getElementById('wmsu_campus');
	const externalWrapper = document.getElementById('external-campus-wrapper');

	if (wmsuCampusSelect && externalWrapper) {
		externalWrapper.style.display = 'none'; // hide initially

		wmsuCampusSelect.addEventListener('change', function() {
			const selectedText = this.options[this.selectedIndex].text;

			if (selectedText === 'WMSU ESU') {
				externalWrapper.style.display = 'block';
			} else {
				externalWrapper.style.display = 'none';
			}
		});
	}
</script>

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

	// Form validation
	function validateForm() {
		const studentIdEl = document.getElementById("student_id");
		const emailEl = document.getElementById("email");
		const passwordEl = document.getElementById("password");
		const confirmPasswordEl = document.getElementById("confirm_password");

		// If elements don't exist (e.g. custom form without these fields), skip validation or handle accordingly
		if (!studentIdEl || !emailEl) return true;

		const studentId = studentIdEl.value;
		const email = emailEl.value;
		const password = passwordEl ? passwordEl.value : '';
		const confirmPassword = confirmPasswordEl ? confirmPasswordEl.value : '';

		const passwordError = document.getElementById("password-error");
		const emailError = document.getElementById("email-error");

		// Reset errors
		if (passwordError) passwordError.textContent = "";
		if (emailError) emailError.textContent = "";

		// Validate Student ID format
		if (!validateStudentId(studentId)) {
			if (emailError) emailError.textContent = "Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)";
			return false;
		}

		// Validate Email matches Student ID
		if (!validateEmail(email, studentId)) {
			if (emailError) emailError.textContent = "Email must be in format xt<StudentID>@wmsu.edu.ph (e.g., xt202012345@wmsu.edu.ph)";
			return false;
		}

		// Password validation
		if (passwordEl && confirmPasswordEl) {
			if (password !== confirmPassword) {
				if (passwordError) passwordError.textContent = "Passwords do not match!";
				return false;
			}

			if (password.length < 8) {
				if (passwordError) passwordError.textContent = "Password must be at least 8 characters long!";
				return false;
			}
		}

		return true;
	}

	// Add event listeners for real-time validation
	document.addEventListener('DOMContentLoaded', function() {


		function showCorModal(modalId) {
			const myModal = new bootstrap.Modal(document.getElementById(modalId));
			myModal.show();
		}

		// Attach click event listeners to buttons
		const cor1Button = document.getElementById('cor1Button');
		if (cor1Button) {
			cor1Button.addEventListener('click', function() {
				showCorModal('cor1Modal');
			});
		}

		const cor2Button = document.getElementById('cor2Button');
		if (cor2Button) {
			cor2Button.addEventListener('click', function() {
				showCorModal('cor2Modal');
			});
		}




		const studentIdEl = document.getElementById("student_id");
		const emailEl = document.getElementById("email");

		if (studentIdEl) {
			studentIdEl.addEventListener("input", function() {
				// Automatic formatting: YYYY-XXXXX
				let value = this.value.replace(/[^0-9]/g, '');
				if (value.length > 4) {
					value = value.slice(0, 4) + '-' + value.slice(4, 9);
				}
				this.value = value;

				const studentId = this.value;
				const errorElement = document.getElementById("email-error");

				if (errorElement) {
					if (!validateStudentId(studentId)) {
						errorElement.textContent = "Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)";
					} else {
						errorElement.textContent = "";
					}
				}
			});
		}

		if (emailEl) {

			emailEl.addEventListener("input", function() {

				const email = this.value;
				const studentId = document.getElementById("student_id") ? document.getElementById("student_id").value : '';
				const errorElement = document.getElementById("email-error");

				if (errorElement) {
					if (studentId && !validateEmail(email, studentId)) {
						errorElement.textContent = "Email must be in format xt<StudentID>@wmsu.edu.ph (e.g., xt202012345@wmsu.edu.ph)";
					} else {
						errorElement.textContent = "";
					}
				}
			});
		}


		// File input previews
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


			if (!fileInput) {
				console.warn(`File input with ID "${input.id}" not found.`);
				return; // Skip to the next iteration
			}

			const preview = document.getElementById(input.previewId);

			if (!preview) {
				console.warn(`Preview element  with ID "${input.id}" not found.`);
				return; // Skip to the next iteration

			}


			if (!fileInput) {

				console.warn(`File input or preview element with ID "${input.id}" or "${input.previewId}" not found.`);
				return; // Skip to the next iteration

			}

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
</script>


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

<!-- <script>
	document.querySelector('form').addEventListener('submit', function(e) {
		// College
		if (collegeSelect && document.getElementById('college_id')) {
			const selected = collegeSelect.options[collegeSelect.selectedIndex];
			document.getElementById('college_id').value = selected?.getAttribute('data-id') || '';
			console.log('college_id:', document.getElementById('college_id').value);
		}

		// Department
		if (deptSelect && document.getElementById('department_id')) {
			const selected = deptSelect.options[deptSelect.selectedIndex];
			document.getElementById('department_id').value = selected?.getAttribute('data-id') || '';
			console.log('department_id:', document.getElementById('department_id').value);
		}

		// Course
		if (courseSelect && document.getElementById('course_id')) {
			const selected = courseSelect.options[courseSelect.selectedIndex];
			document.getElementById('course_id').value = selected?.getAttribute('data-id') || '';
			console.log('course_id:', document.getElementById('course_id').value);
		}

		// Major
		if (majorSelect && document.getElementById('major_id')) {
			const selected = majorSelect.options[majorSelect.selectedIndex];
			document.getElementById('major_id').value = selected?.getAttribute('data-id') || '';
			console.log('major_id:', document.getElementById('major_id').value);
		}

		// WMSU Campus
		if (campusSelect && document.getElementById('wmsu_campus_id')) {
			const selected = campusSelect.options[campusSelect.selectedIndex];
			document.getElementById('wmsu_campus_id').value = selected?.getAttribute('data-id') || '';
			console.log('wmsu_campus_id:', document.getElementById('wmsu_campus_id').value);
		}

		// External Campus
		if (externalSelect && document.getElementById('external_campus_id')) {
			const selected = externalSelect.options[externalSelect.selectedIndex];
			document.getElementById('external_campus_id').value = selected?.getAttribute('data-id') || '';
			console.log('external_campus_id:', document.getElementById('external_campus_id').value);
		}

		// Year Level
		if (yearSelect && document.getElementById('year_level_id')) {
			const selected = yearSelect.options[yearSelect.selectedIndex];
			document.getElementById('year_level_id').value = selected?.getAttribute('data-id') || '';
			console.log('year_level_id:', document.getElementById('year_level_id').value);
		}

		// Return true to allow form submit
		return true;
	});
</script> -->