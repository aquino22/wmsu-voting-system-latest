<?php
require_once 'includes/conn.php';

try {
    // Validate and sanitize form_id
    if (!isset($_GET['form_id']) || !is_numeric($_GET['form_id'])) {
        throw new Exception("Invalid form ID");
    }
    
    $form_id = (int)$_GET['form_id'];
    
    // Fetch form details
    $form_stmt = $pdo->prepare("SELECT * FROM registration_forms WHERE id = ? AND status = 'active'");
    $form_stmt->execute([$form_id]);
    $form = $form_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        throw new Exception("Form not found or inactive");
    }

    $fields_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
    $fields_stmt->execute([$form_id]);
    $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch parties for this election
    $party_stmt = $pdo->prepare("SELECT name FROM parties WHERE election_name = ? AND status = 'approved' ORDER BY name");
    $party_stmt->execute([$form['election_name']]);
    $parties = $party_stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo htmlspecialchars($form['form_name']); ?> - Candidate Registration</title>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <style>
            .field-container { margin: 15px 0; }
            .required::after { content: '*'; color: red; margin-left: 5px; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2><?php echo htmlspecialchars($form['form_name']); ?></h2>
        <form method="POST" action="submit_candidate.php" enctype="multipart/form-data">
    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
    
    <?php foreach($fields as $field): ?>
        <div class="field-container">
            <label class="<?php echo $field['is_required'] ? 'required' : ''; ?>">
                <?php echo htmlspecialchars($field['field_name']); ?>
            </label>
            
            <?php switch($field['field_name']):
                case 'full_name': ?>
                    <input type="text" 
                           name="fields[<?php echo $field['id']; ?>]"
                           <?php echo $field['is_required'] ? 'required' : ''; ?>
                           maxlength="255">
                <?php break;
                
                case 'party': ?>
                    <select name="fields[<?php echo $field['id']; ?>]" 
                            id="party_select"
                            <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <option value="">Select Party</option>
                        <?php foreach ($parties as $party): ?>
                            <option value="<?php echo htmlspecialchars($party['name']); ?>">
                                <?php echo htmlspecialchars($party['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php break;
                
                case 'position': ?>
                    <select name="fields[<?php echo $field['id']; ?>]" 
                            id="position_select"
                            <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <option value="">Select Party First</option>
                    </select>
                <?php break;
                
                default: 
                    switch($field['field_type']):
                        case 'text': ?>
                            <input type="text" 
                                   name="fields[<?php echo $field['id']; ?>]"
                                   <?php echo $field['is_required'] ? 'required' : ''; ?>
                                   maxlength="255">
                        <?php break;
                        
                        case 'textarea': ?>
                            <textarea name="fields[<?php echo $field['id']; ?>]"
                                      <?php echo $field['is_required'] ? 'required' : ''; ?>
                                      rows="4" cols="50"></textarea>
                        <?php break;
                        
                        case 'select': 
                            $options = $field['options'] ? explode(',', $field['options']) : [];
                            ?>
                            <select name="fields[<?php echo $field['id']; ?>]"
                                  <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value="">Select an option</option>
                                <?php foreach($options as $option): 
                                    $option = trim($option);
                                    if (!empty($option)): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        <?php break;
                        
                        case 'checkbox': ?>
                            <input type="checkbox" 
                                 name="fields[<?php echo $field['id']; ?>]" 
                                 value="1">
                        <?php break;
                        
                        case 'radio': 
                            $options = $field['options'] ? explode(',', $field['options']) : [];
                            foreach($options as $option): 
                                $option = trim($option);
                                if (!empty($option)): ?>
                                    <label>
                                        <input type="radio" 
                                             name="fields[<?php echo $field['id']; ?>]"
                                             value="<?php echo htmlspecialchars($option); ?>"
                                             <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php break;
                        
                        case 'file': ?>
                            <input type="file" 
                                 name="fields[<?php echo $field['id']; ?>]"
                                 <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php break;
                    endswitch;
                break;
            endswitch; ?>
        </div>
    <?php endforeach; ?>
    
    <button type="submit">Submit Registration</button>
</form>

        <script>
        $(document).ready(function() {
            $('#party_select').change(function() {
                let partyName = $(this).val();
                let electionName = '<?php echo addslashes($form['election_name']); ?>';
                
                if (partyName) {
                    $.ajax({
                        url: 'getter_positions.php',
                        method: 'POST',
                        data: { 
                            election_name: electionName,
                            party_name: partyName 
                        },
                        success: function(response) {
                            $('#position_select').html(response);
                        }
                    });
                } else {
                    $('#position_select').html('<option value="">Select Party First</option>');
                }
            });
        });
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
    </head>
    <body>
        <div class="error">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <a href="create_form.php">Back to Form Builder</a>
        </div>
    </body>
    </html>
    <?php
}
?>