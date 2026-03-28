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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
  header("Location: precincts.php");
  exit();
}

// Load the precinct
$stmt = $pdo->prepare("
    SELECT p.*,
           c.college_name,   c.college_abbreviation,
           d.department_name,
           m.major_name,
           cm.campus_name    AS campus_type_name,
           esu.campus_name   AS esu_campus_name
    FROM precincts p
    LEFT JOIN colleges    c   ON p.college          = c.college_id
    LEFT JOIN departments d   ON p.department        = d.department_id
    LEFT JOIN majors      m   ON p.major_id          = m.major_id
    LEFT JOIN campuses    cm  ON p.type              = cm.campus_id
    LEFT JOIN campuses    esu ON p.college_external  = esu.campus_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$precinct = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$precinct) {
  header("Location: precincts.php");
  exit();
}

// Dropdowns
$colleges = $pdo->query("SELECT * FROM colleges ORDER BY college_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$departments = $pdo->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$courses  = $pdo->query("SELECT id, college_id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$majors   = $pdo->query("SELECT major_id, major_name, course_id FROM majors ORDER BY major_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$allCampuses = $pdo->query("SELECT campus_id, campus_name, parent_id, latitude, longitude FROM campuses ORDER BY campus_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$parentCampuses = array_filter($allCampuses, fn($c) => $c['parent_id'] === null);
$esuCampuses    = array_filter($allCampuses, fn($c) => $c['parent_id'] !== null);

$stmt = $pdo->query("SELECT coordinate_id, college_id, campus_id, latitude, longitude FROM college_coordinates");
$collegeCoordinates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Current elections for this precinct
$elStmt = $pdo->prepare("
    SELECT pe.election_name AS election_id, e.election_name AS election_label
    FROM precinct_elections pe
    JOIN elections e ON pe.election_name = e.id
    WHERE pe.precinct_id = ?
");
$elStmt->execute([$id]);
$currentElections = $elStmt->fetchAll(PDO::FETCH_ASSOC);
$currentElectionIds = array_column($currentElections, 'election_id');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin: Edit Precinct</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.9.55/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-image: url('../external/img/Peach Ash Grey Gradient Color and Style Video Background.png');
      background-repeat: no-repeat;
      background-size: cover;
    }

    #leaflet-map {
      height: 400px;
      width: 100%;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
    }

    #google-map iframe {
      width: 100%;
      height: 400px;
      border: 0;
      border-radius: 8px;
    }

    .card {
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
    }

    .form-label {
      font-weight: 500;
    }

    .election-checkboxes {
      max-height: 150px;
      overflow-y: auto;
      padding: 10px;
      border: 1px solid #dee2e6;
      border-radius: 5px;
    }

    .hint-badge {
      font-size: .72rem;
      color: #6c757d;
      display: block;
      margin-top: 2px;
    }
  </style>
</head>

<body>
  <div class="container-fluid p-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <a href="precincts.php" class="btn btn-outline-secondary btn-sm me-3">
            <i class="bi bi-arrow-left"></i> Back to Precincts
          </a>
          <h2 class="card-title mb-0 text-primary">
            <i class="mdi mdi-map-marker-outline"></i> Edit Precinct
          </h2>
        </div>

        <div class="alert alert-info" role="alert">
          <i class="bi bi-info-circle-fill me-1"></i>
          Fields marked with <strong>Current:</strong> show the saved value. The precinct name will
          re-generate automatically when you change any of its source fields.
        </div>

        <form id="editPrecinctForm" action="processes/precincts/update.php" method="POST">
          <input type="hidden" name="precinct_id" value="<?= $id ?>">

          <div class="row d-flex justify-content-center align-items-start">

            <!-- ── LEFT COLUMN ─────────────────────────────────────── -->
            <div class="col-lg-6">

              <!-- Precinct Name (read-only, auto-generated) -->
              <div class="mb-3">
                <label for="name" class="form-label">
                  <i class="mdi mdi-label-outline me-1"></i>Precinct Name
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['name']) ?></small>
                <input type="text" class="form-control" id="name" name="name"
                  value="<?= htmlspecialchars($precinct['name']) ?>"
                  placeholder="Auto-generated" readonly required>
              </div>

              <!-- Election -->
              <div class="mb-3">
                <label class="form-label">
                  <i class="mdi mdi-vote-outline me-1"></i>Select Election for Precinct:
                </label>
                <?php if (!empty($currentElections)): ?>
                  <small class="hint-badge">
                    Current:
                    <?= implode(', ', array_column($currentElections, 'election_label')) ?>
                  </small>
                <?php endif; ?>
                <div class="election-checkboxes bg-light p-3 rounded" id="electionCheckboxContainer">
                  <p class="text-muted mb-0 small">Loading elections…</p>
                </div>
              </div>

              <!-- Max Capacity -->
              <div class="mb-3">
                <label for="maxCapacity" class="form-label">Max Capacity</label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['max_capacity']) ?></small>
                <input type="number" class="form-control" id="maxCapacity" name="max_capacity"
                  value="<?= htmlspecialchars($precinct['max_capacity']) ?>"
                  placeholder="Enter max capacity" required>
              </div>

              <!-- Location -->
              <div class="mb-3">
                <label for="precinctLocation" class="form-label">Location</label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['location']) ?></small>
                <input type="text" class="form-control" id="precinctLocation" name="location"
                  value="<?= htmlspecialchars($precinct['location']) ?>"
                  placeholder="e.g. WMSU Social Hall" required>
              </div>

              <!-- Coordinates -->
              <div class="alert alert-primary" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                Click on the map to update coordinates, or edit the fields below directly.
              </div>
              <div class="mb-3">
                <label for="xInput" class="form-label">X (Longitude)</label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['longitude'] ?? '') ?></small>
                <input type="text" id="xInput" class="form-control" name="longitude"
                  value="<?= htmlspecialchars($precinct['longitude'] ?? '') ?>"
                  placeholder="Longitude">
              </div>
              <div class="mb-3">
                <label for="yInput" class="form-label">Y (Latitude)</label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['latitude'] ?? '') ?></small>
                <input type="text" id="yInput" class="form-control" name="latitude"
                  value="<?= htmlspecialchars($precinct['latitude'] ?? '') ?>"
                  placeholder="Latitude">
              </div>

              <!-- College -->
              <div class="mb-3">
                <label for="college_type" class="form-label">
                  <i class="mdi mdi-school-outline me-1"></i>College
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['college_name'] ?? 'N/A') ?></small>
                <select class="form-select" id="college_type" name="college" required>
                  <option value="" disabled <?= !$precinct['college'] ? 'selected' : '' ?>>Select College</option>
                  <?php foreach ($colleges as $col): ?>
                    <option value="<?= $col['college_id'] ?>"
                      <?= $precinct['college'] == $col['college_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($col['college_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Department -->
              <div class="mb-3">
                <label for="department" class="form-label">
                  <i class="mdi mdi-domain me-1"></i>Department
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['department_name'] ?? 'N/A') ?></small>
                <select class="form-select" id="department" name="department" required>
                  <option value="" disabled>Select Department</option>
                  <!-- Populated by JS on load -->
                </select>
              </div>

              <!-- Major -->
              <div class="mb-3" id="major-container" style="display:none;">
                <label for="major" class="form-label">
                  <i class="mdi mdi-school-outline me-1"></i>Major
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['major_name'] ?? 'N/A') ?></small>
                <select class="form-select" id="major" name="major">
                  <option value="">None</option>
                </select>
              </div>

              <!-- Campus Type -->
              <div class="mb-3">
                <label for="campus_type" class="form-label">
                  <i class="mdi mdi-format-list-bulleted-type me-1"></i>Campus Type
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['campus_type_name'] ?? 'N/A') ?></small>
                <select class="form-select" id="campus_type" name="type" required>
                  <option value="" disabled>Select Campus Type</option>
                  <?php foreach ($parentCampuses as $parent): ?>
                    <option value="<?= $parent['campus_id'] ?>"
                      <?= $precinct['type'] == $parent['campus_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($parent['campus_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- ESU Location -->
              <div class="mb-3" id="campus-location-container"
                style="display:<?= !empty($precinct['college_external']) ? 'block' : 'none' ?>;">
                <label for="college_external" class="form-label">
                  <i class="mdi mdi-domain me-1"></i>Campus Location (ESU)
                </label>
                <small class="hint-badge">Current: <?= htmlspecialchars($precinct['esu_campus_name'] ?? 'N/A') ?></small>
                <select class="form-select" id="college_external" name="college_external">
                  <option value="" disabled selected>Select ESU Campus</option>
                  <?php foreach ($esuCampuses as $campus): ?>
                    <option value="<?= $campus['campus_id'] ?>"
                      <?= $precinct['college_external'] == $campus['campus_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($campus['campus_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="d-flex justify-content-between gap-2 mt-4">
                <a href="precincts.php" class="btn btn-secondary w-50">
                  <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <button type="button" class="btn btn-primary w-50" id="submitForm">
                  <i class="mdi mdi-content-save me-1"></i> Save Changes
                </button>
              </div>
            </div>

            <!-- ── RIGHT COLUMN: MAP ───────────────────────────────── -->
            <div class="col-lg-6">
              <div class="mb-3">
                <label class="form-label">
                  <i class="mdi mdi-map-search-outline me-1"></i>Map Selection
                </label>
                <div id="leaflet-map" class="w-100" style="height:400px; margin-top:20px;"></div>
                <div id="google-map" style="margin-top:20px;">
                  <iframe id="googleMapIframe"
                    src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d990!2d<?= $precinct['longitude'] ?? '122.063213' ?>!3d<?= $precinct['latitude'] ?? '6.912972' ?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1"
                    style="border:0; width:100%; height:400px;"
                    allowfullscreen loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                  </iframe>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Hidden: saved values passed to JS for pre-selection -->
  <script>
    // ── Data from PHP ────────────────────────────────────────────────────────
    const savedPrecinctId = <?= $id ?>;
    const savedCollegeId = <?= (int)($precinct['college'] ?? 0) ?>;
    const savedDepartmentId = <?= (int)($precinct['department'] ?? 0) ?>;
    const savedMajorId = <?= (int)($precinct['major_id'] ?? 0) ?>;
    const savedCampusType = <?= (int)($precinct['type'] ?? 0) ?>;
    const savedEsuId = <?= (int)($precinct['college_external'] ?? 0) ?>;
    const savedElectionIds = <?= json_encode(array_map('intval', $currentElectionIds)) ?>;
    const savedLat = <?= json_encode($precinct['latitude']  ?? null) ?>;
    const savedLng = <?= json_encode($precinct['longitude'] ?? null) ?>;

    const allDepartments = <?= json_encode($departments) ?>;
    const allCourses = <?= json_encode($courses) ?>;
    const allMajors = <?= json_encode($majors) ?>;
    const allCampuses = <?= json_encode(array_values($allCampuses)) ?>;
    const esuCampuses = <?= json_encode(array_values($esuCampuses)) ?>;
    const colleges = <?= json_encode($colleges, JSON_NUMERIC_CHECK) ?>;
    const collegeCoordinates = <?= json_encode($collegeCoordinates) ?>;

    const collegeAbbreviations = {};
    colleges.forEach(c => {
      collegeAbbreviations[c.college_id] = c.college_abbreviation;
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // ── MAP ───────────────────────────────────────────────────────────────────────
    let map, marker;

    function initMap() {
      const lat = parseFloat(savedLat) || 6.912972;
      const lng = parseFloat(savedLng) || 122.063213;

      map = L.map('leaflet-map').setView([lat, lng], 17);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Place existing marker
      if (savedLat && savedLng) {
        marker = L.marker([lat, lng]).addTo(map);
      }

      map.on('click', e => updateCoordinates(e.latlng.lat, e.latlng.lng));
    }

    function updateCoordinates(lat, lng) {
      document.getElementById('xInput').value = lng.toFixed(6);
      document.getElementById('yInput').value = lat.toFixed(6);
      if (marker) marker.remove();
      marker = L.marker([lat, lng]).addTo(map);
      map.setView([lat, lng], 17);
      document.getElementById('googleMapIframe').src =
        `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
    }

    function setCoordinatesByCampus(campusId, collegeId = null) {
      let lat = null,
        lng = null;
      if (collegeId) {
        const coord = collegeCoordinates.find(c =>
          parseInt(c.campus_id) === parseInt(campusId) &&
          parseInt(c.college_id) === parseInt(collegeId)
        );
        if (coord) {
          lat = coord.latitude;
          lng = coord.longitude;
        }
      }
      if (!lat) {
        const campus = allCampuses.find(c => parseInt(c.campus_id) === parseInt(campusId));
        if (campus) {
          lat = campus.latitude;
          lng = campus.longitude;
        }
      }
      if (lat && lng) updateCoordinates(parseFloat(lat), parseFloat(lng));
    }

    // ── PRECINCT NAME GENERATOR ───────────────────────────────────────────────────

    async function fetchElectionData() {
      try {
        const r = await fetch('processes/precincts/fetch_elections.php');
        const d = await r.json();
        return d.elections.map(e => ({
          id: parseInt(e.id),
          school_year_start: e.school_year_start,
          school_year_end: e.school_year_end,
          election_name: e.election_name,
          semester: e.semester,
          location: e.location
        }));
      } catch {
        return [];
      }
    }

    async function fetchExistingPrecincts() {
      try {
        const r = await fetch('processes/precincts/fetch-existing-precincts.php');
        const d = await r.json();
        return Array.isArray(d) ? d : [];
      } catch {
        return [];
      }
    }

    async function generatePrecinctName() {
      const collegeId = document.getElementById('college_type').value;
      const abbr = collegeAbbreviations[collegeId] || 'UNKNOWN';
      const nameInput = document.getElementById('name');

      if (!collegeId) {
        nameInput.value = '';
        return;
      }

      const selectedRadio = document.querySelector('input[name="election"]:checked');
      if (!selectedRadio) {
        nameInput.value = '';
        return;
      }

      const selectedElectionId = parseInt(selectedRadio.value);
      const selectedElectionName = selectedRadio.dataset.electionName;
      const elections = await fetchElectionData();
      const election = elections.find(e => e.id === selectedElectionId);
      if (!election) {
        nameInput.value = '';
        return;
      }

      const {
        school_year_start,
        school_year_end,
        semester
      } = election;
      const cleanSemester = semester.replace(/semester/i, '').trim();
      const rawLocation = document.getElementById('precinctLocation').value.trim() || election.location || 'NA';
      const cleanLocation = rawLocation.replace(/\s+/g, '').replace(/[^\w\-]/g, '');
      const baseName = `${school_year_start}-${school_year_end} ${cleanSemester}_${abbr}_${selectedElectionName}_${cleanLocation}`;

      // Exclude the current precinct from the numbering check
      const existing = (await fetchExistingPrecincts()).filter(n => {
        // Each item may be a string name or object; handle both
        const nm = typeof n === 'string' ? n : (n.name || '');
        return nm !== <?= json_encode($precinct['name']) ?>;
      });

      let highest = 0;
      existing.forEach(n => {
        const nm = typeof n === 'string' ? n : (n.name || '');
        if (nm.startsWith(baseName + '-')) {
          const match = nm.match(/-(\d+)$/);
          if (match) {
            const num = parseInt(match[1]);
            if (num > highest) highest = num;
          }
        }
      });

      nameInput.value = `${baseName}-${highest + 1}`;
    }

    // ── DEPARTMENT CASCADE ────────────────────────────────────────────────────────

    function populateDepartments(collegeId, selectedDeptId = null) {
      const deptSel = document.getElementById('department');
      deptSel.innerHTML = '<option value="" disabled selected>Select Department</option>';
      allDepartments
        .filter(d => parseInt(d.college_id) === parseInt(collegeId))
        .forEach(d => {
          const o = new Option(d.department_name, d.department_id);
          if (parseInt(d.department_id) === parseInt(selectedDeptId)) o.selected = true;
          deptSel.add(o);
        });
    }

    function populateMajors(deptId, selectedMajorId = null) {
      const majorSel = document.getElementById('major');
      const majorWrap = document.getElementById('major-container');
      const dept = allDepartments.find(d => parseInt(d.department_id) === parseInt(deptId));
      if (!dept) {
        majorWrap.style.display = 'none';
        return;
      }

      const deptCourses = allCourses.filter(c => c.course_code === dept.department_name);
      const courseIds = deptCourses.map(c => c.id);
      const filtered = allMajors.filter(m => courseIds.includes(parseInt(m.course_id)));

      majorSel.innerHTML = '<option value="">None [DO NOT SELECT ANYTHING BELOW IF NONE]</option>';
      if (filtered.length > 0) {
        filtered.forEach(m => {
          const o = new Option(m.major_name, m.major_id);
          if (parseInt(m.major_id) === parseInt(selectedMajorId)) o.selected = true;
          majorSel.add(o);
        });
        majorWrap.style.display = 'block';
      } else {
        majorWrap.style.display = 'none';
      }
    }

    // ── INIT ──────────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function() {

      initMap();

      // Pre-populate departments for saved college
      if (savedCollegeId) {
        populateDepartments(savedCollegeId, savedDepartmentId);
      }

      // Pre-populate majors for saved department
      if (savedDepartmentId) {
        populateMajors(savedDepartmentId, savedMajorId);
      }

      // Load elections and pre-check saved ones
      fetch('processes/precincts/fetch_elections.php')
        .then(r => r.json())
        .then(data => {
          const container = document.getElementById('electionCheckboxContainer');
          container.innerHTML = '';
          if (!data.elections.length) {
            container.innerHTML = '<p class="text-muted mb-0 small">No ongoing elections found.</p>';
            return;
          }
          data.elections.forEach((election, index) => {
            const uid = `election_${index + 1}`;
            const checked = savedElectionIds.includes(parseInt(election.id)) ? 'checked' : '';
            container.insertAdjacentHTML('beforeend', `
                    <div class="form-check">
                        <input type="radio" class="form-check-input election-radio"
                               name="election" id="${uid}"
                               data-election-name="${election.election_name}"
                               value="${election.id}" ${checked}>
                        <label class="form-check-label" for="${uid}">
                            ${election.election_name}
                        </label>
                    </div>`);
          });

          // Wire up re-generation on election change
          document.querySelectorAll('.election-radio').forEach(el => {
            el.addEventListener('change', generatePrecinctName);
          });

          // Trigger initial name generation if election and college already set
          if (savedCollegeId && savedElectionIds.length) {
            generatePrecinctName();
          }
        })
        .catch(() => {
          document.getElementById('electionCheckboxContainer').innerHTML =
            '<p class="text-danger small mb-0">Failed to load elections.</p>';
        });

      // ── College change ──
      document.getElementById('college_type').addEventListener('change', function() {
        populateDepartments(this.value);
        // Reset department and major
        document.getElementById('department').value = '';
        document.getElementById('major-container').style.display = 'none';
        setCoordinatesByCampus(document.getElementById('campus_type').value, this.value);
        generatePrecinctName();
      });

      // ── Department change ──
      document.getElementById('department').addEventListener('change', function() {
        populateMajors(this.value);
        generatePrecinctName();
      });

      // ── Location change ── (re-gen name)
      document.getElementById('precinctLocation').addEventListener('input', generatePrecinctName);

      // ── Campus type change ── (show/hide ESU)
      document.getElementById('campus_type').addEventListener('change', function() {
        const campusId = this.value;
        const collegeId = document.getElementById('college_type').value || null;
        const children = esuCampuses.filter(c => parseInt(c.parent_id) === parseInt(campusId));
        const esuWrap = document.getElementById('campus-location-container');
        const esuSel = document.getElementById('college_external');

        esuSel.innerHTML = '<option value="" disabled selected>Select ESU Campus</option>';
        if (children.length > 0) {
          children.forEach(c => esuSel.add(new Option(c.campus_name, c.campus_id)));
          esuWrap.style.display = 'block';
          // Re-select saved ESU if still valid
          if (savedEsuId) esuSel.value = savedEsuId;
        } else {
          esuWrap.style.display = 'none';
        }

        setCoordinatesByCampus(campusId, collegeId);
      });

      // ── Submit ────────────────────────────────────────────────────────────────
      document.getElementById('submitForm').addEventListener('click', async function(e) {
        e.preventDefault();
        const form = document.getElementById('editPrecinctForm');
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        const selectedRadio = document.querySelector('input[name="election"]:checked');
        if (!selectedRadio) {
          Swal.fire({
            icon: 'warning',
            title: 'No Election Selected',
            text: 'Please select an election.',
            confirmButtonColor: '#f0ad4e'
          });
          return;
        }

        try {
          const r = await fetch('processes/precincts/update.php', {
            method: 'POST',
            body: new FormData(form)
          });
          const result = await r.json();
          Swal.fire({
            icon: result.success ? 'success' : 'error',
            title: result.success ? 'Updated!' : 'Error',
            text: result.message,
            timer: result.success ? 2000 : undefined,
            showConfirmButton: !result.success
          }).then(() => {
            if (result.success) window.location.href = 'precincts.php';
          });
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong.'
          });
        }
      });

    });
  </script>
</body>

</html>