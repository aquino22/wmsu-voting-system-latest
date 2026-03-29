  <!DOCTYPE html>
  <html lang="en">

  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
  </head>

  <body>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // College abbreviation mapping
        const collegeAbbreviations = {
          "College of Law": "CL",
          "College of Agriculture": "CA",
          "College of Liberal Arts": "CLA",
          "College of Architecture": "CArch",
          "College of Nursing": "CN",
          "College of Asian & Islamic Studies": "CAIS",
          "College of Computing Studies": "CCS",
          "College of Forestry & Environmental Studies": "CFES",
          "College of Criminal Justice Education": "CCJE",
          "College of Home Economics": "CHE",
          "College of Engineering": "CE",
          "College of Medicine": "CM",
          "College of Public Administration & Development Studies": "CPADS",
          "College of Sports Science & Physical Education": "CSSPE",
          "College of Science and Mathematics": "CSM",
          "College of Social Work & Community Development": "CSWCD",
          "College of Teacher Education": "CTE"
        };

        // Map variables
        let map;
        let marker;

        // Initialize Leaflet map
        function initializeMap() {
          if (map) return; // Already initialized

          map = L.map('leaflet-map').setView([6.9129722649685865, 122.06321320922099], 17);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          }).addTo(map);

          // Add click event listener
          map.on('click', (e) => {
            const {
              lat,
              lng
            } = e.latlng;
            // Update X (longitude) and Y (latitude) input fields
            document.getElementById('xInput').value = lng.toFixed(6);
            document.getElementById('yInput').value = lat.toFixed(6);

            // Update Google Maps iframe
            const googleMap = document.querySelector('#google-map iframe');
            if (googleMap) {
              googleMap.src = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
            }

            // Update marker
            if (marker) marker.remove();
            marker = L.marker([lat, lng]).addTo(map);
          });
        }

        // Initialize map when modal is shown
        $('#addPrecinctModal').on('shown.bs.modal', function() {
          setTimeout(() => {
            initializeMap();
            if (map) map.invalidateSize();
          }, 100);
        });

        // Clean up map when modal is hidden
        $('#addPrecinctModal').on('hidden.bs.modal', function() {
          if (map) {
            map.remove();
            map = null;
            marker = null;
          }
        });

        // Reinitialize map on window resize when modal is visible
        window.addEventListener('resize', function() {
          if (map && $('#addPrecinctModal').hasClass('show')) {
            setTimeout(() => {
              map.invalidateSize();
            }, 100);
          }
        });

        // Copy coordinates to clipboard
        function copyToClipboard() {
          const x = document.getElementById('xInput').value;
          const y = document.getElementById('yInput').value;

          if (!x || !y) {
            Swal.fire({
              title: 'Warning',
              text: 'No coordinates selected. Please click on the map first.',
              icon: 'warning',
              confirmButtonText: 'OK'
            });
            return;
          }

          const coords = `Longitude: ${x}, Latitude: ${y}`;

          navigator.clipboard.writeText(coords)
            .then(() => {
              Swal.fire({
                title: 'Success',
                text: 'Coordinates copied to clipboard!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              });
            })
            .catch(() => {
              // Fallback for non-secure contexts
              const textarea = document.createElement('textarea');
              textarea.value = coords;
              document.body.appendChild(textarea);
              textarea.select();
              try {
                document.execCommand('copy');
                Swal.fire({
                  title: 'Success',
                  text: 'Coordinates copied to clipboard!',
                  icon: 'success',
                  timer: 1500,
                  showConfirmButton: false
                });
              } catch (err) {
                Swal.fire({
                  title: 'Error',
                  text: 'Failed to copy coordinates. Please copy manually.',
                  icon: 'error',
                  confirmButtonText: 'OK'
                });
              }
              document.body.removeChild(textarea);
            });
        }
        window.copyToClipboard = copyToClipboard;

        // Fetch elections data and populate checkboxes
        fetch('processes/precincts/get-elections.php')
          .then(response => response.json())
          .then(data => {
            const electionContainer = document.getElementById('electionCheckboxes');
            if (data.elections && data.elections.length) {
              data.elections.forEach(election => {
                const div = document.createElement('div');
                div.classList.add('form-check');

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.classList.add('form-check-input');
                checkbox.name = 'elections[]';
                checkbox.id = `election_${election.election_name}`;
                checkbox.value = election.election_name;

                const label = document.createElement('label');
                label.classList.add('form-check-label');
                label.setAttribute('for', `election_${election.election_name}`);
                label.textContent = election.election_name;

                div.appendChild(checkbox);
                div.appendChild(label);
                electionContainer.appendChild(div);
              });
            } else {
              electionContainer.innerHTML = '<p>No elections available</p>';
            }
          })
          .catch(error => {
            console.error('Error loading elections:', error);
          });

        // Update precinct name when college or election changes
        function updatePrecinctName() {
          const college = document.getElementById('college').value;
          const nameInput = document.getElementById('name');
          const selectedElections = document.querySelectorAll('input[name="elections[]"]:checked');

          if (!college || selectedElections.length !== 1) {
            nameInput.value = '';
            return;
          }

          const collegeAbbr = collegeAbbreviations[college] || 'UNK';
          const electionName = selectedElections[0].value;

          // Fetch school year from elections data
          fetch('processes/precincts/get-elections.php')
            .then(response => response.json())
            .then(data => {
              const election = data.elections.find(e => e.election_name === electionName);
              if (election && election.school_year_start && election.school_year_end) {
                const schoolYear = `${election.school_year_start}_${election.school_year_end}`;
                getPrecinctIncrement(schoolYear, collegeAbbr, electionName).then(increment => {
                  nameInput.value = `${schoolYear}-${collegeAbbr}-${electionName}-${increment}`;
                });
              } else {
                nameInput.value = '';
              }
            })
            .catch(error => {
              console.error('Error fetching election data:', error);
              nameInput.value = '';
            });
        }

        // Function to fetch existing precincts and determine the increment
        function getPrecinctIncrement(schoolYear, collegeAbbr, electionName) {
          return fetch('processes/precincts/get-precincts.php')
            .then(response => response.json())
            .then(data => {
              const precincts = data.precincts || [];
              const prefix = `${schoolYear}-${collegeAbbr}-${electionName}`;
              const matchingPrecincts = precincts.filter(p => p.name.startsWith(prefix));
              const increments = matchingPrecincts
                .map(p => {
                  const parts = p.name.split('-');
                  return parseInt(parts[parts.length - 1]) || 0;
                })
                .filter(n => !isNaN(n));
              return increments.length ? Math.max(...increments) + 1 : 1;
            })
            .catch(error => {
              console.error('Error fetching precincts:', error);
              return 1; // Default to 1 if error occurs
            });
        }

        // Event listeners for college and election changes
        document.getElementById('college').addEventListener('change', updatePrecinctName);
        document.getElementById('electionCheckboxes').addEventListener('change', function() {
          const checkedCount = document.querySelectorAll('input[name="elections[]"]:checked').length;
          if (checkedCount > 1) {
            Swal.fire({
              title: 'Warning!',
              text: 'Please select only one election for precinct naming.',
              icon: 'warning',
              confirmButtonText: 'OK'
            }).then(() => {
              // Uncheck all but the first selected
              const checkboxes = document.querySelectorAll('input[name="elections[]"]:checked');
              for (let i = 1; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
              }
            });
          }
          updatePrecinctName();
        });

        // Handle form submission
        document.getElementById('submitForm').addEventListener('click', function(event) {
          event.preventDefault();

          const form = document.getElementById('addPrecinctForm');
          const formData = new FormData(form);

          const selectedElections = [];
          document.querySelectorAll('input[name="elections[]"]:checked').forEach(checkbox => {
            selectedElections.push(checkbox.value);
          });

          if (selectedElections.length === 0) {
            Swal.fire({
              title: 'Error!',
              text: 'Please select at least one election.',
              icon: 'warning',
              confirmButtonText: 'OK'
            });
            return;
          }

          formData.append('elections', JSON.stringify(selectedElections));

          fetch('processes/precincts/add.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  title: 'Success!',
                  text: 'Precinct added successfully!',
                  icon: 'success',
                  confirmButtonText: 'OK'
                }).then(() => {
                  $('#addPrecinctModal').modal('hide');
                  location.reload();
                });
              } else {
                Swal.fire({
                  title: 'Error!',
                  text: data.message || 'Error adding precinct',
                  icon: 'error',
                  confirmButtonText: 'Try Again'
                });
              }
            })
            .catch(error => {
              Swal.fire({
                title: 'Error!',
                text: 'An error occurred while submitting the form.',
                icon: 'error',
                confirmButtonText: 'Try Again'
              });
              console.error('Error submitting form:', error);
            });
        });
      });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  </body>

  </html>
  <div class="modal-dialog modal-lg" id="addPrecinctModal">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Add Precinct</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addPrecinctForm">
          <div class="row">
            <div class="col">
              <div class="mb-3">
                <label for="name" class="form-label">Precinct Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
              </div>

              <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" required>
              </div>

              <div class="mb-3">
                <div id="map-container">
                  <div id="leaflet-map"></div>
                  <div id="google-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d990.2030970492448!2d122.06323088460613!3d6.913022088239877!2m3!1f0!2f0!3f0maps!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1746872885316!5m2!1sen!2sph" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                  </div>
                </div>
                <div id="coordinates">
                  <label for="xInput">X (Longitude):</label>
                  <input type="text" id="xInput" readonly placeholder="Longitude">
                  <label for="yInput">Y (Latitude):</label>
                  <input type="text" id="yInput" readonly placeholder="Latitude">
                  <button onclick="copyToClipboard()">Copy Coordinates</button>
                </div>
              </div>

              <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-control" id="type" name="type" required>
                  <option value="" disabled selected>Select Type</option>
                  <option value="Central">Central</option>
                  <option value="External">External</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="college" class="form-label">College</label>
                <select class="form-control" id="college" name="college" required>
                  <option value="" disabled selected>Select College</option>

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
                <label for="department" class="form-label">Department</label>
                <select class="form-control" id="department" name="department" required>

                </select>
              </div>

              <script>
                $(document).ready(function() {
                  if ($.fn.DataTable.isDataTable('#precinctsTable')) {
                    $('#precinctsTable').DataTable().destroy();
                  }
                  $('#precinctsTable').DataTable({
                    'paging': true,
                    'searching': true,
                    'ordering': true,
                    'info': true
                  });
                });
              </script>

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

                document.getElementById("college").addEventListener("change", function() {
                  let college = this.value;
                  let departmentDropdown = document.getElementById("department");

                  // Clear previous department options
                  departmentDropdown.innerHTML = `<option value="" disabled selected>Select Department</option>`;

                  if (college in departmentData) {
                    departmentData[college].forEach(dept => {
                      let option = document.createElement("option");
                      option.value = dept;
                      option.textContent = dept;
                      departmentDropdown.appendChild(option);
                    });
                  }
                });
              </script>


              <div class="col">
                <div class="mb-3" style="display:none">
                  <label for="assignment_status" class="form-label">Assignment Status</label>
                  <select class="form-control" id="assignment_status" name="assignment_status" required>
                    <option value disabled selected>Select Status</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Unassigned" selected>Unassigned</option>
                  </select>
                </div>

                <div class="mb-3" style="display:none">
                  <label for="occupied_status" class="form-label">Occupied Status</label>
                  <select class="form-control" id="occupied_status" name="occupied_status" required>
                    <option value disabled selected>Select Status</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Unoccupied" selected>Unoccupied</option>
                  </select>
                </div>

                <!-- Elections Checkbox Section -->
                <div class="mb-3" style="border: 1px solid lightgrey; border-radius: 10px; padding: 10px;">
                  <label class="form-label">Select One Election Below: </label>
                  <div id="electionCheckboxes"></div> <!-- Dynamically populated checkboxes -->
                </div>


              </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> <i class="mdi mdi-alpha-x-circle"></i> &nbsp; Close</button>
        <button type="submit" class="btn btn-primary" id="submitForm"> <i class="mdi mdi-plus-circle"></i> &nbsp; Save Changes</button>

      </div>
    </div>
  </div>
  </div>
  </div>