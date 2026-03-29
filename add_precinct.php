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
                <label for="location" class="form-label">Location: </label>
                <input type="text" class="form-control form-control-sm" id="precinctLocation" placeholder="Enter location or name here: e.g. (WMSU Social Hall)" name="location" required>
              </div>

              <div id="coordinates" class="mb-3">
                <label for="xInput" class="form-label">X (Longitude):</label>
                <input type="text" id="xInput" class="form-control form-control-sm" placeholder="Longitude" name="longitude">
                <br>
                <label for="yInput" class="form-label">Y (Latitude):</label>
                <input type="text" id="yInput" class="form-control form-control-sm" placeholder="Latitude" name="latitude">

              </div>




              <div class="mb-3">
                <label for="type" class="form-label">
                  <i class="mdi mdi-format-list-bulleted-type me-1"></i>Campus Type
                </label>
                <select class="form-select" id="campus_type" name="type" required>
                  <option value="" disabled selected>Select Type</option>
                  <option value="Main Campus">Main Campus</option>
                  <option value="WMSU ESU">WMSU ESU</option>
                </select>
              </div>

              <div class="mb-3" id="campus-location-container" style="display: none;">
                <label for="college_external" class="form-label">
                  <i class="mdi mdi-domain me-1"></i>Campus Location (ESU Campus)
                </label>
                <select class="form-select" id="college_external" name="college_external">
                  <option value="" disabled selected>Select College Location</option>
                  <option value="WMSU Curuan">WMSU Curuan</option>
                  <option value="WMSU Imelda">WMSU Imelda</option>
                  <option value="WMSU Siay">WMSU Siay</option>
                  <option value="WMSU Naga">WMSU Naga</option>
                  <option value="WMSU Molave">WMSU Molave</option>
                  <option value="WMSU Diplahan">WMSU Diplahan</option>
                  <option value="WMSU Olutanga">WMSU Olutanga</option>
                  <option value="WMSU Malangas">WMSU Malangas</option>
                  <option value="WMSU Ipil">WMSU Ipil</option>
                  <option value="WMSU Mabuhay">WMSU Mabuhay</option>
                  <option value="WMSU Pagadian">WMSU Pagadian</option>
                  <option value="WMSU Tungawan">WMSU Tungawan</option>
                  <option value="WMSU Alicia">WMSU Alicia</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="college" class="form-label">
                  <i class="mdi mdi-school-outline me-1"></i>College Type
                </label>
                <select class="form-select" id="college_type" name="college" required>
                  <option value="" disabled selected>Select College Type</option>
                  <option value="College of Law">College of Law</option>
                  <option value="College of Agriculture">College of Agriculture</option>
                  <option value="College of Liberal Arts">College of Liberal Arts</option>
                  <option value="College of Architecture">College of Architecture</option>
                  <option value="College of Nursing">College of Nursing</option>
                  <option value="College of Asian & Islamic Studies">College of Asian & Islamic Studies</option>
                  <option value="College of Computing Studies">College of Computing Studies</option>
                  <option value="College of Forestry & Environmental Studies">College of Forestry & Environmental Studies</option>
                  <option value="College of Criminal Justice Education">College of Criminal Justice Education</option>
                  <option value="College of Home Economics">College of Home Economics</option>
                  <option value="College of Engineering">College of Engineering</option>
                  <option value="College of Medicine">College of Medicine</option>
                  <option value="College of Public Administration & Development Studies">College of Public Administration & Development Studies</option>
                  <option value="College of Sports Science & Physical Education">College of Sports Science & Physical Education</option>
                  <option value="College of Science and Mathematics">College of Science and Mathematics</option>
                  <option value="College of Social Work & Community Development">College of Social Work & Community Development</option>
                  <option value="College of Teacher Education">College of Teacher Education</option>
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
    // Department options for each college
    const departmentData = {
      "College of Law": ["Law"],
      "College of Agriculture": [
        "Agriculture",
        "Food Technology",
        "Agribusiness",
        "Agricultural Technology",
        "Agronomy"
      ],
      "College of Liberal Arts": [
        "Accountancy",
        "History",
        "English",
        "Political Science",
        "Mass Communication - Journalism",
        "Mass Communication - Broadcasting",
        "Economics",
        "Psychology"
      ],
      "College of Architecture": ["Architecture"],
      "College of Nursing": ["Nursing"],
      "College of Asian & Islamic Studies": [
        "Asian Studies",
        "Islamic Studies"
      ],
      "College of Computing Studies": [
        "Computer Science",
        "Information Technology",
        "Computer Technology"
      ],
      "College of Forestry & Environmental Studies": [
        "Forestry",
        "Agroforestry",
        "Environmental Science"
      ],
      "College of Criminal Justice Education": ["Criminology"],
      "College of Home Economics": [
        "Home Economics",
        "Nutrition and Dietetics",
        "Hospitality Management"
      ],
      "College of Engineering": [
        "Agricultural and Biosystems",
        "Civil Engineering",
        "Computer Engineering",
        "Electrical Engineering",
        "Electronics Engineering",
        "Environmental Engineering",
        "Geodetic Engineering",
        "Industrial Engineering",
        "Mechanical Engineering",
        "Sanitary Engineering"
      ],
      "College of Medicine": ["Medicine"],
      "College of Public Administration & Development Studies": [
        "Public Administration"
      ],
      "College of Sports Science & Physical Education": [
        "Physical Education",
        "Exercise and Sports Sciences"
      ],
      "College of Science and Mathematics": [
        "Biology",
        "Chemistry",
        "Mathematics",
        "Physics",
        "Statistics"
      ],
      "College of Social Work & Community Development": [
        "Social Work",
        "Community Development"
      ],
      "College of Teacher Education": [
        "Culture and Arts Education",
        "Early Childhood Education",
        "Elementary Education",
        "Secondary Education",
        "Secondary Education major in English",
        "Secondary Education Major in Filipino",
        "Secondary Education Major in Mathematics",
        "Secondary Education Major in Sciences",
        "Secondary Education Major in Social Studies",
        "Secondary Education Major in Values Education",
        "Special Needs Education"
      ]
    };
    // Campus coordinates array


    const campusCoordinates = {
      'Central': [6.912972, 122.063213],
      'WMSU Curuan': [7.209669638907075, 122.23141380170503],
      'WMSU Imelda': [7.6500857237306, 122.946780424737818],
      'WMSU Siay': [7.711976062859293, 122.86550741336379],
      'WMSU Naga': [7.839055181448927, 122.71845020784252],
      'WMSU Molave': [7.567890, 122.678901],
      'WMSU Diplahan': [7.683730212241171, 122.98697974894604],
      'WMSU Olutanga': [7.309729036663526, 122.84521272401533],
      'WMSU Malangas': [7.631303710772468, 123.03521645508067],
      'WMSU Ipil': [7.78761386922412, 122.57855693730419],
      'WMSU Mabuhay': [7.411319342439962, 122.83731294266357],
      'WMSU Pagadian': [7.846914004702418, 123.44583052122903],
      'WMSU Tungawan': [7.60114052850201, 122.42490373581737],
      'WMSU Alicia': [7.5058181318319885, 122.94668581057246]
    };

    // College coordinates for Central campus
    const centralCollegeCoordinates = {
      'College of Law': [6.913468824762561, 122.06070841262144],
      'College of Agriculture': [6.913000, 122.063300],
      'College of Liberal Arts': [6.913518084831762, 122.06076473898845],
      'College of Architecture': [6.914531243601551, 122.06141249247219],
      'College of Nursing': [6.913508765367044, 122.06239552205487],
      'College of Asian & Islamic Studies': [6.912390682182722, 122.063229684925820],
      'College of Computing Studies': [6.9125105042518475, 122.06361592301529],
      'College of Forestry & Environmental Studies': [6.913709623848472, 122.06134711034687], //
      'College of Criminal Justice Education': [6.912867307557466, 122.06302181374697], //
      'College of Home Economics': [6.913707136966571, 122.06223458952678],
      'College of Engineering': [6.914016676371317, 122.06110202683602],
      'College of Medicine': [6.913116270895381, 122.06366688498277],
      'College of Public Administration & Development Studies': [6.913559513802672, 122.06054412404318], //
      'College of Sports Science & Physical Education': [6.912473561516037, 122.06032363058867], //
      'College of Science and Mathematics': [6.912976478663647, 122.06255779157827],
      'College of Social Work & Community Development': [6.912478551704376, 122.06315994747247],
      'College of Teacher Education': [6.912779919623977, 122.0613553737633]
    };

    // Initialize Leaflet map
    let map;
    let marker;

    function initializeMap() {
      if (map) return;
      map = L.map('leaflet-map').setView([6.912972, 122.063213], 17);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      map.on('click', (e) => {
        const {
          lat,
          lng
        } = e.latlng;
        updateCoordinates(lat, lng);
      });
    }

    // Function to update coordinates, Leaflet marker, and Google Maps iframe
    function updateCoordinates(lat, lng) {
      document.getElementById('xInput').value = lng.toFixed(6);
      document.getElementById('yInput').value = lat.toFixed(6);

      if (marker) marker.remove();
      marker = L.marker([lat, lng]).addTo(map);
      map.setView([lat, lng], 17);

      const googleMapIframe = document.querySelector('#google-map iframe');
      if (googleMapIframe) {
        googleMapIframe.src = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
      }
    }

    // Initialize map and event listeners
    document.addEventListener('DOMContentLoaded', () => {
      initializeMap();

      const campusTypeSelect = document.getElementById('campus_type');
      const campusLocationContainer = document.getElementById('campus-location-container');
      const campusLocationSelect = document.getElementById('college_external');
      const collegeSelect = document.getElementById('college_type');
      const departmentSelect = document.getElementById('department');

      const locationInput = document.getElementById('precinctLocation');
      const rawLocation = locationInput?.value?.trim() || 'NA';

      // Optional: remove spaces or special characters if needed
      const cleanLocation = rawLocation.replace(/\s+/g, '').replace(/[^\w\-]/g, '');

      console.log(collegeSelect);

      if (campusTypeSelect.value === 'Main Campus' && collegeSelect.value && centralCollegeCoordinates[collegeSelect.value]) {
        const [lat, lng] = centralCollegeCoordinates[collegeSelect.value];
        updateCoordinates(lat, lng);
      }

      campusTypeSelect.addEventListener('change', () => {
        const isExternal = campusTypeSelect.value === 'WMSU ESU';
        campusLocationContainer.style.display = isExternal ? 'block' : 'none';
        campusLocationSelect.required = isExternal;
        campusLocationSelect.value = ''; // Reset campus location selection

        if (campusTypeSelect.value === 'Main Campus') {
          const selectedCollege = collegeSelect.value;
          if (selectedCollege && centralCollegeCoordinates[selectedCollege]) {
            // Use college-specific coordinates if available
            const [lat, lng] = centralCollegeCoordinates[selectedCollege];
            updateCoordinates(lat, lng);
          } else {
            // Fallback to Central campus coordinates
            const [lat, lng] = campusCoordinates['Main Campus'];
            updateCoordinates(lat, lng);
          }
        } else {
          // Clear map, inputs, and iframe for External until a location is selected
          if (marker) marker.remove();
          document.getElementById('xInput').value = '';
          document.getElementById('yInput').value = '';
          const googleMapIframe = document.querySelector('#google-map iframe');
          if (googleMapIframe) {
            googleMapIframe.src = 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d990.2030970492448!2d122.06323088460613!3d6.913022088239877!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1746872885316!5m2!1sen!2sph';
          }
        }
      });

      // Update map and iframe based on campus location selection
      campusLocationSelect.addEventListener('change', () => {
        const selectedCampus = campusLocationSelect.value;
        if (campusCoordinates[selectedCampus]) {
          const [lat, lng] = campusCoordinates[selectedCampus];
          updateCoordinates(lat, lng);
        }
      });

      collegeSelect.addEventListener('change', () => {
        console.log('College selected:', collegeSelect.value); // Log the selected college
        departmentSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';
        const selectedCollege = collegeSelect.value;
        if (departmentData[selectedCollege]) {
          departmentData[selectedCollege].forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = dept;
            departmentSelect.appendChild(option);
            console.log('Added department:', dept); // Log each added department
          });
        }

        // Update map coordinates if campus type is Central
        if (campusTypeSelect.value === 'Main Campus' && centralCollegeCoordinates[selectedCollege]) {
          const [lat, lng] = centralCollegeCoordinates[selectedCollege];
          updateCoordinates(lat, lng);
        } else if (campusTypeSelect.value === 'Main Campus') {
          // Fallback to Central campus coordinates if no college-specific coordinates
          const [lat, lng] = campusCoordinates['Main Campus'];
          updateCoordinates(lat, lng);
        }
      });

      // Copy coordinates to clipboard
      window.copyToClipboard = function() {
        const x = document.getElementById('xInput').value;
        const y = document.getElementById('yInput').value;
        if (!x || !y) {
          const alertDiv = document.createElement('div');
          alertDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
          alertDiv.role = 'alert';
          alertDiv.innerHTML = `
            No coordinates selected. Please select a campus or click on the map.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          `;
          document.querySelector('.card-body').prepend(alertDiv);
          setTimeout(() => alertDiv.remove(), 3000);
          return;
        }
        const coords = `Longitude: ${x}, Latitude: ${y}`;
        navigator.clipboard.writeText(coords)
          .then(() => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
              Coordinates copied to clipboard!
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.card-body').prepend(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
          })
          .catch(() => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
              Failed to copy coordinates. Please copy manually.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.card-body').prepend(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
          });
      };

      document.getElementById('submitForm').addEventListener('click', async (event) => {
        event.preventDefault();
        const form = document.getElementById('addPrecinctForm');

        if (form.checkValidity()) {
          const formData = new FormData(form);

          try {
            const response = await fetch('processes/precincts/add.php', {
              method: 'POST',
              body: formData
            });

            const result = await response.json();

            const alertMessage = result.message;
            const alertType = result.status === 'success' ? 'success' : 'error';

            // Use SweetAlert to display the message
            Swal.fire({
              icon: alertType, // 'success' or 'error'
              title: alertType === 'success' ? 'Success!' : 'Oops!',
              text: alertMessage,
              showConfirmButton: true,
              timer: 3000 // Automatically closes after 3 seconds (optional)
            });


            if (result.status === 'success') {
              form.reset();
              campusLocationContainer.style.display = 'none';
              if (marker) marker.remove();
              document.getElementById('xInput').value = '';
              document.getElementById('yInput').value = '';
              departmentSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';
              const googleMapIframe = document.querySelector('#google-map iframe');
              if (googleMapIframe) {
                googleMapIframe.src = 'https://www.google.com/maps/embed?...'; // keep your original URL here
              }
            }

          } catch (error) {
            console.error('Error submitting form:', error);
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
        Failed to submit form. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
            document.querySelector('.card-body').prepend(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
          }
        } else {
          form.reportValidity();
        }
      });

    });

    document.addEventListener('DOMContentLoaded', () => {
      const collegeSelect = document.getElementById('college_type');
      const precinctNameInput = document.getElementById('name');

      // College abbreviation mapping
      const collegeAbbreviations = {
        'College of Law': 'CL',
        'College of Agriculture': 'CA',
        'College of Liberal Arts': 'CLA',
        'College of Architecture': 'CArch',
        'College of Nursing': 'CN',
        'College of Asian & Islamic Studies': 'CAIS',
        'College of Computing Studies': 'CCS',
        'College of Forestry & Environmental Studies': 'CFES',
        'College of Criminal Justice Education': 'CCJE',
        'College of Home Economics': 'CHE',
        'College of Engineering': 'CE',
        'College of Medicine': 'CM',
        'College of Public Administration & Development Studies': 'CPADS',
        'College of Sports Science & Physical Education': 'CSSPE',
        'College of Science and Mathematics': 'CSM',
        'College of Social Work & Community Development': 'CSWCD',
        'College of Teacher Education': 'CTE'
      };

      async function fetchElectionData() {
        try {
          const response = await fetch('processes/precincts/fetch_elections.php');
          if (!response.ok) throw new Error('Failed to fetch election data');
          const data = await response.json();

          if (!data.elections || data.elections.length === 0) {
            throw new Error('No ongoing elections found');
          }

          // Map all elections
          const elections = data.elections.map(election => ({
            school_year_start: election.school_year_start,
            school_year_end: election.school_year_end,
            election_name: election.election_name,
            semester: election.semester,
            location: election.location
          }));

          return elections;
        } catch (error) {
          console.error('Error fetching election data:', error);
          return [{
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
          if (!response.ok) {
            throw new Error('Failed to fetch precincts');
          }
          const data = await response.json();
          return Array.isArray(data) ? data : [];
        } catch (error) {
          console.error('Error fetching existing precincts:', error);
          return [];
        }
      }
      async function generatePrecinctName(college) {
        if (!college) {
          precinctNameInput.value = '';
          return;
        }

        const abbr = collegeAbbreviations[college] || 'UNKNOWN';

        // Get selected election from radio buttons
        const selectedRadio = document.querySelector('input[name="election"]:checked');
        if (!selectedRadio) {
          precinctNameInput.value = '';
          console.error('No election selected');
          return;
        }

        const selectedElectionName = selectedRadio.value;

        // Fetch all ongoing elections
        const elections = await fetchElectionData();

        // Find the election object that matches the selected radio value
        const election = elections.find(e => e.election_name === selectedElectionName);

        if (!election) {
          precinctNameInput.value = '';
          console.error('Selected election not found in data');
          return;
        }

        const {
          school_year_start,
          school_year_end,
          semester,
          location: electionLocation
        } = election;

        // Clean semester
        const cleanSemester = semester.replace(/semester/i, '').trim();

        // Get and clean location from input field (fallback to election location if empty)
        const locationInput = document.getElementById('precinctLocation');
        const rawLocation = locationInput?.value?.trim() || electionLocation || 'NA';
        const cleanLocation = rawLocation.replace(/\s+/g, '').replace(/[^\w\-]/g, ''); // Remove spaces & special chars

        // Build base precinct name
        const basePrecinctName = `${school_year_start}-${school_year_end} ${cleanSemester}_${abbr}_${selectedElectionName}_${cleanLocation}`;

        const existingPrecincts = await fetchExistingPrecincts();
        const validPrecincts = Array.isArray(existingPrecincts) ? existingPrecincts : [];

        // Find highest number suffix
        const matchingPrecincts = validPrecincts.filter(name =>
          typeof name === 'string' && name.startsWith(basePrecinctName + '-')
        );

        let highestNumber = 0;
        matchingPrecincts.forEach(name => {
          const match = name.match(/-(\d+)$/);
          if (match) {
            const number = parseInt(match[1], 10);
            if (!isNaN(number) && number > highestNumber) {
              highestNumber = number;
            }
          }
        });

        const newNumber = highestNumber + 1;
        const precinctName = `${basePrecinctName}-${newNumber}`;
        precinctNameInput.value = precinctName;
      }


      // Event listener for college selection change
      collegeSelect.addEventListener('change', (e) => {
        const selectedCollege = e.target.value;
        generatePrecinctName(selectedCollege);
      });

      // Initialize with current selection if any
      if (collegeSelect.value) {
        generatePrecinctName(collegeSelect.value);
      }
    });

    document.addEventListener('DOMContentLoaded', async () => {
      const container = document.getElementById('electionCheckboxContainer');

      try {
        const response = await fetch('processes/precincts/fetch_elections.php');
        const data = await response.json();

        const elections = data.elections;

        if (elections.length === 0) {
          container.innerHTML = '<p class="text-muted">No ongoing elections found.</p>';
          return;
        }

        elections.forEach((election, index) => {
          const checkboxId = `election_${index + 1}`;
          const checkbox = `
          <div class="form-check">
            <input type="RADIO" class="form-check-input" name="election" id="${checkboxId}" value="${election.election_name}">
            <label class="form-check-label" for="${checkboxId}">${election.election_name}</label>
          </div>
        `;
          container.insertAdjacentHTML('beforeend', checkbox);
        });

      } catch (err) {
        console.error('Error loading elections:', err);
        container.innerHTML = '<p class="text-danger">Failed to load elections.</p>';
      }
    });
  </script>
</body>

</html>