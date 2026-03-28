<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

// Fetch Academic Years
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC");
$academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine Selected AY
$selected_ay_id = isset($_GET['ay_id']) ? $_GET['ay_id'] : null;
$selected_ay = null;

if ($selected_ay_id) {
    foreach ($academic_years as $ay) {
        if ($ay['id'] == $selected_ay_id) {
            $selected_ay = $ay;
            break;
        }
    }
} else {
    // Default to Ongoing
    foreach ($academic_years as $ay) {
        if ($ay['status'] == 'Ongoing') {
            $selected_ay = $ay;
            $selected_ay_id = $ay['id'];
            break;
        }
    }
    // If no ongoing, take the first one
    if (!$selected_ay && !empty($academic_years)) {
        $selected_ay = $academic_years[0];
        $selected_ay_id = $selected_ay['id'];
    }
}

if ($selected_ay_id) {
    $academicYearId = $selected_ay_id;
}

// Check and insert default fields if necessary
// if ($selected_ay_id) {
//     $academicYearId = $selected_ay_id;

//     $checkSql = "SELECT COUNT(*) FROM voter_custom_fields WHERE academic_year_id = ?";
//     $checkStmt = $pdo->prepare($checkSql);
//     $checkStmt->execute([$academicYearId]);
//     $fieldCount = $checkStmt->fetchColumn();

//     if ($fieldCount == 0) {
//         $defaultFields = [
//             ['Student ID', 'text', 1, 'e.g. 2021-00001', 'Format: YYYY-XXXXX', 1, 1, 3, 1],
//             ['First Name', 'text', 1, 'First Name', '', 2, 1, 3, 1],
//             ['Middle Name', 'text', 0, 'Middle Name', '', 3, 1, 3, 1],
//             ['Last Name', 'text', 1, 'Last Name', '', 4, 1, 3, 1],
//             ['Year Level', 'select', 1, '', '', 5, 1, 3, 1],
//             ['College', 'select', 1, '', '', 6, 1, 3, 1],
//             ['Course', 'select', 1, '', '', 7, 1, 3, 1],
//             ['Department', 'select', 1, '', '', 8, 1, 3, 1],
//             ['WMSU Campus', 'select', 1, '', '', 9, 1, 3, 1],
//             ['External Campus', 'select', 0, '', '', 10, 1, 3, 1],
//             [
//                 'Email',
//                 'email',
//                 1,
//                 'Enter your WMSU Email (e.g., xt202012345@wmsu.edu.ph)',
//                 'Format: xt<StudentID>@wmsu.edu.ph',
//                 11,
//                 1,
//                 3,
//                 1
//             ],

//             ['Password', 'password', 1, 'Password', '', 12, 1, 3, 1],

//             ['Confirm Password', 'password', 1, 'Confirm Password', '', 13, 1, 3, 1],

//             [
//                 'Certificate of Registration (COR) - Portal',
//                 'file',
//                 1,
//                 '',
//                 'Accepted formats: PDF, JPG, PNG',
//                 14,
//                 1,
//                 4,
//                 1
//             ],

//             [
//                 'Certificate of Registration (COR) - Student Affairs',
//                 'file',
//                 1,
//                 '',
//                 'Accepted formats: PDF, JPG, PNG',
//                 15,
//                 1,
//                 4,
//                 1
//             ],
//         ];

//         $insertSql = "INSERT INTO voter_custom_fields
//             (academic_year_id, field_label, field_type, is_required, field_sample,
//              field_description, sort_order, is_visible, column_width, order_row)
//             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

//         $insertStmt = $pdo->prepare($insertSql);

//         foreach ($defaultFields as $index => $field) {
//             $insertStmt->execute(array_merge([$academicYearId], $field));
//         }
//     }
// }

// Fetch Custom Fields
$custom_fields = [];
if ($selected_ay) {
    $stmt = $pdo->prepare("SELECT * FROM voter_custom_fields WHERE academic_year_id = ?");
    $stmt->execute([$selected_ay_id]);
    $custom_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT status FROM academic_years WHERE id = ?");
    $stmt->execute([$selected_ay_id]);
    $ayStatus = $stmt->fetch(PDO::FETCH_ASSOC);

    $workingStatus = $ayStatus['status'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Custom Fields</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.17.0/Sortable.min.js"></script>
</head>

<body>
    <div class="container-scroller">
        <!-- Navbar -->
        <?php
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email 
                               FROM admin a 
                               JOIN users u ON a.user_id = u.id 
                               WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.php">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Admin</span></h1>
                        <h6>
                            <?php
                            // Join with academic_years to get semester and year_label
                            $stmt = $pdo->prepare("
                SELECT e.id, e.election_name, e.academic_year_id, a.year_label, a.semester
                FROM elections e
                JOIN academic_years a ON e.academic_year_id = a.id
                WHERE e.status = :status
                ORDER BY a.year_label DESC, a.semester DESC
            ");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($ongoingElections) {
                                // Show first election
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> " . htmlspecialchars($first['semester']) . " | ";
                                echo "<b>Election:</b> " . htmlspecialchars($first['election_name']) . "<br>";

                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none; margin-top:5px;">';

                                    foreach ($ongoingElections as $election) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($election['year_label']) . " | ";
                                        echo "<b>Semester:</b> " . htmlspecialchars($election['semester']) . " | ";
                                        echo "<b>Election:</b> " . htmlspecialchars($election['election_name']) . "<br>";
                                    }

                                    echo '</div><br>';
                                    echo '<a href="javascript:void(0)" id="toggleElections" class="text-decoration-underline text-white">Show More</a>';
                                }
                            }
                            ?>
                        </h6>

                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const toggleBtn = document.getElementById("toggleElections");
                                const moreDiv = document.getElementById("moreElections");

                                if (toggleBtn) {
                                    toggleBtn.addEventListener("click", function() {
                                        if (moreDiv.style.display === "none") {
                                            moreDiv.style.display = "block";
                                            toggleBtn.textContent = "Show Less";
                                        } else {
                                            moreDiv.style.display = "none";
                                            toggleBtn.textContent = "Show More";
                                        }
                                    });
                                }

                                // Back to Top Button
                                const backToTopButton = document.getElementById('backToTop');
                                if (backToTopButton) {
                                    window.addEventListener('scroll', function() {
                                        if (window.pageYOffset > 200) {
                                            backToTopButton.classList.add('show');
                                        } else {
                                            backToTopButton.classList.remove('show');
                                        }
                                    });

                                    backToTopButton.addEventListener('click', function() {
                                        window.scrollTo({
                                            top: 0,
                                            behavior: 'smooth'
                                        });
                                    });
                                }
                            });
                        </script>
                    </li>
                </ul>


                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>

        </nav>

        <div class="container-fluid page-body-wrapper">
            <!-- Sidebar -->

            <?php include('includes/sidebar.php') ?>

            <!-- Main Content -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Voter Registration Custom Fields</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <h4 class="card-title mb-0">Manage Custom Fields</h4>
                                                    <form method="GET" class="d-flex align-items-center">
                                                        <label class="me-2 mb-0">Academic Year:</label>
                                                        <select name="ay_id" class="form-select" onchange="this.form.submit()">
                                                            <?php foreach ($academic_years as $ay): ?>
                                                                <option value="<?= $ay['id'] ?>" <?= ($selected_ay_id == $ay['id']) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($ay['year_label'] . ' - ' . $ay['semester']) ?>
                                                                    <?= ($ay['status'] == 'Ongoing') ? '(Ongoing)' : '' ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                </div>

                                                <?php if ($selected_ay): ?>
                                                    <?php if ($selected_ay['custom_voter_option'] == 1): ?>
                                                        <div class="alert alert-info">
                                                            Custom voter fields are <strong>enabled</strong> for this academic year. You can add extra fields for voter registration below.
                                                        </div>

                                                        <?php $disabled = ($workingStatus === 'archived') ? 'disabled' : ''; ?>

                                                        <button class="btn btn-primary text-white mb-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addFieldModal"
                                                            <?= $disabled ?>>
                                                            <i class="mdi mdi-plus"></i> Add Custom Field
                                                        </button>

                                                        <button class="btn btn-danger text-white mb-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#resetFieldsModal"
                                                            <?= $disabled ?>>
                                                            <i class="mdi mdi-trash-can"></i> Reset Custom Fields
                                                        </button>

                                                        <div class="modal fade" id="resetFieldsModal" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title">Confirm Reset</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>

                                                                    <div class="modal-body">
                                                                        <p>
                                                                            This will permanently delete ALL custom fields for this academic year.
                                                                        </p>
                                                                        <p class="text-danger fw-bold">
                                                                            This action cannot be undone.
                                                                        </p>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <form action="processes/reset_custom_fields.php" method="POST">
                                                                            <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($academicYearId) ?>">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" class="btn btn-danger">
                                                                                Yes, Reset Fields
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table class="table table-striped table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Label</th>
                                                                        <th>Type</th>
                                                                        <th>Required</th>
                                                                        <th>Options (for Dropdown/Radio)</th>
                                                                        <th>Description</th>
                                                                        <th>Sample</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (empty($custom_fields)): ?>
                                                                        <tr>
                                                                            <td colspan="7" class="text-center">No custom fields added yet.</td>
                                                                        </tr>
                                                                    <?php else: ?>
                                                                        <?php foreach ($custom_fields as $field): ?>
                                                                            <tr>
                                                                                <td><?= htmlspecialchars($field['field_label']) ?></td>
                                                                                <td><?= htmlspecialchars(ucfirst($field['field_type'])) ?></td>
                                                                                <td><?= $field['is_required'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>' ?></td>
                                                                                <td>
                                                                                    <?php if (in_array($field['field_type'], ['dropdown', 'radio'])): ?>
                                                                                        <?= htmlspecialchars($field['options'] ?? '') ?>
                                                                                    <?php else: ?>
                                                                                        <span class="text-muted">N/A</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td><?= htmlspecialchars($field['field_description'] ?? '-') ?></td>
                                                                                <td>
                                                                                    <?php if (!empty($field['field_sample'])): ?>
                                                                                        <a href="uploads/field_samples/<?= htmlspecialchars($field['field_sample']) ?>" target="_blank" class="btn btn-sm btn-info text-white">View</a>
                                                                                    <?php else: ?>
                                                                                        <span class="text-muted">None</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php $disabled = ($workingStatus === 'archived') ? 'disabled' : ''; ?>

                                                                                    <form action="delete_field.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this field?');">
                                                                                        <input type="hidden" name="id" value="<?= $field['id'] ?>">
                                                                                        <input type="hidden" name="academic_year_id" value="<?= $selected_ay_id ?>">
                                                                                        <button type="submit" class="btn btn-danger btn-sm text-white" <?= $disabled ?>>
                                                                                            <i class="mdi mdi-delete"></i> Delete
                                                                                        </button>
                                                                                    </form>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <i class="mdi mdi-alert-circle-outline"></i>
                                                            Custom voter fields are <strong>disabled</strong> for this academic year (<?= htmlspecialchars($selected_ay['year_label']) ?>).

                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="alert alert-danger">No academic year found.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content-wrapper ends -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>

    <!-- Add Field Modal -->
    <?php
    $defaultFields = [
        ['Student ID', 'text', 1, 'e.g. 2021-00001', 'Format: YYYY-XXXXX', 1],
        ['First Name', 'text', 1, 'First Name', '', 2],
        ['Middle Name', 'text', 0, 'Middle Name', '', 3],
        ['Last Name', 'text', 1, 'Last Name', '', 4],
        ['College', 'select', 1, '', '', 6],
        ['Course', 'select', 1, '', '', 7],
        ['Department', 'select', 1, '', '', 8],
        ['Major', 'select', 0, '', '', 8],
        ['Year Level', 'select', 1, '', '', 5],
        ['WMSU Campus', 'select', 1, '', '', 9],
        ['External Campus', 'select', 0, '', '', 10],
        ['Email', 'email', 1, 'Enter your WMSU Email', '', 11],
        ['Password', 'password', 1, 'Password', '', 12],
        ['Confirm Password', 'password', 1, 'Confirm Password', '', 13],
        ['COR from WMSU Portal', 'file', 1, '', '', 14],
        ['Validated COR from Student Affairs', 'file', 1, '', '', 15],
    ];
    ?>

    <div class="modal fade" id="addFieldModal" tabindex="-1" aria-labelledby="addFieldModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form action="add_field.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="academic_year_id" value="<?= $selected_ay_id ?? '' ?>">
                    <div id="hiddenFieldsData"></div>
                    <div id="dynamicFileInputs"></div>
                    <input type="hidden" name="field_order" id="fieldOrderInput">

                    <div class="modal-header">
                        <h5 class="modal-title" id="addFieldModalLabel">Manage Form Fields</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Columns -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Number of Columns</label>
                            <input type="number" id="numColumns" name="numColumns" class="form-control" min="1" max="6" value="3">
                            <small class="text-muted">Adjust the layout grid (1-6 columns). Drag to reorder.</small>
                        </div>

                        <!-- Draggable Fields -->
                        <label class="form-label fw-bold">Arrange Fields</label>
                        <div id="fieldsContainer"
                            class="border rounded p-3 bg-light mb-4 d-flex flex-wrap gap-3"
                            style="min-height:150px;">
                        </div>

                        <div class="alert alert-warning" role="alert">
                            <p>Everything that spans up to 3 columns will take center-fold of the form.</p>
                        </div>


                        <hr>

                        <div>
                            <h6 class="fw-bold mb-3">Add New Custom Field</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Field Label</label>
                                    <input type="text" class="form-control" name="field_label" id="newFieldLabel" placeholder="e.g., Phone Number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Field Type</label>
                                    <select class="form-select" id="newFieldType" name="field_type" onchange="toggleOptions()">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                        <option value="dropdown">Dropdown</option>
                                        <option value="radio">Radio Button</option>
                                        <option value="checkbox">Checkbox</option>
                                        <option value="file">File Upload</option>
                                    </select>
                                </div>
                                <div class="col-12" id="optionsContainer" style="display:none;">
                                    <label class="form-label">Options (comma separated)</label>
                                    <input type="text" class="form-control" id="newFieldOptions" name="options" placeholder="Option 1, Option 2, Option 3">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Field Description (Optional)</label>
                                    <textarea class="form-control" id="newFieldDescription" rows="2" name="field_description" placeholder="Instructions for the user..."></textarea>
                                </div>

                                <div class="col-md">
                                    <label class="form-label">Sample File/Image (Optional and only viewable for input fields)</label>
                                    <input type="file" class="form-control" id="newFieldSample" name="field_sample">
                                    <div id="samplePreview" class="mt-2"></div>
                                </div>
                                <div class="col-md d-flex align-items-center" style="margin-left: 15px;">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" id="newFieldRequired" value="1" name="is_required">
                                        <label class="form-check-label ms-2" for="newFieldRequired">Required Field</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success" id="addFieldBtn">Add Field</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        let fieldsData = <?= json_encode($defaultFields) ?>;
        const container = document.getElementById('fieldsContainer');
        let sortableInstance;

        const DEFAULT_FIELDS_COUNT = <?= count($defaultFields) ?>;

        function toggleOptions() {
            const type = document.getElementById('newFieldType').value;
            document.getElementById('optionsContainer').style.display = ['dropdown', 'radio', 'checkbox'].includes(type) ? 'block' : 'none';
        }

        // Render fields
        function renderFields() {
            container.innerHTML = '';
            fieldsData.forEach((field, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'field-wrapper p-2';
                wrapper.dataset.label = field[0];
                // Store the original index to find the data easily later
                wrapper.dataset.index = index;

                const div = document.createElement('div');
                div.className = 'p-3 border rounded bg-white shadow-sm field-item h-100';
                div.innerHTML = `
    <div class="d-flex justify-content-between align-items-start">
        <div class="fw-bold small">${field[0]}</div>
        <div class="d-flex gap-1 align-items-center">
            ${field[6] !== undefined && index >= DEFAULT_FIELDS_COUNT
                ? `<button type="button" class="btn btn-sm p-0 text-danger border-0 bg-transparent" onclick="removeField('${field[0]}')" title="Remove field">
                       <i class="bi bi-trash-fill" style="font-size:14px;"></i>
                   </button>`
                : ''
            }
            <i class="bi bi-grip-vertical text-muted drag-handle" style="cursor:grab;"></i>
        </div>
    </div>
    <div class="text-muted" style="font-size:0.75rem;">
        ${field[1].toUpperCase()} • ${field[2] ? 'Required' : 'Optional'}
    </div>`;

                wrapper.appendChild(div);
                container.appendChild(wrapper);
            });

            updateColumns();
            updateOrderInput(); // Sync the hidden inputs to the current state
        }

        function removeField(label) {
            const originalLength = <?= count($defaultFields) ?>;
            const fieldIndex = fieldsData.findIndex(f => f[0] === label);

            // Prevent deleting default fields
            if (fieldIndex < originalLength) return;

            fieldsData.splice(fieldIndex, 1);
            renderFields();
        }

        // Update hidden input
        function updateOrderInput() {
            const hiddenContainer = document.getElementById('hiddenFieldsData');
            hiddenContainer.innerHTML = '';

            const items = container.querySelectorAll('.field-wrapper');

            items.forEach((el, index) => {
                const label = el.dataset.label;
                const data = fieldsData.find(f => f[0] === label);

                if (data) {
                    createHidden(hiddenContainer, `fields[${index}][label]`, data[0]);
                    createHidden(hiddenContainer, `fields[${index}][type]`, data[1]);
                    createHidden(hiddenContainer, `fields[${index}][required]`, data[2]);
                    createHidden(hiddenContainer, `fields[${index}][options]`, data[3] || '');
                    createHidden(hiddenContainer, `fields[${index}][description]`, data[4] || '');
                    createHidden(hiddenContainer, `fields[${index}][order]`, index + 1);

                    // FIX: Handle the file upload dynamically
                    if (data[5] instanceof File) {
                        const fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.name = `fields[${index}][field_sample]`;
                        fileInput.style.display = 'none';

                        // Transfer the file object to the new input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(data[5]);
                        fileInput.files = dataTransfer.files;

                        hiddenContainer.appendChild(fileInput);
                    }
                }
            });

            const orderString = Array.from(items).map(el => el.dataset.label).join(',');
            document.getElementById('fieldOrderInput').value = orderString;
        }

        // Helper function to keep code clean
        function createHidden(parent, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            parent.appendChild(input);
        }

        // Initialize Sortable once
        function initSortable() {
            // Only initialize if it hasn't been done yet
            if (!sortableInstance) {
                sortableInstance = new Sortable(container, {
                    animation: 150,
                    ghostClass: 'bg-light-info',
                    onEnd: updateOrderInput,
                    // This ensures it works even if items are added dynamically
                    forceFallback: true
                });
            }
        }

        // Update grid columns dynamically
        function updateColumns() {
            const input = document.getElementById('numColumns');
            let cols = parseInt(input.value);
            if (cols < 1) cols = 1;
            if (cols > 6) cols = 6;

            const items = container.querySelectorAll('.field-wrapper');
            items.forEach(item => {
                item.style.flex = `1 1 calc(${100 / cols}% - 12px)`;
                // 12px is the gap between items
            });
        }

        // Attach the listener so it runs on every edit/keypress
        document.getElementById('numColumns').addEventListener('input', updateColumns);

        // Add new field
        document.getElementById('addFieldBtn').addEventListener('click', () => {
            const label = document.getElementById('newFieldLabel').value.trim();
            if (!label) return alert('Field label is required.');

            const fieldSampleInput = document.getElementById('newFieldSample');
            const type = document.getElementById('newFieldType').value;
            const required = document.getElementById('newFieldRequired').checked ? 1 : 0;
            const options = document.getElementById('newFieldOptions').value || '';
            const description = document.getElementById('newFieldDescription').value || '';

            // FIX: Capture the actual File object, not just the name
            const fileObject = fieldSampleInput.files.length > 0 ? fieldSampleInput.files[0] : null;

            // Push fileObject to the array (Index 5)
            const newField = [label, type, required, options, description, fileObject, fieldsData.length + 1];
            fieldsData.push(newField);

            // Clear form and Preview
            fieldSampleInput.value = '';
            document.getElementById('samplePreview').innerHTML = '';
            document.getElementById('newFieldLabel').value = '';
            document.getElementById('newFieldDescription').value = '';
            toggleOptions();

            renderFields();
        });
        // Sample File Preview
        document.getElementById('newFieldSample').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById('samplePreview');
            previewContainer.innerHTML = '';

            if (file) {
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.className = 'img-fluid border rounded';
                    img.style.maxHeight = '150px';
                    previewContainer.appendChild(img);
                } else {
                    previewContainer.innerHTML = `<span class="text-muted"><i class="bi bi-file-earmark"></i> ${file.name} selected</span>`;
                }
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            renderFields();
            initSortable(); // Re-initialize sortable after rendering
            // Change columns dynamically
            document.getElementById('numColumns').addEventListener('input', updateColumns);
        });
    </script>

    <style>
        #fieldsContainer {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            /* spacing between fields */
            min-height: 150px;
        }

        .field-wrapper {
            cursor: grab;

            /* width will be set dynamically via JS for columns */
        }

        .field-item {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: auto;
            /* let height adjust automatically */
        }

        .bg-light-info {

            background-color: #e7f3ff !important;
            border: 2px dashed #0d6efd !important;
        }
    </style>

    <!-- Plugins and Scripts -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>

    <?php if (isset($_SESSION['status'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['status'] ?>',
                title: '<?= $_SESSION['status'] == 'success' ? 'Success' : 'Error' ?>',
                text: '<?= $_SESSION['message'] ?>'
            });
        </script>
        <?php unset($_SESSION['status'], $_SESSION['message']); ?>
    <?php endif; ?>
</body>

</html>