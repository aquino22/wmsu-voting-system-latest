<?php
ini_set('max_execution_time', 3600);
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $sql = "
        SELECT
            m.id,
            m.name,
            m.email,
            m.password,
            m.gender,
            m.status,
            m.precinct       AS precinct_id,
            m.college        AS college_id,
            m.department     AS department_id,
            m.major          AS major_id,
            c.college_name,
            d.department_name,
            j.major_name,
            p.name           AS precinct_name,
            p.location       AS precinct_location,
            p.type           AS precinct_type,
            p.college_external,
            p.current_capacity,
            p.max_capacity,
            cm.campus_name,
            cm.campus_id
        FROM moderators m
        LEFT JOIN precincts   p  ON m.precinct   = p.id
        LEFT JOIN colleges    c  ON m.college     = c.college_id
        LEFT JOIN campuses    cm ON p.type        = cm.campus_id
        LEFT JOIN departments d  ON m.department  = d.department_id
        LEFT JOIN majors      j  ON m.major       = j.major_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $moderators = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching moderators: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Moderators</title>

    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <style>
        .custom-padding {
            padding: 20px !important;
        }

        .form-check {
            padding-left: 10px !important;
            margin-right: 0 !important;
            position: relative;
        }

        .form-check,
        .form-check-label {
            margin-left: 10px !important;
        }

        .edit-hint {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <div class="container-scroller">

        <!-- ── NAVBAR ───────────────────────────────────────────────────────── -->
        <?php
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email
                           FROM admin a JOIN users u ON a.user_id = u.id
                           WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $admin_full_name    = $admin['full_name'];
            $admin_phone_number = $admin['phone_number'];
            $admin_email        = $admin['email'];
        }
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
                        <small style="font-size:16px;"><b>WMSU i-Elect</b></small>
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
                            $stmt = $pdo->prepare("
                            SELECT e.id, e.election_name, e.academic_year_id, a.year_label, a.semester
                            FROM elections e JOIN academic_years a ON e.academic_year_id = a.id
                            WHERE e.status = :status
                            ORDER BY a.year_label DESC, a.semester DESC
                        ");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($ongoingElections) {
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> "        . htmlspecialchars($first['semester'])    . " | ";
                                echo "<b>Election:</b> "        . htmlspecialchars($first['election_name']) . "<br>";
                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none; margin-top:5px;">';
                                    foreach ($ongoingElections as $el) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($el['year_label']) . " | ";
                                        echo "<b>Semester:</b> "    . htmlspecialchars($el['semester'])    . " | ";
                                        echo "<b>Election:</b> "    . htmlspecialchars($el['election_name']) . "<br>";
                                    }
                                    echo '</div><br>';
                                    echo '<a href="javascript:void(0)" id="toggleElections" class="text-decoration-underline text-white">Show More</a>';
                                }
                            }
                            ?>
                        </h6>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-xs rounded-circle" src="images/wmsu-logo.png" alt="Profile image">
                            </div>
                            <p class="mb-1 mt-3 font-weight-semibold dropdown-item"><b>WMSU ADMIN</b></p>
                            <a class="dropdown-item" href="processes/accounts/logout.php">
                                <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <!-- ── END NAVBAR ───────────────────────────────────────────────────── -->

        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php') ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" data-bs-toggle="tab"
                                                href="#overview" role="tab">Moderators</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="d-flex align-items-center">
                                                    <h3 class="mb-0"><b>Moderators</b></h3>
                                                    <div class="ms-auto">
                                                        <button class="btn btn-primary text-white"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addModeratorModal">
                                                            <i class="bi bi-person-add"></i> Add Moderator
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered" id="moderatorsTable">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Name</th>
                                                                    <th>Email</th>
                                                                    <th>Gender</th>
                                                                    <th>College</th>
                                                                    <th>Department</th>
                                                                    <th>Precinct</th>
                                                                    <th>Account Status</th>
                                                                    <th>Manage</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($moderators as $m): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($m['name']) ?></td>
                                                                        <td><?= htmlspecialchars($m['email']) ?></td>
                                                                        <td><?= htmlspecialchars($m['gender']) ?></td>
                                                                        <td><?= htmlspecialchars($m['college_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($m['department_name'] ?? 'N/A') ?></td>
                                                                        <td>
                                                                            <?php if (!empty($m['precinct_name'])): ?>
                                                                                <strong><?= htmlspecialchars($m['precinct_name']) ?></strong><br>
                                                                                <small class="text-muted">
                                                                                    <?php
                                                                                    $typeDisplay = $m['campus_name'] ?? $m['precinct_type'];
                                                                                    if ($typeDisplay === 'WMSU ESU' && !empty($m['college_external'])) {
                                                                                        $typeDisplay .= ' - ' . htmlspecialchars($m['college_external']);
                                                                                    }
                                                                                    echo htmlspecialchars($m['precinct_location'])
                                                                                        . ' (' . htmlspecialchars($typeDisplay) . ')';
                                                                                    ?>
                                                                                </small>
                                                                            <?php else: ?>
                                                                                None
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($m['status'] === 'active'): ?>
                                                                                <button type="button" class="btn btn-success text-white btn-sm">Activated</button>
                                                                            <?php else: ?>
                                                                                <button type="button" class="btn btn-danger text-white btn-sm">Unactivated</button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <a href="view_moderator.php?id=<?= (int)$m['id'] ?>"
                                                                                class="btn btn-sm btn-primary text-white">
                                                                                <i class="bi bi-eye"></i> View
                                                                            </a>

                                                                            <!-- Edit: all saved values stored as data-* -->
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-warning text-white editModeratorBtn"
                                                                                data-id="<?= (int)$m['id'] ?>"
                                                                                data-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>"
                                                                                data-email="<?= htmlspecialchars($m['email'], ENT_QUOTES) ?>"
                                                                                data-gender="<?= htmlspecialchars($m['gender'], ENT_QUOTES) ?>"
                                                                                data-college-id="<?= (int)($m['college_id'] ?? 0) ?>"
                                                                                data-college-name="<?= htmlspecialchars($m['college_name'] ?? '', ENT_QUOTES) ?>"
                                                                                data-department-id="<?= (int)($m['department_id'] ?? 0) ?>"
                                                                                data-department-name="<?= htmlspecialchars($m['department_name'] ?? '', ENT_QUOTES) ?>"
                                                                                data-major-id="<?= (int)($m['major_id'] ?? 0) ?>"
                                                                                data-major-name="<?= htmlspecialchars($m['major_name'] ?? '', ENT_QUOTES) ?>"
                                                                                data-precinct-id="<?= (int)($m['precinct_id'] ?? 0) ?>"
                                                                                data-precinct-name="<?= htmlspecialchars($m['precinct_name'] ?? '', ENT_QUOTES) ?>">
                                                                                <i class="bi bi-pen"></i> Edit
                                                                            </button>

                                                                            <button type="button"
                                                                                class="btn btn-sm toggle-status-btn text-white <?= $m['status'] === 'active' ? 'btn-success' : 'btn-warning' ?>"
                                                                                data-id="<?= $m['id'] ?>"
                                                                                data-status="<?= $m['status'] ?>">
                                                                                <?= $m['status'] === 'active'
                                                                                    ? '<i class="bi bi-check-circle"></i> Active'
                                                                                    : '<i class="bi bi-x-circle"></i> Inactive' ?>
                                                                            </button>

                                                                            <button type="button"
                                                                                class="btn btn-sm btn-danger text-white"
                                                                                onclick="deleteModerator(<?= $m['id'] ?>)">
                                                                                <i class="bi bi-trash"></i> Delete
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end container-scroller -->


    <!-- ════════════════════════════════════════════════════════════════════════
     ADD MODERATOR MODAL
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="addModeratorModal" tabindex="-1" aria-labelledby="addModeratorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModeratorModalLabel">Add Moderator Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addModeratorForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="add_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="add_password" name="password"
                                    required minlength="8" oninput="checkPasswordStrength('add')">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('add')">
                                    <i id="add_toggleIcon" class="bi bi-eye"></i>
                                </span>
                            </div>
                            <small id="add_strength_text" class="form-text"></small>
                            <div id="add_strength_bar" class="progress mt-1" style="height:5px; display:none;">
                                <div class="progress-bar" role="progressbar" style="width:0%;"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-control" id="add_gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">College <span class="text-danger">*</span></label>
                            <select class="form-control" id="add_college" name="college" required>
                                <option value="" disabled selected>Select College</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-control" id="add_department" name="department" required>
                                <option value="" disabled selected>Select Department</option>
                            </select>
                        </div>
                        <div class="mb-3" id="add_major_wrapper" style="display:none;">
                            <label class="form-label">Major</label>
                            <select class="form-control" id="add_major" name="major">
                                <option value="0">None</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precinct</label>
                            <div id="add_precinct_container" class="border rounded p-2"
                                style="max-height:220px; overflow-y:auto;">
                                <p class="text-muted mb-0">Select a college and department first.</p>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-alpha-x-circle"></i> Close
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-plus-circle"></i> Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════════════════
     EDIT MODERATOR MODAL  (same structure as Add)
════════════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editModeratorModal" tabindex="-1" aria-labelledby="editModeratorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModeratorModalLabel">Edit Moderator Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editModeratorForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="moderator_id" id="edit_moderator_id">

                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="edit_password" name="password"
                                    oninput="checkPasswordStrength('edit')">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('edit')">
                                    <i id="edit_toggleIcon" class="bi bi-eye"></i>
                                </span>
                            </div>
                            <small class="text-muted">Leave blank to keep current password</small><br>
                            <small id="edit_strength_text" class="form-text"></small>
                            <div id="edit_strength_bar" class="progress mt-1" style="height:5px; display:none;">
                                <div class="progress-bar" role="progressbar" style="width:0%;"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">College <span class="text-danger">*</span></label>
                            <small class="edit-hint d-block mb-1" id="edit_hint_college"></small>
                            <select class="form-control" id="edit_college" name="college" required>
                                <option value="" disabled selected>Select College</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <small class="edit-hint d-block mb-1" id="edit_hint_department"></small>
                            <select class="form-control" id="edit_department" name="department" required>
                                <option value="" disabled selected>Select Department</option>
                            </select>
                        </div>
                        <div class="mb-3" id="edit_major_wrapper" style="display:none;">
                            <label class="form-label">Major</label>
                            <small class="edit-hint d-block mb-1" id="edit_hint_major"></small>
                            <select class="form-control" id="edit_major" name="major">
                                <option value="0">None</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precinct</label>
                            <small class="edit-hint d-block mb-1" id="edit_hint_precinct"></small>
                            <div id="edit_precinct_container" class="border rounded p-2"
                                style="max-height:220px; overflow-y:auto;">
                                <p class="text-muted mb-0">Loading...</p>
                            </div>
                        </div>

                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Moderator</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════════════════
     SCRIPTS  (single load, correct order)
════════════════════════════════════════════════════════════════════════ -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="vendors/progressbar.js/progressbar.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/todolist.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/Chart.roundedBarCharts.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ─── Navbar ───────────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleElections');
            const moreDiv = document.getElementById('moreElections');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const open = moreDiv.style.display === 'none';
                    moreDiv.style.display = open ? 'block' : 'none';
                    toggleBtn.textContent = open ? 'Show Less' : 'Show More';
                });
            }
            const backToTop = document.getElementById('backToTop');
            if (backToTop) {
                window.addEventListener('scroll', () => backToTop.classList.toggle('show', window.pageYOffset > 200));
                backToTop.addEventListener('click', () => window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                }));
            }
        });

        // ─── DataTable ────────────────────────────────────────────────────────────────
        $(document).ready(function() {
            $('#moderatorsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                order: [
                    [0, 'asc']
                ]
            });
        });

        // ─── Shared utilities ─────────────────────────────────────────────────────────

        function escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function calcStrength(pw) {
            let s = 0;
            if (pw.length > 0) s += 20;
            if (pw.length >= 8) s += 20;
            if (/[A-Z]/.test(pw)) s += 20;
            if (/[0-9]/.test(pw)) s += 20;
            if (/[^A-Za-z0-9]/.test(pw)) s += 20;
            return s;
        }

        function checkPasswordStrength(prefix) {
            const pw = document.getElementById(prefix + '_password').value;
            const bar = document.getElementById(prefix + '_strength_bar');
            const text = document.getElementById(prefix + '_strength_text');
            const s = calcStrength(pw);

            bar.style.display = pw.length ? 'flex' : 'none';
            bar.children[0].style.width = s + '%';

            const levels = [
                [20, 'Very Weak', 'bg-danger'],
                [40, 'Weak', 'bg-warning'],
                [60, 'Fair', 'bg-info'],
                [80, 'Good', 'bg-primary'],
                [100, 'Strong', 'bg-success'],
            ];
            const lvl = levels.find(([max]) => s <= max) || levels[4];
            text.textContent = lvl[1];
            bar.children[0].className = 'progress-bar ' + lvl[2];
        }

        function togglePassword(prefix) {
            const input = document.getElementById(prefix + '_password');
            const icon = document.getElementById(prefix + '_toggleIcon');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !show);
            icon.classList.toggle('bi-eye-slash', show);
        }

        // ─── Precinct rendering (shared) ─────────────────────────────────────────────

        function buildPrecinctRow(p, selectedId, inputName) {
            const uid = inputName + '_' + p.id;
            const pct = p.max_capacity > 0 ? (p.current_capacity / p.max_capacity) * 100 : 0;
            const dotCls = pct < 50 ? 'cap-low' : pct < 80 ? 'cap-mid' : 'cap-high';
            const div = document.createElement('div');
            div.className = 'precinct-item';
            div.innerHTML = `
        <input type="radio" name="${inputName}" id="${uid}" value="${p.id}"
               class="precinct-check" ${p.id == selectedId ? 'checked' : ''}>
        <label for="${uid}">
            <span class="p-name" style="font-size:.77rem;">${escHtml(p.name)}</span>
            <span class="p-meta" style="font-size:.77rem;">
                📍 ${escHtml(p.location)} &nbsp;·&nbsp;<br>
                <span class="cap-dot ${dotCls}"></span>
                ${p.current_capacity} / ${p.max_capacity}
            </span>
        </label>`;
            return div;
        }

        function renderPrecincts(container, data, selectedId, inputName) {
            container.innerHTML = '';
            if (!data.success || !Array.isArray(data.groups) || !data.groups.length) {
                container.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0">No precincts found.</div>';
                return;
            }
            data.groups.forEach(group => {
                const direct = (group.precincts || []).length;
                const sub = (group.sub_groups || []).reduce((a, s) => a + s.precincts.length, 0);
                const total = direct + sub;

                const card = document.createElement('div');
                card.className = 'card mb-3 shadow-sm border-0';
                card.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                <strong class="small">🏫 ${escHtml(group.group_name)}</strong>
                <span class="badge bg-danger">${total} precinct${total !== 1 ? 's' : ''}</span>
            </div>
            <div class="card-body p-2"></div>`;
                const body = card.querySelector('.card-body');

                (group.precincts || []).forEach(p => {
                    const row = buildPrecinctRow(p, selectedId, inputName);
                    row.classList.add('mb-1', 'small');
                    body.appendChild(row);
                });

                (group.sub_groups || []).forEach(sub => {
                    const sw = document.createElement('div');
                    sw.className = 'mt-2 p-2 rounded bg-light border-start border-4';
                    sw.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-secondary">📍 ${escHtml(sub.sub_name)}</span>
                    <span class="badge rounded-pill bg-secondary">${sub.precincts.length}</span>
                </div>`;
                    (sub.precincts || []).forEach(p => {
                        const row = buildPrecinctRow(p, selectedId, inputName);
                        row.classList.add('mb-1', 'ms-1');
                        row.style.fontSize = '.85rem';
                        sw.appendChild(row);
                    });
                    body.appendChild(sw);
                });

                container.appendChild(card);
            });
        }

        function loadPrecincts(collegeId, deptId, container, selectedId, inputName) {
            container.innerHTML = '<div class="text-muted small p-2">Loading precincts…</div>';
            if (!collegeId || !deptId) {
                container.innerHTML = '<div class="alert alert-warning py-2 px-3 mb-0">Select a college and department first.</div>';
                return;
            }
            fetch(`processes/moderators/get_precincts.php?college_id=${encodeURIComponent(collegeId)}&department_id=${encodeURIComponent(deptId)}`)
                .then(r => r.json())
                .then(data => renderPrecincts(container, data, selectedId, inputName))
                .catch(() => {
                    container.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0">Error loading precincts.</div>';
                });
        }

        // ─── ADD modal ────────────────────────────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function() {

            const aC = document.getElementById('add_college');
            const aD = document.getElementById('add_department');
            const aM = document.getElementById('add_major');
            const aMW = document.getElementById('add_major_wrapper');
            const aPC = document.getElementById('add_precinct_container');

            // Load colleges once
            fetch('processes/moderators/get_colleges.php')
                .then(r => r.json())
                .then(data => {
                    aC.innerHTML = '<option value="" disabled selected>Select College</option>';
                    data.forEach(c => {
                        const o = new Option(c.college_name, c.college_id);
                        aC.add(o);
                    });
                });

            aC.addEventListener('change', function() {
                aD.innerHTML = '<option value="" disabled selected>Loading…</option>';
                aM.innerHTML = '<option value="0">None</option>';
                aMW.style.display = 'none';
                fetch(`processes/moderators/get_departments.php?college_id=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        aD.innerHTML = '<option value="" disabled selected>Select Department</option>';
                        data.forEach(d => aD.add(new Option(d.department_name, d.department_id)));
                    });
                loadPrecincts(this.value, '', aPC, 0, 'precinct');
            });

            aD.addEventListener('change', function() {
                fetch(`processes/moderators/get_majors.php?department_id=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        aM.innerHTML = '<option value="0">None</option>';
                        if (data.length) {
                            data.forEach(m => aM.add(new Option(m.major_name, m.major_id)));
                            aMW.style.display = 'block';
                        } else {
                            aMW.style.display = 'none';
                        }
                    });
                loadPrecincts(aC.value, this.value, aPC, 0, 'precinct');
            });

            document.getElementById('addModeratorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (calcStrength(document.getElementById('add_password').value) < 60) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Weak Password',
                        text: 'Use at least 8 characters with uppercase, numbers, and special characters.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }
                fetch('processes/moderators/process_moderators.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message,
                                    confirmButtonColor: '#3085d6'
                                })
                                .then(() => {
                                    this.reset();
                                    location.reload();
                                });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(err => Swal.fire({
                        icon: 'error',
                        title: 'Oops…',
                        text: 'Something went wrong! ' + err.message
                    }));
            });
        });

        // ─── EDIT modal ───────────────────────────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function() {

            const eC = document.getElementById('edit_college');
            const eD = document.getElementById('edit_department');
            const eM = document.getElementById('edit_major');
            const eMW = document.getElementById('edit_major_wrapper');
            const ePC = document.getElementById('edit_precinct_container');

            let savedPrecinctId = 0; // remembered between cascades

            // Load colleges once into edit dropdown
            fetch('processes/moderators/get_colleges.php')
                .then(r => r.json())
                .then(data => {
                    eC.innerHTML = '<option value="" disabled selected>Select College</option>';
                    data.forEach(c => eC.add(new Option(c.college_name, c.college_id)));
                });

            // College change inside edit modal
            eC.addEventListener('change', function() {
                eD.innerHTML = '<option value="" disabled selected>Loading…</option>';
                eM.innerHTML = '<option value="0">None</option>';
                eMW.style.display = 'none';
                fetch(`processes/moderators/get_departments.php?college_id=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        eD.innerHTML = '<option value="" disabled selected>Select Department</option>';
                        data.forEach(d => eD.add(new Option(d.department_name, d.department_id)));
                    });
                loadPrecincts(this.value, '', ePC, savedPrecinctId, 'edit_precinct');
            });

            // Department change inside edit modal
            eD.addEventListener('change', function() {
                fetch(`processes/moderators/get_majors.php?department_id=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        eM.innerHTML = '<option value="0">None</option>';
                        if (data.length) {
                            data.forEach(m => eM.add(new Option(m.major_name, m.major_id)));
                            eMW.style.display = 'block';
                        } else {
                            eMW.style.display = 'none';
                        }
                    });
                loadPrecincts(eC.value, this.value, ePC, savedPrecinctId, 'edit_precinct');
            });

            // ── Open edit modal from button ───────────────────────────────────────────
            document.querySelectorAll('.editModeratorBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const d = this.dataset;

                    // Save precinct so radio auto-checks after precincts load
                    savedPrecinctId = parseInt(d.precinctId) || 0;

                    // Plain fields
                    document.getElementById('edit_moderator_id').value = d.id;
                    document.getElementById('edit_name').value = d.name;
                    document.getElementById('edit_email').value = d.email;
                    document.getElementById('edit_gender').value = d.gender;
                    document.getElementById('edit_password').value = '';
                    document.getElementById('edit_strength_bar').style.display = 'none';
                    document.getElementById('edit_strength_text').textContent = '';

                    // Hints (Current: …)
                    const hint = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = val ? `Current: ${val}` : '';
                    };
                    hint('edit_hint_college', d.collegeName);
                    hint('edit_hint_department', d.departmentName);
                    hint('edit_hint_major', (d.majorId && d.majorId !== '0') ? d.majorName : '');
                    hint('edit_hint_precinct', d.precinctName || 'None');

                    // Wait for colleges to finish loading, then set value and cascade
                    // (colleges are loaded once on DOMContentLoaded so they should be ready)
                    eC.value = d.collegeId;

                    // Cascade: departments
                    fetch(`processes/moderators/get_departments.php?college_id=${encodeURIComponent(d.collegeId)}`)
                        .then(r => r.json())
                        .then(depts => {
                            eD.innerHTML = '<option value="" disabled selected>Select Department</option>';
                            depts.forEach(dept => eD.add(new Option(dept.department_name, dept.department_id)));
                            eD.value = d.departmentId;

                            // Cascade: majors
                            return fetch(`processes/moderators/get_majors.php?department_id=${encodeURIComponent(d.departmentId)}`);
                        })
                        .then(r => r.json())
                        .then(majors => {
                            eM.innerHTML = '<option value="0">None</option>';
                            if (majors.length) {
                                majors.forEach(m => eM.add(new Option(m.major_name, m.major_id)));
                                eMW.style.display = 'block';
                                eM.value = d.majorId || '0';
                            } else {
                                eMW.style.display = 'none';
                            }

                            // Cascade: precincts (with saved precinct pre-checked)
                            loadPrecincts(d.collegeId, d.departmentId, ePC, savedPrecinctId, 'edit_precinct');
                        })
                        .catch(() => {
                            ePC.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0">Error loading data.</div>';
                        });

                    new bootstrap.Modal(document.getElementById('editModeratorModal')).show();
                });
            });

            // EDIT form submit
            document.getElementById('editModeratorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('processes/moderators/edit_moderator.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: 'Moderator updated successfully.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to update moderator.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong.'
                    }));
            });
        });

        // ─── Toggle status ────────────────────────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-status-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const stat = this.dataset.status;
                    const self = this;
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Change status to ${stat === 'active' ? 'inactive' : 'active'}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        fetch('processes/moderators/toggle_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `id=${encodeURIComponent(id)}`
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    const active = data.new_status === 'active';
                                    self.classList.toggle('btn-success', active);
                                    self.classList.toggle('btn-warning', !active);
                                    self.innerHTML = active ?
                                        '<i class="bi bi-check-circle"></i> Active' :
                                        '<i class="bi bi-x-circle"></i> Inactive';
                                    self.dataset.status = data.new_status;
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: data.message,
                                        timer: 750,
                                        showConfirmButton: false
                                    });
                                    setTimeout(() => location.reload(), 750);
                                } else {
                                    throw new Error(data.message || 'Failed');
                                }
                            })
                            .catch(err => Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: `Failed: ${err.message}`,
                                confirmButtonColor: '#d33'
                            }));
                    });
                });
            });
        });

        // ─── Delete ───────────────────────────────────────────────────────────────────

        function deleteModerator(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to undo this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('processes/moderators/delete_moderator.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${id}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The moderator has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                })
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error!', 'Something went wrong.', 'error'));
            });
        }
    </script>
    <script>
        async function checkElectionStatus() {
            try {
                const response = await fetch('check_status.php');
                const data = await response.json();

                if (data.expired && data.elections.length > 0) {
                    // Loop through each expired election found
                    for (const election of data.elections) {
                        await Swal.fire({
                            title: 'Voting Period Concluded',
                            text: `The period for ${election.election_name} has officially ended. Click 'Publish Results' to proceed. This can still be dismissed if further information checking on periods are needed to be performed.`,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Publish Results',
                            cancelButtonText: 'Dismiss',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#6e7881',
                            allowOutsideClick: true,
                            allowEscapeKey: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = election.redirect_to;
                            }
                        });
                    }
                }
            } catch (err) {
                console.error('Status Check Error:', err);
            }
        }

        document.addEventListener('DOMContentLoaded', checkElectionStatus);
    </script>

    <button class="back-to-top" id="backToTop" title="Go to top">
        <i class="mdi mdi-arrow-up"></i>
    </button>

</body>

</html>