<?php
session_start();
include('includes/conn.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Voting Interface</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            font-family: 'Roboto', 'Arial', sans-serif;
        }

        body {
            background-color: #B22222;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .profiler {
            max-width: 150px;
            height: auto;
            border-radius: 50%;
        }

        .bordered {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }

        .spacer {
            margin-right: 10px;
        }

        .election-selector {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="background-color: #ffc107 !important;">
        <nav class="navbar navbar-expand-lg bg-warning">
            <div class="container-fluid justify-content-center">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="images/wmsu-logo.png" alt="WMSU Logo" height="40" class="d-inline-block align-top">
                    <span class="ms-2 fw-bold text-dark">Admin Voting Interface</span>
                </a>
            </div>
        </nav>
    </div>


    <div class="container mt-4" style="height: 100vh">

        <!-- Voting Interface -->
        <div id="votingInterface" style="display:none;">
            <div class="card card-rounded">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center mb-4">
                        <div>
                            <h2 class="card-title card-title-dash text-center" id="electionTitle"></h2>
                        </div>
                    </div>

                    <p style="text-align:center"><b>Note:</b> This will disrupt academic integrity and honesty as revoting with the student body is seen as a much better honest approach.</p>
                    <form id="voteForm" method="POST" action="submit_admin_vote.php">
                        <input type="hidden" name="voting_period_id" id="votingPeriodId">
                        <input type="hidden" name="admin_vote" value="1">

                        <div id="candidatesContainer" class="container-fluid text-center">
                            <!-- Candidates will be loaded here -->
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Submit Admin Vote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        $(document).ready(function() {
            votingPeriodId = <?php echo $_GET['voting_period_id'] ?>

            // Load candidates for selected period
            loadCandidates(votingPeriodId);


            // Function to load candidates
            // Updated loadCandidates function
            function loadCandidates(votingPeriodId) {
                $.ajax({
                    url: 'get_candidates_for_voting.php',
                    method: 'POST',
                    data: {
                        voting_period_id: votingPeriodId,
                        admin_mode: 1,
                        college_grouping: 1 // New flag for college grouping
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Loading Candidates',
                            text: 'Please wait...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();

                        if (response.success) {
                            $('#electionTitle').text(response.election_name + " Elections");
                            $('#votingPeriodId').val(votingPeriodId);

                            let html = '';

                            // Central positions (unchanged)
                            if (response.central.length > 0) {
                                html += '<h1 class="text-center text-primary mb-4">CENTRAL POSITIONS</h1>';
                                html += buildPositionGroups(response.central);
                            }

                            // Local positions - now organized by college
                            if (response.local_by_college && Object.keys(response.local_by_college).length > 0) {
                                html += '<h1 class="text-center text-primary mb-4" style="margin-top: 40px;">LOCAL POSITIONS</h1>';

                                // Loop through each college
                                for (const [college, positions] of Object.entries(response.local_by_college)) {
                                    html += `<div class="college-section">
                                <div class="college-header">
                                    <h3>${college}</h3>
                                </div>`;

                                    // Add positions for this college
                                    html += buildPositionGroups(positions);

                                    html += '</div>'; // Close college-section
                                }
                            }

                            $('#candidatesContainer').html(html);
                            $('#votingInterface').show();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load candidates', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load candidates. Please try again.', 'error');
                    }
                });
            }



            // Updated buildPositionGroups function
            function buildPositionGroups(positions) {
                let html = '';

                positions.forEach(position => {
                    html += `<div class="position-group">
                <div class="position-title">
                    <h4><b>${position.name.toUpperCase()}</b></h4>
                </div>
                <div class="row">`;

                    position.candidates.forEach(candidate => {
                        html += `<div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center text-center">
                <img src="../login/uploads/candidates/${candidate.photo}" class="profiler mb-3" alt="Candidate Photo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                <h5><b>${candidate.name}</b></h5>
                <p class="text-success mb-2">${candidate.party}</p>
                ${candidate.department ? `<p class="text-muted small">${candidate.department}</p>` : ''}
                
                <div class="form-check mt-auto">
                    <input class="form-check-input me-2" type="radio" 
                           name="vote[${position.name}]" 
                           id="candidate_${candidate.id}" 
                           value="${candidate.id}" required>
                    <label class="form-check-label" for="candidate_${candidate.id}">
                        Vote
                    </label>
                </div>
            </div>
        </div>
    </div>`;
                    });


                    html += `</div></div>`; // Close row and position-group
                });

                return html;
            }
            // Handle vote submission
            $('#voteForm').submit(function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);

                // Build receipt
                let receiptHtml = '<h3>Admin Voting Receipt</h3><br><table class="table table-bordered"><thead><tr><th>Position</th><th>Candidate</th><th>Party</th></tr></thead><tbody>';
                let hasVotes = false;

                // Get all selected votes
                $('input[type="radio"]:checked').each(function() {
                    const position = $(this).attr('name').replace('vote[', '').replace(']', '');
                    const candidateId = $(this).val();
                    const $card = $(this).closest('.card');
                    const candidateName = $card.find('h5 b').text(); // Corrected selector
                    const candidateParty = $card.find('p.text-success').text(); // Corrected selector

                    if (candidateName && position) { // Ensure valid data
                        receiptHtml += `<tr><td>${position}</td><td>${candidateName}</td><td>${candidateParty}</td></tr>`;
                        hasVotes = true;
                    }
                });

                receiptHtml += '</tbody></table>';

                if (!hasVotes) {
                    Swal.fire('Error', 'Please select at least one candidate', 'warning');
                    return;
                }

                // Show confirmation
                Swal.fire({
                    title: 'Confirm Admin Vote',
                    html: receiptHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Submit Vote',
                    cancelButtonText: 'Review',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitVote(formData);
                    }
                });
            });
            // Function to submit vote
            function submitVote(formData) {
                Swal.fire({
                    title: 'Submitting Vote',
                    text: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'submit_admin_vote.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Reload the page to clear selections
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Vote submission failed', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to submit vote. Please try again.', 'error');
                    }
                });
            }
        });
    </script>
</body>

</html>