<?php
session_start();
include('includes/conn.php');
include('includes/conn_archived_conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADMIN";
  header("Location: ../index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | Precincts</title>
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="js/select.dataTables.min.css">
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.9.55/css/materialdesignicons.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<style>
  .custom-padding {
    padding: 20px !important;
  }

  th,
  td {
    padding: 20px !important;
    text-align: center;
  }

  #electionCheckboxes {
    margin-left: 25px !important;
  }

  /* Grey pill badge for previous elections */
  .archived-badge {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 12px;
    background-color: #6c757d;
    color: #fff;
    display: inline-block;
    margin: 2px;
  }

  /* ── Tab switcher ────────────────────────────────────────────────── */
  .precinct-tabs {
    border-bottom: 2px solid #1F3BB3;
  }

  .precinct-tabs .nav-link {
    color: #1F3BB3;
    font-weight: 500;
    border-radius: 8px 8px 0 0;
    padding: 10px 26px;
    border: 1px solid transparent;
    transition: background .15s;
  }

  .precinct-tabs .nav-link.active {
    background-color: #1F3BB3;
    color: #fff !important;
    border-color: #1F3BB3 #1F3BB3 #fff;
  }

  .precinct-tabs .nav-link:not(.active):hover {
    background-color: #e8ecf8;
  }
</style>

<body>
  <div class="container-scroller">

    <?php
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email
                         FROM admin a
                         JOIN users u ON a.user_id = u.id
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

    <!-- ── Navbar ──────────────────────────────────────────────────────── -->
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
              FROM elections e
              JOIN academic_years a ON e.academic_year_id = a.id
              WHERE e.status = :status
              ORDER BY a.year_label DESC, a.semester DESC
            ");
              $stmt->execute(['status' => 'Ongoing']);
              $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
              if ($ongoingElections) {
                $first = array_shift($ongoingElections);
                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label'])     . " | ";
                echo "<b>Semester:</b> "         . htmlspecialchars($first['semester'])       . " | ";
                echo "<b>Election:</b> "         . htmlspecialchars($first['election_name'])  . "<br>";
                if ($ongoingElections) {
                  echo '<div id="moreElections" style="display:none;margin-top:5px;">';
                  foreach ($ongoingElections as $el) {
                    echo "<b>School Year:</b> " . htmlspecialchars($el['year_label'])    . " | ";
                    echo "<b>Semester:</b> "     . htmlspecialchars($el['semester'])      . " | ";
                    echo "<b>Election:</b> "     . htmlspecialchars($el['election_name']) . "<br>";
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
                    const hidden = moreDiv.style.display === "none";
                    moreDiv.style.display = hidden ? "block" : "none";
                    toggleBtn.textContent = hidden ? "Show Less" : "Show More";
                  });
                }
                const btn = document.getElementById('backToTop');
                if (btn) {
                  window.addEventListener('scroll', () => btn.classList.toggle('show', window.pageYOffset > 200));
                  btn.addEventListener('click', () => window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                  }));
                }
              });
            </script>
          </li>
        </ul>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <div class="dropdown-header text-center">
                <img class="img-xs rounded-circle logoe" src="images/wmsu-logo.png" alt="Profile image">
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
    <!-- /Navbar -->

    <div class="page-body-wrapper">
      <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
      <?php include('includes/sidebar.php') ?>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">

              <?php
              // Pre-fetch counts for the tab badges
              $active_count   = (int) $pdo->query("SELECT COUNT(*) FROM precincts WHERE status != 'archived'")->fetchColumn();
              $archived_count = (int) $pdo->query("SELECT COUNT(*) FROM precinct_elections WHERE archived = 1")->fetchColumn();
              ?>

              <!-- ════════════════════════════════════════════════════════ -->
              <!-- TAB NAV                                                  -->
              <!-- ════════════════════════════════════════════════════════ -->
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active" id="tab-active" data-bs-toggle="tab"
                        data-bs-target="#paneActive" type="button" role="tab"
                        aria-controls="paneActive" aria-selected="true">
                        <i class="bi bi-house-door me-1"></i>
                        Active Precincts
                        <span class="tab-badge"><?= $active_count ?></span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="tab-archived" data-bs-toggle="tab"
                        data-bs-target="#paneArchived" type="button" role="tab"
                        aria-controls="paneArchived" aria-selected="false"> <i class="bi bi-archive me-1"></i>
                        Archived Election History
                        <span class="tab-badge"><?= $archived_count ?></span>
                      </a>
                    </li>

                  </ul>
                </div>
                <!-- ════════════════════════════════════════════════════════ -->
                <!-- TAB CONTENT                                              -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="tab-content" id="precinctTabContent">


                  <!-- ══════════════════════════════════════════════════════ -->
                  <!-- PANE 1 — ACTIVE PRECINCTS                             -->
                  <!-- ══════════════════════════════════════════════════════ -->
                  <div class="tab-pane fade show active" id="paneActive" role="tabpanel" aria-labelledby="tab-active">
                    <div class="card px-5 pt-4">

                      <div class="d-flex align-items-center mb-3 mt-2">
                        <h3 class="mb-0"><b>Precincts</b></h3>
                        <div class="ms-auto">
                          <a href="add_precinct.php" target="_blank">
                            <button class="btn btn-primary text-white" style="background-color:#1F3BB3;">
                              <i class="bi bi-house-add"></i> Add Precinct
                            </button>
                          </a>
                        </div>
                      </div>

                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered w-100" id="precinctsTable">
                            <thead class="thead-dark text-center">
                              <tr>
                                <th>Name</th>
                                <th>College</th>
                                <th>Department</th>
                                <th>Major</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Max Capacity</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                                <th>Assignment Status</th>
                                <th>Occupied Status</th>
                                <th>Active Election</th>
                                <th>Previous Elections</th>
                                <th>Manage</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                              $query = "
    SELECT
        p.*,
        c.college_name,
        d.department_name,
        m.major_name,
        campus.campus_name     AS campus_type_name,
        ext_campus.campus_name AS external_campus_name
    FROM precincts p
    LEFT JOIN colleges    c          ON p.college          = c.college_id
    LEFT JOIN departments d          ON p.department       = d.department_id
    LEFT JOIN majors      m          ON p.major_id         = m.major_id
    LEFT JOIN campuses    campus     ON p.type             = campus.campus_id
    LEFT JOIN campuses    ext_campus ON p.college_external = ext_campus.campus_id
    WHERE p.status != 'archived'
    ORDER BY p.created_at DESC
";
                              $stmt = $pdo->prepare($query);
                              $stmt->execute();
                              $precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                              if (count($precincts) > 0) {
                                foreach ($precincts as $row) {
                                  $pid = $row['id'];

                                  // Active election only (archived = 0)
                                  $ae = $pdo->prepare("
                                SELECT e.election_name
                                FROM precinct_elections pe
                                JOIN elections e ON pe.election_name = e.id
                                WHERE pe.precinct_id = ? AND pe.archived = 0
                                LIMIT 1
                              ");
                                  $ae->execute([$pid]);
                                  $activeEl = $ae->fetchColumn();
                                  $activeElHtml = $activeEl
                                    ? '<span class="badge bg-success px-3 py-2">' . htmlspecialchars($activeEl) . '</span>'
                                    : '<span class="text-muted">None</span>';

                                  // Previous elections (archived = 1)
                                  $pe_stmt = $pdo->prepare("
                                SELECT e.election_name, pe.archived_at
                                FROM precinct_elections pe
                                JOIN elections e ON pe.election_name = e.id
                                WHERE pe.precinct_id = ? AND pe.archived = 1
                                ORDER BY pe.archived_at DESC
                              ");
                                  $pe_stmt->execute([$pid]);
                                  $prevEls = $pe_stmt->fetchAll(PDO::FETCH_ASSOC);

                                  $prevHtml = '';
                                  foreach ($prevEls as $p_el) {
                                    $prevHtml .= '<span class="archived-badge" title="Archived: '
                                      . date('M j, Y', strtotime($p_el['archived_at'])) . '">'
                                      . htmlspecialchars($p_el['election_name'])
                                      . '</span> ';
                                  }
                                  if (!$prevHtml) $prevHtml = '<span class="text-muted">—</span>';

                                  echo "<tr>
                                <td>" . htmlspecialchars($row['name']) . "</td>
                                <td>" . htmlspecialchars($row['college_name']    ?? 'Unknown College')     . "</td>
                                <td>" . htmlspecialchars($row['department_name'] ?? 'Unknown Department')  . "</td>
                                <td>" . htmlspecialchars($row['major_name']      ?? 'No majors')           . "</td>
                                <td>" . htmlspecialchars($row['location']) . "</td>
                                <td>" . htmlspecialchars($row['campus_type_name'] ?? 'Unknown');
                                  if (($row['campus_type_name'] ?? '') === 'WMSU ESU' && !empty($row['external_campus_name'])) {
                                    echo " (" . htmlspecialchars($row['external_campus_name']) . ")";
                                  }
                                  echo "</td>
                                <td>" . htmlspecialchars($row['max_capacity']) . "</td>
                                <td>" . date('F j, Y g:i A', strtotime($row['created_at'])) . "</td>
                                <td>" . date('F j, Y g:i A', strtotime($row['updated_at'])) . "</td>
                                <td>
                                  <button class='text-white btn btn-" . ($row['assignment_status'] === 'assigned' ? 'success' : 'danger') . "'>
                                    " . ucfirst($row['assignment_status']) . "
                                  </button>
                                </td>
                                <td>
                                  <button class='text-white btn btn-" . ($row['occupied_status'] === 'occupied' ? 'success' : 'danger') . "'>
                                    " . ucfirst($row['occupied_status']) . "
                                  </button>
                                </td>
                                <td>{$activeElHtml}</td>
                                <td>{$prevHtml}</td>
                                <td>
                                  <a href='view_precinct.php?id=" . urlencode($pid) . "' target='_blank'>
                                    <button class='text-white btn btn-success mb-1'>View</button>
                                  </a>
                                  <a href='edit_precinct.php?id=" . urlencode($pid) . "' target='_blank'>
                                    <button class='text-white btn btn-warning mb-1'>Edit</button>
                                  </a>
                                  <button class='text-white btn btn-danger deleteBtn' data-id='" . urlencode($pid) . "'>
                                    Delete
                                  </button>
                                </td>
                              </tr>";
                                }
                              } else {
                                echo "<tr><td colspan='14' class='text-center py-4'>No precincts found.</td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- /PANE 1 -->


                  <!-- ══════════════════════════════════════════════════════ -->
                  <!-- PANE 2 — ARCHIVED ELECTION HISTORY                    -->
                  <!-- ══════════════════════════════════════════════════════ -->
                  <div class="tab-pane fade" id="paneArchived" role="tabpanel" aria-labelledby="tab-archived">
                    <div class="card px-5 pt-4">

                      <div class="d-flex align-items-center mb-1 mt-2">
                        <h3 class="mb-0"><b>Archived Election History</b></h3>
                      </div>
                      <p class="text-muted mb-3">
                        These are previous election assignments per precinct.
                        Click <strong>Edit &amp; Reassign</strong> to open the precinct in the edit page —
                        saving it will archive the current assignment and create a new active one.
                      </p>

                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered w-100" id="archivedHistoryTable">
                            <thead class="thead-dark text-center">
                              <tr>
                                <th>Precinct Name</th>
                                <th>College</th>
                                <th>Department</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Previously Assigned Election</th>
                                <th>Archived On</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                              $arch_stmt = $pdo->prepare("
                            SELECT
                              p.id          AS precinct_id,
                              p.name        AS precinct_name,
                              p.location,
                              c.college_name,
                              d.department_name,
                              e.election_name,
                              pe.archived_at,
                               campus.campus_name     AS campus_type_name,
        ext_campus.campus_name AS external_campus_name
                            FROM precinct_elections pe
                            JOIN precincts    p  ON pe.precinct_id   = p.id
                            JOIN elections   e   ON pe.election_name = e.id
                            LEFT JOIN colleges    c ON p.college    = c.college_id
                            LEFT JOIN departments d ON p.department = d.department_id
                             LEFT JOIN campuses    campus     ON p.type             = campus.campus_id
    LEFT JOIN campuses    ext_campus ON p.college_external = ext_campus.campus_id
                            WHERE pe.archived = 1
                            ORDER BY pe.archived_at DESC
                          ");
                              $arch_stmt->execute();
                              $arch_rows = $arch_stmt->fetchAll(PDO::FETCH_ASSOC);

                              if (!empty($arch_rows)) {
                                foreach ($arch_rows as $ar) {
                                  echo "<tr>
                                <td>" . htmlspecialchars($ar['precinct_name'])    . "</td>
                                <td>" . htmlspecialchars($ar['college_name']    ?? '—') . "</td>
                                <td>" . htmlspecialchars($ar['department_name'] ?? '—') . "</td>
                                   <td>" . htmlspecialchars($ar['location']) . "</td>
                               <td>" . htmlspecialchars($ar['campus_type_name'] ?? 'Unknown');
                                  if (($ar['campus_type_name'] ?? '') === 'WMSU ESU' && !empty($ar['external_campus_name'])) {
                                    echo " (" . htmlspecialchars($ar['external_campus_name']) . ")";
                                  }
                                  echo "</td>
                                <td>
                                  <span class='badge bg-secondary fs-6 px-3 py-2'>"
                                    . htmlspecialchars($ar['election_name'])
                                    . "</span>
                                </td>
                                <td>" . date('F j, Y g:i A', strtotime($ar['archived_at'])) . "</td>
                                <td>
                                  <a href='edit_precinct.php?id=" . urlencode($ar['precinct_id']) . "' target='_blank'>
                                    <button class='text-white btn btn-warning'>
                                      <i class='bi bi-pencil-square me-1'></i> Edit &amp; Reassign
                                    </button>
                                  </a>
                                </td>
                              </tr>";
                                }
                              } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>No archived election assignments yet.</td></tr>";
                              }
                              ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- /PANE 2 -->

                </div><!-- /tab-content -->
              </div><!-- /col -->
            </div><!-- /row -->
          </div><!-- /content-wrapper -->
        </div><!-- /main-panel -->
      </div><!-- /page-body-wrapper -->
    </div><!-- /container-scroller -->
  </div><!-- /container-scroller -->

  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <!-- VIEW MODAL                                                            -->
  <!-- ══════════════════════════════════════════════════════════════════════ -->
  <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="viewModalLabel">Viewing Precinct Details</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="row">
              <div class="col">
                <div class="mb-3">
                  <label for="view_name" class="form-label">Name</label>
                  <input type="text" class="form-control" id="view_name" readonly>
                </div>
                <div class="mb-3">
                  <label for="view_location" class="form-label">Location</label>
                  <input type="text" class="form-control" id="view_location" readonly>
                </div>
                <div class="mb-3">
                  <label for="view_type" class="form-label">Type</label>
                  <input type="text" class="form-control" id="view_type" readonly>
                </div>
              </div>
              <div class="col">
                <div class="mb-3">
                  <label for="view_assignment_status" class="form-label">Assignment Status</label>
                  <input type="text" class="form-control" id="view_assignment_status" readonly>
                </div>
                <div class="mb-3">
                  <label for="view_occupied_status" class="form-label">Occupied Status</label>
                  <input type="text" class="form-control" id="view_occupied_status" readonly>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>

  <script>
    $(document).ready(function() {

      // ── Active Precincts DataTable ────────────────────────────────────
      $('#precinctsTable').DataTable({
        order: [
          [0, 'asc']
        ],
        paging: true,
        searching: true,
        ordering: true,
        info: true
      });

      // ── Archived History DataTable — init on first tab open only ─────
      // (avoids column-width glitch when table is hidden on load)
      $('#tab-archived').one('shown.bs.tab', function() {
        $('#archivedHistoryTable').DataTable({
          order: [
            [4, 'desc']
          ], // sort by Archived On descending
          paging: true,
          searching: true,
          ordering: true,
          info: true
        });
      });

      // ── View modal ────────────────────────────────────────────────────
      $(document).on('click', '.viewBtn', function() {
        const as = ($(this).data('assignment_status') || '');
        const os = ($(this).data('occupied_status') || '');
        $('#view_name').val($(this).data('name'));
        $('#view_location').val($(this).data('location'));
        $('#view_type').val($(this).data('type'));
        $('#view_assignment_status').val(as.charAt(0).toUpperCase() + as.slice(1));
        $('#view_occupied_status').val(os.charAt(0).toUpperCase() + os.slice(1));
      });

      // ── Delete ────────────────────────────────────────────────────────
      $(document).on('click', '.deleteBtn', function() {
        const precinctId = $(this).data('id');
        Swal.fire({
          title: 'Are you sure?',
          text: 'This action cannot be undone!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              type: 'POST',
              url: 'processes/precincts/delete.php',
              data: {
                id: precinctId
              },
              dataType: 'json',
              success: function(response) {
                if (response.success) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Precinct deleted successfully!',
                    timer: 1500,
                    showConfirmButton: false
                  }).then(() => location.reload());
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message
                  });
                }
              },
              error: function(xhr) {
                console.error('Delete Error:', xhr.responseText);
                Swal.fire({
                  icon: 'error',
                  title: 'Error!',
                  text: 'Something went wrong. Please try again.'
                });
              }
            });
          }
        });
      });

    });
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