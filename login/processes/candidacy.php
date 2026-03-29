<?php
date_default_timezone_set('Asia/Manila');
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Event Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<style>
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
        width: 100%;
        cursor: pointer;
    }

    .custom-file-input {
        display: none;
    }

    .file-label {
        background-color: #4CAF50;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .file-label:hover {
        background-color: #45a049;
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
        color: black !important;
    }

    select {
        color: black !important;
    }

    .file-preview {
        display: block;
    }

    /* .input100:active {
        border-bottom: 1px solid black !important;
    } */

    .custom-searchable-select {
        position: relative;
        width: 100%;
    }

    .search-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-bottom: 5px;
        box-sizing: border-box;
    }

    .custom-searchable {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        min-height: 38px;
        cursor: text;
    }

    .custom-searchable.placeholder {
        color: #999;
    }

    .custom-searchable:focus {
        outline: none;
        border-color: #666;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    }

    .hidden-select {
        display: none;
    }

    .dropdown-options {
        display: none;
        background: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
    }

    .dropdown-options div {
        padding: 8px 10px;
        cursor: pointer;
    }

    .dropdown-options div:hover {
        background: #f0f0f0;
    }
</style>

<body>
    <div class="limiter">
        <div class="container-login100" style="background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');">
            <?php
            include('../includes/conn.php');
            $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
            $canRegister = false;

            try {
                // Validate event_id
                if ($event_id <= 0) {
                    throw new Exception("Invalid event ID");
                }

                // Fetch event details to check registration status and get candidacy
                $event_stmt = $pdo->prepare("
                    SELECT candidacy, registration_deadline 
                    FROM events 
                    WHERE id = ? AND status = 'published' AND registration_enabled = 1
                ");
                $event_stmt->execute([$event_id]);
                $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$event || empty($event['candidacy'])) {
                    throw new Exception("Event not found, inactive, or registration not enabled");
                }

                // Check if registration is still open
                $current_date = new DateTime();
                $registration_deadline = new DateTime($event['registration_deadline']);
                $canRegister = $current_date <= $registration_deadline;

                if ($canRegister) {
                    $candidacy = $event['candidacy'];

                    // Fetch form_id from registration_forms using election_name (candidacy)
                    $form_stmt = $pdo->prepare("
                        SELECT id, form_name 
                        FROM registration_forms 
                        WHERE election_name = ? AND status = 'active'
                    ");
                    $form_stmt->execute([$candidacy]);
                    $form = $form_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$form) {
                        throw new Exception("No active registration form found for this candidacy");
                    }

                    $form_id = $form['id'];
                    $form_name = $form['form_name'];

                    // Fetch fields
                    $fields_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
                    $fields_stmt->execute([$form_id]);
                    $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Fetch parties for this election
                    $party_stmt = $pdo->prepare("
                        SELECT name 
                        FROM parties 
                        WHERE election_name = ? AND status = 'approved' 
                        ORDER BY name
                    ");
                    $party_stmt->execute([$candidacy]);
                    $parties = $party_stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            ?>

            <div class="container-fluid mt-5 wrap-login100" style="width: 1000px">
                <a href="#" class="g-back" onclick="goBack()">
                    <i class="bi bi-arrow-left"></i> &nbsp; Go back
                </a>

                <script>
                    function goBack() {
                        if (document.referrer) {
                            window.location.href = '../index.php';
                        } else {
                            window.location.href = '../index.php';
                        }
                    }
                </script>

                <br>
                <h2 class="text-center text-light m-2 mb-5">Event Registration</h2>

                <?php if ($canRegister):

                    $stmt = $pdo->prepare("
                   SELECT id, 
       student_id,
     TRIM(CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name)) AS full_name
FROM voters

                ");
                    $stmt->execute();
                    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $stmt = $pdo->query("SELECT DATABASE()");
                    $currentDb = $stmt->fetchColumn();
                   


                ?>

                    <div class="card mx-auto">
                        <div class="card-body p-4">
                            <h4 class="text-center mb-4"><?php echo htmlspecialchars($form_name); ?></h4>
                            <?php if ($canRegister): ?>
                                <form method="POST" id="registrationForm" enctype="multipart/form-data">
    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
    <input type="hidden" id="lat" name="lat">
    <input type="hidden" id="lon" name="lon">

    <span>Note: File types only include PDFs, DOCXs, JPGs, and PNGs</span>

    <?php foreach ($fields as $field): ?>
        <div class="field-container">
            <label class="p-b-10 <?php echo $field['is_required'] ? 'required' : ''; ?>">
                <?php
                if ($field['field_name'] === 'full_name') {
                    echo 'Full Name';
                } elseif ($field['field_name'] === 'student_id') {
                    echo 'Student ID';
                } else {
                    echo ucfirst(htmlspecialchars($field['field_name']));
                }
                ?>
            </label>

            <?php switch ($field['field_name']):
                case 'full_name': ?>
                    <div class="wrap-input100 validate-input">
                        <div class="custom-searchable" contenteditable="true" id="search_select" data-placeholder="Select Full Name"></div>
                        <select class="input100 hidden-select" name="fields[<?php echo $field['id']; ?>]" id="full_name_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <option value="">Select Full Name</option>
                            <?php foreach ($voters as $voter): ?>
                                <option value="<?php echo htmlspecialchars($voter['full_name']); ?>" data-student-id="<?php echo htmlspecialchars($voter['student_id']); ?>">
                                    <?php echo htmlspecialchars($voter['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php break;

                case 'student_id': ?>
                    <div class="wrap-input100 validate-input" style="border: 1px solid lightgray; border-radius: 4px;">
                        <input class="input100" type="text" name="fields[<?php echo $field['id']; ?>]" id="student_id_field" readonly>
                    </div>
                <?php break;

                case 'party': ?>
                    <div class="wrap-input100 validate-input">
                        <select class="input100" name="fields[<?php echo $field['id']; ?>]" id="party_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <option value="">Select Party</option>
                            <?php foreach ($parties as $party): ?>
                                <option value="<?php echo htmlspecialchars($party['name']); ?>">
                                    <?php echo htmlspecialchars($party['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php break;

                case 'position': ?>
                    <div class="wrap-input100 validate-input">
                        <select class="input100" name="fields[<?php echo $field['id']; ?>]" id="position_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <option value="">Select Party First</option>
                        </select>
                    </div>
                <?php break;

                case 'picture': ?>
                    <div class="wrap-input100 validate-input custom-file mt-10">
                        <input class="custom-file-input" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".jpg, .jpeg, .png" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <label class="file-label" for="field_<?php echo $field['id']; ?>">Choose Profile Picture</label>
                    </div>
                    <div class="file-preview" id="preview_<?php echo $field['id']; ?>" style="margin: 10px;"></div>
                <?php break;

                default:
                    switch ($field['field_type']):
                        case 'text': ?>
                            <div class="wrap-input100 validate-input">
                                <input class="input100" type="text" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?> maxlength="255">
                            </div>
                        <?php break;

                        case 'textarea': ?>
                            <div class="wrap-input100 validate-input">
                                <textarea class="input100" style="height: 100px;" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?>></textarea>
                            </div>
                        <?php break;

                        case 'dropdown': ?>
                            <div class="wrap-input100 validate-input">
                                <select class="input100" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                    <option value="">Select an option</option>
                                    <?php $options = $field['options'] ? explode(',', $field['options']) : [];
                                    foreach ($options as $option):
                                        $option = trim($option);
                                        if (!empty($option)): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>">
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php break;

                        case 'checkbox': ?>
                            <div class="wrap-input100 validate-input">
                                <input type="checkbox" name="fields[<?php echo $field['id']; ?>]" value="1">
                                <label><?php echo htmlspecialchars($field['field_name']); ?></label>
                            </div>
                        <?php break;

                        case 'radio': ?>
                            <div class="wrap-input100 validate-input">
                                <?php $options = $field['options'] ? explode(',', $field['options']) : [];
                                foreach ($options as $option):
                                    $option = trim($option);
                                    if (!empty($option)): ?>
                                        <label style="margin-right: 15px;">
                                            <input type="radio" name="fields[<?php echo $field['id']; ?>]" value="<?php echo htmlspecialchars($option); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php break;

                        case 'file': ?>
                            <div class="wrap-input100 validate-input custom-file mt-10">
                                <input class="custom-file-input" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".pdf, .docx, .jpg, .png" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <label class="file-label" for="field_<?php echo $field['id']; ?>">Choose File</label>
                            </div>
                            <div class="file-preview" id="preview_<?php echo $field['id']; ?>" style="margin: 10px;"></div>
                        <?php break;
                    endswitch;
                    break;
            endswitch; ?>

            <?php if (!empty($field['template_path'])): ?>
                <div class="mt-2">
                    <label class="mb-3" style="font-style: italic; font-size: 12px;">Template Provided to Follow:</label>
                    <a href="../uploads/templates/<?php echo htmlspecialchars($field['template_path']); ?>" class="btn btn-sm btn-outline-primary" download target="_blank">
                        <i class="bi bi-download"></i> Download Template
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="login100-form-btn w-100">Submit Registration</button>
</form>
                            <?php else: ?>
                                <p class="text-center text-danger"><?php echo htmlspecialchars($error_message ?? 'Registration is closed or not available.'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>



                <?php else: ?>
                    <p class="alert alert-danger text-center"><?php echo isset($error_message) ? htmlspecialchars($error_message) : "Registration is closed for this event."; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>




    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($fields as $field): ?>
                <?php if ($field['field_type'] === 'file'): ?>
                    const fileInput_<?php echo $field['id']; ?> = document.getElementById('field_<?php echo $field['id']; ?>');
                    const preview_<?php echo $field['id']; ?> = document.getElementById('preview_<?php echo $field['id']; ?>');

                    fileInput_<?php echo $field['id']; ?>.addEventListener('change', function() {
                        preview_<?php echo $field['id']; ?>.innerHTML = ''; // Clear previous preview
                        const file = this.files[0];
                        if (file) {
                            const fileName = file.name;
                            const fileType = file.type;
                            const fileSize = (file.size / 1024).toFixed(2); // Size in KB

                            // Display file details
                            const details = `
                        <p style="margin: 5px 0;"><strong>File:</strong> ${fileName}</p>
                        <p style="margin: 5px 0;"><strong>Type:</strong> ${fileType}</p>
                        <p style="margin: 5px 0;"><strong>Size:</strong> ${fileSize} KB</p>
                    `;
                            preview_<?php echo $field['id']; ?>.innerHTML = details;

                            // Preview for images
                            if (fileType === 'image/jpeg' || fileType === 'image/png') {
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                img.style.maxWidth = '200px';
                                img.style.marginTop = '10px';
                                img.style.display = 'block';
                                img.style.border = '1px solid #ccc';
                                img.style.borderRadius = '4px';
                                preview_<?php echo $field['id']; ?>.appendChild(img);
                            } else if (fileType === 'application/pdf') {
                                const pdfLink = document.createElement('p');
                                pdfLink.innerHTML = '<em style="color: #666;">PDF preview not available in browser</em>';
                                pdfLink.style.margin = '5px 0';
                                preview_<?php echo $field['id']; ?>.appendChild(pdfLink);
                            } else if (fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                const docxNote = document.createElement('p');
                                docxNote.innerHTML = '<em style="color: #666;">DOCX preview not available in browser</em>';
                                docxNote.style.margin = '5px 0';
                                preview_<?php echo $field['id']; ?>.appendChild(docxNote);
                            }
                        }
                    });
                <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchSelect = document.getElementById('search_select');
            const fullNameSelect = document.getElementById('full_name_select');
            const studentIdField = document.getElementById('student_id_field');
            let allOptions = Array.from(fullNameSelect.options);
            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown-options';

            // Store original options
            const originalOptions = allOptions.map(option => ({
                value: option.value,
                text: option.text,
                studentId: option.getAttribute('data-student-id')
            }));

            // Set placeholder
            searchSelect.textContent = searchSelect.getAttribute('data-placeholder');
            searchSelect.classList.add('placeholder');

            // Create dropdown
            document.body.appendChild(dropdown);
            let isDropdownVisible = false;

            // Position dropdown
            function positionDropdown() {
                const rect = searchSelect.getBoundingClientRect();
                dropdown.style.position = 'absolute';
                dropdown.style.top = `${rect.bottom + window.scrollY}px`;
                dropdown.style.left = `${rect.left + window.scrollX}px`;
                dropdown.style.width = `${rect.width}px`;
            }

            // Show/hide dropdown
            function updateDropdown(options) {
                dropdown.innerHTML = '';
                options.forEach(opt => {
                    const div = document.createElement('div');
                    div.textContent = opt.text;
                    div.dataset.value = opt.value;
                    div.dataset.studentId = opt.studentId;
                    div.addEventListener('click', () => {
                        searchSelect.textContent = opt.text;
                        searchSelect.classList.remove('placeholder');
                        fullNameSelect.value = opt.value;
                        if (studentIdField) studentIdField.value = opt.studentId || '';
                        hideDropdown();
                    });
                    dropdown.appendChild(div);
                });
                positionDropdown();
                dropdown.style.display = 'block';
                isDropdownVisible = true;
            }

            function hideDropdown() {
                dropdown.style.display = 'none';
                isDropdownVisible = false;
            }

            // Handle input
            searchSelect.addEventListener('input', function() {
                this.classList.remove('placeholder');
                const searchTerm = this.textContent.toLowerCase();
                const filteredOptions = originalOptions.filter(option =>
                    option.text.toLowerCase().includes(searchTerm)
                );
                updateDropdown(filteredOptions);
            });

            // Show all options on focus
            searchSelect.addEventListener('focus', function() {
                if (this.textContent === this.getAttribute('data-placeholder')) {
                    this.textContent = '';
                }
                updateDropdown(originalOptions);
            });

            // Handle blur
            searchSelect.addEventListener('blur', function() {
                setTimeout(() => {
                    if (!this.textContent.trim() || !fullNameSelect.value) {
                        this.textContent = this.getAttribute('data-placeholder');
                        this.classList.add('placeholder');
                    }
                    hideDropdown();
                }, 100);
            });

            // Handle select change
            if (fullNameSelect && studentIdField) {
                fullNameSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const studentId = selectedOption.getAttribute('data-student-id');
                    studentIdField.value = studentId || '';
                    searchSelect.textContent = selectedOption.text;
                    searchSelect.classList.remove('placeholder');
                });
            }

            // Keyboard navigation
            searchSelect.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && isDropdownVisible) {
                    e.preventDefault();
                    const firstOption = dropdown.querySelector('div');
                    if (firstOption) firstOption.click();
                }
            });
        });
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
        $(document).ready(function() {
            // Update position options based on party selection
            $('#party_select').change(function() {
                const partyName = $(this).val();
                const electionName = '<?php echo isset($candidacy) ? addslashes($candidacy) : ''; ?>';
                if (partyName && electionName) {
                    $.ajax({
                        url: 'processes/get_positions.php',
                        method: 'POST',
                        data: {
                            election_name: electionName,
                            party_name: partyName
                        },
                        success: function(response) {
                            $('#position_select').html(response);
                        },
                        error: function() {
                            $('#position_select').html('<option value="">Error loading positions</option>');
                        }
                    });
                } else {
                    $('#position_select').html('<option value="">Select Party First</option>');
                }
            });


        });
    </script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const formId = <?php echo $form_id; ?>;
    const eventId = <?php echo $event_id; ?>;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Detect if the user is on a mobile device
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        if (isMobile && navigator.geolocation) {
            // Prompt for geolocation on mobile devices
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    document.getElementById('lat').value = lat;
                    document.getElementById('lon').value = lon;

                    form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=${lat}&lon=${lon}`;
                    form.submit();
                },
                function(error) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Geolocation Error',
                        text: 'Could not get your location. Proceeding without it.',
                        showConfirmButton: true
                    }).then(() => {
                        form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0`;
                        form.submit();
                    });
                }, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            // Proceed without geolocation for non-mobile devices or if geolocation is unsupported
            if (!navigator.geolocation) {
                console.warn('Geolocation is not supported by this browser.');
            }
            form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0 `;
            form.submit();
        }
    });
});
</script>

    <?php
    if (isset($_SESSION['STATUS'])) {
        switch ($_SESSION['STATUS']) {
            case 'SUCCESS_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'Your candidacy has been successfully registered!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Candidacy Error',
                        text: 'There was an error while registering your candidacy. Please try again!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_PARTY_POSITION_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidacy Registration Error!',
                            text: 'The party and position has already been taken. Please try again!',
                            showConfirmButton: true
                        });
                    </script>";
                break;



            case 'ERROR_CANDIDACY_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidate already exists!',
                            text: 'There was an error while registering your candidacy for it already exists!',
                            showConfirmButton: true
                        });
                    </script>";
                break;


            case 'LOGOUT_SUCCESSFUL':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have successfully logged out!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>";
                break;
        }
        unset($_SESSION['STATUS']);
    }
    ?>
</body>

</html>