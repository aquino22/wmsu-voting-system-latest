<?php
require_once 'includes/conn.php';

// Fetch available elections
$elections = $pdo->query("SELECT DISTINCT election_name FROM candidacy WHERE status = 'Ongoing'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Registration Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .field-container { margin: 10px 0; }
        .field-options { margin-left: 20px; }
    </style>
</head>
<body>
    <form id="formBuilder" method="POST" action="save_form.php">
        <!-- Form Basics -->
        <div>
            <label>Form Name:</label>
            <input type="text" name="form_name" required>
        </div>

        <div>
            <label>Election:</label>
            <select name="election_name" id="election_name" required>
                <option value="">Select Election</option>
                <?php foreach ($elections as $election): ?>
                    <option value="<?php echo htmlspecialchars($election['election_name']); ?>">
                        <?php echo htmlspecialchars($election['election_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Default Fields -->
        <h3>Default Fields</h3>
        <div>
            <input type="checkbox" name="default_fields[]" value="full_name" checked disabled> Full Name (Required)
            <input type="hidden" name="default_fields[]" value="full_name">
        </div>
        <div>
            <input type="checkbox" name="default_fields[]" value="party" id="party_checkbox"> Party
            <div id="party_options" style="display: none;">
                <select name="party_source" disabled>
                    <option value="">Will be populated based on election</option>
                </select>
            </div>
        </div>
        <div>
            <input type="checkbox" name="default_fields[]" value="position" id="position_checkbox"> Position
            <div id="position_options" style="display: none;">
                <select name="position_source" disabled>
                    <option value="">Will be populated based on election and party</option>
                </select>
            </div>
        </div>

        <!-- Custom Fields -->
        <h3>Custom Fields</h3>
        <div id="custom_fields"></div>
        <button type="button" id="add_field">Add Custom Field</button>

        <button type="submit">Save Form</button>
    </form>

    <script>
    $(document).ready(function() {
        let fieldCount = 0;

        // Show/hide party options
        $('#party_checkbox').change(function() {
            $('#party_options').toggle(this.checked);
        });

        // Show/hide position options
        $('#position_checkbox').change(function() {
            $('#position_options').toggle(this.checked);
        });

        // Load parties when election changes
        $('#election_name').change(function() {
            let electionName = $(this).val();
            if (electionName) {
                // Load parties
                $.ajax({
                    url: 'get_parties.php',
                    method: 'POST',
                    data: { election_name: electionName },
                    success: function(response) {
                        if ($('#party_checkbox').is(':checked')) {
                            $('#party_options select').html(response);
                        }
                    }
                });

                // Clear positions
                $('#position_options select').html('<option value="">Select a party first</option>');
            }
        });

        // Handle party selection for positions (simulated here)
        $('#formBuilder').on('change', '#party_options select', function() {
            if ($('#position_checkbox').is(':checked')) {
                let electionName = $('#election_name').val();
                let partyName = $(this).val();
                if (electionName && partyName) {
                    $.ajax({
                        url: 'getter_positions.php',
                        method: 'POST',
                        data: { 
                            election_name: electionName,
                            party_name: partyName 
                        },
                        success: function(response) {
                            $('#position_options select').html(response);
                        }
                    });
                }
            }
        });

        // Add custom field
        $('#add_field').click(function() {
            fieldCount++;
            let fieldHtml = `
                <div class="field-container" id="field_${fieldCount}">
                    <div>
                        <label>Field Label:</label>
                        <input type="text" name="custom_fields[${fieldCount}][label]" required>
                    </div>
                    <div>
                        <label>Field Type:</label>
                        <select name="custom_fields[${fieldCount}][type]" class="field-type" data-id="${fieldCount}">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Dropdown</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio</option>
                            <option value="file">File</option>
                        </select>
                    </div>
                    <div class="field-options" id="options_${fieldCount}"></div>
                    <div>
                        <input type="checkbox" name="custom_fields[${fieldCount}][required]"> Required
                        <button type="button" class="remove_field" data-id="${fieldCount}">Remove</button>
                    </div>
                </div>`;
            $('#custom_fields').append(fieldHtml);
        });

        // Handle field type change for options
        $(document).on('change', '.field-type', function() {
            let fieldId = $(this).data('id');
            let type = $(this).val();
            let optionsHtml = '';
            if (type === 'select' || type === 'radio') {
                optionsHtml = `
                    <label>Options (comma-separated):</label>
                    <input type="text" name="custom_fields[${fieldId}][options]" 
                           placeholder="option1, option2, option3">
                `;
            }
            $('#options_' + fieldId).html(optionsHtml);
        });

        // Remove field
        $(document).on('click', '.remove_field', function() {
            let fieldId = $(this).data('id');
            $('#field_' + fieldId).remove();
        });
    });
    </script>
</body>
</html>