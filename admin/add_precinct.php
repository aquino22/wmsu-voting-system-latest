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

// 1️⃣ Colleges
$colleges = $pdo->query("
    SELECT *
    FROM colleges
    ORDER BY college_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2️⃣ Fetch all departments
$departments = $pdo->query("
    SELECT department_id, department_name, college_id
    FROM departments
    ORDER BY department_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$courses = $pdo->query("
    SELECT id, college_id, course_name, course_code
    FROM courses
    ORDER BY course_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$majors = $pdo->query("
    SELECT major_id, major_name, course_id
    FROM majors
    ORDER BY major_name ASC
")->fetchAll(PDO::FETCH_ASSOC);


$allCampuses = $pdo->query("
    SELECT campus_id, campus_name, parent_id, latitude, longitude
    FROM campuses
    ORDER BY campus_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$campusTree = [];

$parentCampuses = $pdo->query("
    SELECT campus_id, campus_name
    FROM campuses
    WHERE parent_id IS NULL
    ORDER BY campus_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Step 1: Add parent campuses (parent_id = NULL)
foreach ($allCampuses as $campus) {
  if ($campus['parent_id'] === null) {
    $campusTree[$campus['campus_id']] = [
      'name' => $campus['campus_name'],
      'children' => []
    ];
  }
}

// Step 2: Assign children to their parent
foreach ($allCampuses as $campus) {
  if ($campus['parent_id'] !== null) {
    $parentId = $campus['parent_id'];
    if (isset($campusTree[$parentId])) {
      $campusTree[$parentId]['children'][] = [
        'campus_id' => $campus['campus_id'],
        'name' => $campus['campus_name']
      ];
    }
  }
}

$esuCampuses = $pdo->query("
    SELECT campus_id, campus_name, parent_id
    FROM campuses
    WHERE parent_id IS NOT NULL
    ORDER BY campus_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        coordinate_id,
        college_id,
        campus_id,
        latitude,
        longitude
    FROM college_coordinates
");

$collegeCoordinates = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>WMSU i-Elect Admin: Adding Precinct</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Material Design Icons CSS -->
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.9.55/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');
      background-repeat: no-repeat;
      background-size: cover;
    }

    #map-container {
      display: flex;
      height: 400px;
      width: 100%;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      /* Rounded corners */
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      /* Subtle shadow */
      overflow: hidden;
      /* Ensure no overflow */
    }

    #leaflet-map,
    #google-map {
      height: 100%;
      width: 50%;
    }

    #google-map iframe {
      width: 100%;
      height: 100%;
      border: 0;
    }

    .card {
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      /* Card shadow */
    }

    .btn-primary {
      transition: all 0.3s ease;
      /* Smooth button transitions */
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      /* Slight lift on hover */
    }

    .form-label {
      font-weight: 500;
      /* Slightly bolder labels */
    }

    .election-checkboxes {
      max-height: 150px;
      overflow-y: auto;
      /* Scrollable if many elections */
      padding: 10px;
      border: 1px solid #dee2e6;
      border-radius: 5px;
    }
  </style>
</head>

<body>
  <div class="container-fluid p-3 ">
    <!-- Wrap form in a Bootstrap card -->
    <div class="card 3">
      <div class="card-body">
        <h2 class="card-title mb-4 text-primary text-center">
          <i class="mdi mdi-map-marker"></i> Adding Precinct for Elections
        </h2>

        <div class="alert alert-warning text-center" role="alert">
          <h6><i class="bi bi-exclamation-triangle-fill"></i> If nothing loads, please reload the page or wait for a while.</h6>
        </div>

        <form id="addPrecinctForm" action="processes/precincts/add.php">
          <div class="row d-flex justify-content-center align-items-start">
            <div class="col">
              <div class="mb-3">
                <label for="name" class="form-label">
                  <i class="mdi mdi-label-outline me-1"></i>Precinct Name
                </label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter precinct name" required readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">
                  <i class="mdi mdi-vote-outline me-1"></i>Select Election for Precinct Below:
                </label>
                <div class="election-checkboxes bg-light p-3 rounded" id="electionCheckboxContainer">
                  <!-- Checkboxes will be populated here -->
                </div>
              </div>

              <div class="mb-3">
                <label for="location" class="form-label">Max Capacity: </label>
                <input type="text" class="form-control form-control-sm" id="maxCapacity" placeholder="Enter max capacity of available voters' in that precinct." name="max_capacity" required>
              </div>

              <div class="mb-3">
                <label for="location" class="form-label">Location: </label>
                <input type="text" class="form-control form-control-sm" id="precinctLocation" placeholder="Enter location or name here: e.g. (WMSU Social Hall)" name="location" required>
              </div>
              <div class="alert alert-primary" role="alert">

                <p> <i class="bi bi-info-circle-fill"></i>
                  The precinct coordinates are automatically generated based on coordinates
                  added on <a href="academic_info.php">'Academic Info'</a> tab yet it can still be changed
                  by clicking on the top map on the right.</p>
              </div>


              <div id="coordinates" class="mb-3">
                <label for="xInput" class="form-label">X (Longitude):</label>
                <input type="text" id="xInput" class="form-control form-control-sm" placeholder="Longitude" name="longitude">
                <br>
                <label for="yInput" class="form-label">Y (Latitude):</label>
                <input type="text" id="yInput" class="form-control form-control-sm" placeholder="Latitude" name="latitude">

              </div>

              <div class="mb-3">
                <label for="college_type" class="form-label">
                  <i class="mdi mdi-school-outline me-1"></i>College
                </label>
                <select class="form-select" id="college_type" name="college" required>
                  <option value="" disabled selected>Select College</option>
                  <?php foreach ($colleges as $college): ?>
                    <option value="<?= $college['college_id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="department" class="form-label">
                  <i class="mdi mdi-domain me-1"></i>Department
                </label>
                <select class="form-select" id="department" name="department" required>
                  <option value="" disabled selected>Select Department</option>
                </select>
              </div>

              <div class="mb-3" id="major-container" style="display:none;">
                <label for="major" class="form-label">
                  <i class="mdi mdi-school-outline me-1"></i>Major
                </label>
                <select class="form-select" id="major" name="major">
                  <option value="" disabled selected>Select Major</option>
                </select>
              </div>

              <?php
              // Fetch only parent campuses
              $parentCampuses = $pdo->query("
    SELECT campus_id, campus_name
    FROM campuses
    WHERE parent_id IS NULL
    ORDER BY campus_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
              ?>

              <div class="mb-3">
                <label for="campus_type" class="form-label">
                  <i class="mdi mdi-format-list-bulleted-type me-1"></i>Campus Type
                </label>
                <select class="form-select" id="campus_type" name="type" required>
                  <option value="" disabled selected>Select Campus Type</option>
                  <?php foreach ($parentCampuses as $parent): ?>
                    <option value="<?= $parent['campus_id'] ?>">
                      <?= htmlspecialchars($parent['campus_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3" id="campus-location-container" style="display:none;">
                <label for="college_external" class="form-label">
                  <i class="mdi mdi-domain me-1"></i>Campus Location (ESU Campus)
                </label>
                <select class="form-select" id="college_external" name="college_external">
                  <option value="" disabled selected>Select ESU Campus</option>
                  <?php foreach ($esuCampuses as $campus): ?>
                    <option value="<?= $campus['campus_id'] ?>"><?= htmlspecialchars($campus['campus_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>





              <div class="mb-3" style="display:none">
                <label for="assignment_status" class="form-label">Assignment Status</label>
                <select class="form-select" id="assignment_status" name="assignment_status" required>
                  <option value disabled selected>Select Status</option>
                  <option value="Assigned">Assigned</option>
                  <option value="Unassigned" selected>Unassigned</option>
                </select>
              </div>

              <div class="mb-3" style="display:none">
                <label for="occupied_status" class="form-label">Occupied Status</label>
                <select class="form-select" id="occupied_status" name="occupied_status" required>
                  <option value disabled selected>Select Status</option>
                  <option value="Occupied">Occupied</option>
                  <option value="Unoccupied" selected>Unoccupied</option>
                </select>
              </div>



              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-primary w-100" id="submitForm">
                  <i class="mdi mdi-plus-circle me-1"></i> Save Changes
                </button>

              </div>
            </div>
            <div class="col">
              <div class="mb-3">
                <label class="form-label">
                  <i class="mdi mdi-map-search-outline me-1"></i>Map Selection
                </label>
                <!-- Leaflet Map -->
                <div id="leaflet-map" class="w-100" style="height: 400px; margin-top: 20px;"></div>

                <!-- Google Maps Iframe -->
                <div id="google-map" style="margin-top: 20px;">
                  <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d990.2030970492448!2d122.06323088460613!3d6.913022088239877!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1746872885316!5m2!1sen!2sph"
                    style="border:0; width: 1216px; height: 400px;"
                    allowfullscreen=""
                    loading="lazy"
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>






  <script>
    const colleges = <?= json_encode($colleges, JSON_NUMERIC_CHECK); ?>;
    const campuses = <?= json_encode($allCampuses) ?>;
    const collegeCoordinates = <?= json_encode($collegeCoordinates) ?>;

    console.log(colleges);
    console.log(campuses);
    console.log(collegeCoordinates);

    // GLOBAL ABBREVIATION MAP
    const collegeAbbreviations = {};
    colleges.forEach(c => {
      collegeAbbreviations[c.college_id] = c.college_abbreviation;
    });

    let map;
    let marker;

    function initializeMap() {
      if (map) return;

      map = L.map('leaflet-map').setView([6.912972, 122.063213], 17);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      map.on('click', (e) => {
        const {
          lat,
          lng
        } = e.latlng;
        updateCoordinates(lat, lng);
      });
    }

    function updateCoordinates(lat, lng) {

      document.getElementById('xInput').value = lng.toFixed(6);
      document.getElementById('yInput').value = lat.toFixed(6);

      if (marker) marker.remove();

      marker = L.marker([lat, lng]).addTo(map);
      map.setView([lat, lng], 17);

      const googleMapIframe = document.querySelector('#google-map iframe');

      if (googleMapIframe) {
        googleMapIframe.src =
          `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
      }
    }


    // FETCH HELPERS
    async function fetchElectionData() {
      try {

        const response = await fetch('processes/precincts/fetch_elections.php');
        if (!response.ok) throw new Error('Failed to fetch election data');

        const data = await response.json();

        return data.elections.map(e => ({
          id: parseInt(e.id),
          school_year_start: e.school_year_start,
          school_year_end: e.school_year_end,
          election_name: e.election_name,
          semester: e.semester,
          location: e.location
        }));

      } catch (error) {

        console.error(error);

        return [{
          id: 0,
          school_year_start: '0000',
          school_year_end: '0000',
          election_name: 'UNKNOWN',
          semester: '0th',
          location: 'N/A'
        }];
      }
    }

    async function fetchExistingPrecincts() {
      try {

        const response = await fetch('processes/precincts/fetch-existing-precincts.php');

        if (!response.ok) throw new Error();

        const data = await response.json();

        return Array.isArray(data) ? data : [];

      } catch {

        return [];
      }
    }


    // GENERATE PRECINCT NAME
    async function generatePrecinctName(collegeId, abbr) {

      const precinctNameInput = document.getElementById('name');

      if (!collegeId) {
        precinctNameInput.value = '';
        return;
      }

      const selectedRadio = document.querySelector('input[name="election"]:checked');

      if (!selectedRadio) {
        precinctNameInput.value = '';
        console.error('No election selected');
        return;
      }

      const selectedElectionId = parseInt(selectedRadio.value);

      const selectedElectionName = selectedRadio.dataset.electionName;

      const elections = await fetchElectionData();

      const election = elections.find(e => e.id === selectedElectionId);

      if (!election) {
        precinctNameInput.value = '';
        console.error('Election not found');
        return;
      }

      const {
        school_year_start,
        school_year_end,
        semester,
        location: electionLocation
      } = election;

      const cleanSemester = semester.replace(/semester/i, '').trim();

      const locationInput = document.getElementById('precinctLocation');

      const rawLocation = locationInput?.value?.trim() || electionLocation || 'NA';

      const cleanLocation = rawLocation.replace(/\s+/g, '').replace(/[^\w\-]/g, '');

      const basePrecinctName =
        `${school_year_start}-${school_year_end} ${cleanSemester}_${abbr}_${selectedElectionName}_${cleanLocation}`;

      const existingPrecincts = await fetchExistingPrecincts();

      let highestNumber = 0;

      existingPrecincts.forEach(name => {

        if (typeof name === 'string' && name.startsWith(basePrecinctName + '-')) {

          const match = name.match(/-(\d+)$/);

          if (match) {
            const num = parseInt(match[1]);
            if (num > highestNumber) highestNumber = num;
          }

        }

      });

      precinctNameInput.value = `${basePrecinctName}-${highestNumber + 1}`;
    }



    // MAIN INITIALIZATION
    document.addEventListener('DOMContentLoaded', () => {

      initializeMap();

      const campusSelect = document.getElementById('campus_type');
      const collegeSelect = document.getElementById('college_type');
      const departmentSelect = document.getElementById('department');
      const campusLocationContainer = document.getElementById('campus-location-container');
      const locationInput = document.getElementById('precinctLocation');


      // COLLEGE CHANGE
      collegeSelect.addEventListener('change', (e) => {

        const collegeId = e.target.value;
        const abbr = collegeAbbreviations[collegeId] || 'UNKNOWN';

        generatePrecinctName(collegeId, abbr);

      });


      // CAMPUS COORDINATES
      function setCoordinates(campusId, collegeId = null) {

        let lat = null;
        let lng = null;

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

        if (lat === null || lng === null) {

          const campus = campuses.find(c =>
            parseInt(c.campus_id) === parseInt(campusId)
          );

          if (campus) {
            lat = campus.latitude;
            lng = campus.longitude;
          }

        }

        if (lat && lng) {
          updateCoordinates(parseFloat(lat), parseFloat(lng));
        }
      }


      campusSelect.addEventListener('change', function() {

        const campusId = this.value;
        const collegeId = collegeSelect.value || null;

        setCoordinates(campusId, collegeId);

      });


      collegeSelect.addEventListener('change', function() {

        const campusId = campusSelect.value;

        if (!campusId) return;

        setCoordinates(campusId, this.value);

      });



      // FORM SUBMIT
      document.getElementById('submitForm').addEventListener('click', async (event) => {

        event.preventDefault();

        const form = document.getElementById('addPrecinctForm');

        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        const formData = new FormData(form);

        try {

          const response = await fetch('processes/precincts/add.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          Swal.fire({
            icon: result.status === 'success' ? 'success' : 'error',
            title: result.status === 'success' ? 'Success!' : 'Oops!',
            text: result.message,
            timer: 3000
          });

          if (result.status === 'success') {

            form.reset();
            campusLocationContainer.style.display = 'none';

            if (marker) marker.remove();

            document.getElementById('xInput').value = '';
            document.getElementById('yInput').value = '';

            departmentSelect.innerHTML =
              '<option value="" disabled selected>Select Department</option>';

          }

        } catch (error) {

          console.error(error);

          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to submit form'
          });

        }

      });



      // LOAD ELECTIONS
      const container = document.getElementById('electionCheckboxContainer');

      fetch('processes/precincts/fetch_elections.php')
        .then(r => r.json())
        .then(data => {

          if (!data.elections.length) {
            container.innerHTML = '<p class="text-muted">No ongoing elections found.</p>';
            return;
          }

          data.elections.forEach((election, index) => {

            const id = `election_${index + 1}`;

            container.insertAdjacentHTML('beforeend', `
          <div class="form-check">
            <input type="radio"
                   class="form-check-input"
                   name="election"
                   id="${id}"
                   data-election-name="${election.election_name}"
                   value="${election.id}">
            <label class="form-check-label" for="${id}">
              ${election.election_name}
            </label>
          </div>
        `);

          });

        })
        .catch(() => {

          container.innerHTML = '<p class="text-danger">Failed to load elections.</p>';

        });


    });
  </script>
</body>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const collegeSelect = document.getElementById('college_type');
    const departmentSelect = document.getElementById('department');

    // All departments from PHP
    const allDepartments = <?= json_encode($departments) ?>; // Make sure $departments is fetched in PHP

    collegeSelect.addEventListener('change', function() {
      const collegeId = parseInt(this.value);

      // Filter departments for selected college
      const filteredDepartments = allDepartments.filter(d => parseInt(d.college_id) === collegeId);

      // Reset department dropdown
      departmentSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';

      // Populate filtered departments
      filteredDepartments.forEach(d => {
        const option = document.createElement('option');
        option.value = d.department_id;
        option.textContent = d.department_name;
        departmentSelect.appendChild(option);
      });
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const parentSelect = document.getElementById('campus_type');
    const childContainer = document.getElementById('campus-location-container');
    const childSelect = document.getElementById('college_external');

    const childCampuses = <?= json_encode($esuCampuses) ?>;

    console.log("Child container:", childContainer);

    console.log("childCampuses container:", childCampuses);

    parentSelect.addEventListener('change', function() {
      const parentId = parseInt(this.value);

      const children = childCampuses.filter(c => parseInt(c.parent_id) === parentId);

      // Clear previous options
      childSelect.innerHTML = '<option value="" disabled selected>Select Campus Location</option>';

      if (children.length > 0) {
        children.forEach(c => {
          const option = document.createElement('option');
          option.value = c.campus_id;
          option.textContent = c.campus_name;
          childSelect.appendChild(option);
        });
        childContainer.style.display = 'block';
        childSelect.value = ""; // reset selection
      } else {
        childContainer.style.display = 'none';
      }
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department');
    const majorContainer = document.getElementById('major-container');
    const majorSelect = document.getElementById('major');

    // PHP arrays
    const allDepartments = <?= json_encode($departments) ?>;
    const allCourses = <?= json_encode($courses) ?>;
    const allMajors = <?= json_encode($majors) ?>;

    departmentSelect.addEventListener('change', function() {
      const deptId = parseInt(this.value);

      // Find selected department
      const dept = allDepartments.find(d => parseInt(d.department_id) === deptId);
      if (!dept) return;

      // Step 1: Find courses where course_code === department_name
      const deptCourses = allCourses.filter(c => c.course_code === dept.department_name);

      // Step 2: Get course IDs
      const deptCourseIds = deptCourses.map(c => c.id);

      // Step 3: Filter majors for these courses
      const filteredMajors = allMajors.filter(m => deptCourseIds.includes(m.course_id));

      // Reset major dropdown
      majorSelect.innerHTML = '<option value="" disabled selected>Select Major [DO NOT SELECT ANYTHING BELOW IF NONE]</option>';

      if (filteredMajors.length > 0) {
        filteredMajors.forEach(m => {
          const option = document.createElement('option');
          option.value = m.major_id;
          option.textContent = m.major_name;
          majorSelect.appendChild(option);
        });
        majorContainer.style.display = 'block';
      } else {
        majorContainer.style.display = 'none';
      }
    });
  });
</script>


</html>