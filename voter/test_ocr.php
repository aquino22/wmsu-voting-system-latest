<?php
function findTextInImage($imagePath, $searchTexts, $tesseractPath = 'tesseract') {
    // Check if file exists
    if (!file_exists($imagePath)) {
        return ['error' => 'Image file not found'];
    }

    // Validate search texts
    if (!is_array($searchTexts)) {
        $searchTexts = [$searchTexts];
    }

    // Create a temporary file for OCR output
    $tempFile = tempnam(sys_get_temp_dir(), 'ocr');
    $outputFile = $tempFile . '.txt';

    // Execute Tesseract OCR
    $command = escapeshellcmd("$tesseractPath " . escapeshellarg($imagePath) . " " . escapeshellarg($tempFile));
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        return ['error' => 'OCR processing failed'];
    }

    // Read the OCR results
    $ocrText = file_get_contents($outputFile);
    unlink($outputFile); // Clean up temporary file
    unlink($tempFile);   // Clean up temporary file

    // Analyze results
    $foundTexts = [];
    $missingTexts = [];
    $allTextsFound = true;

    foreach ($searchTexts as $text) {
        if (stripos($ocrText, $text) !== false) {
            $foundTexts[] = $text;
        } else {
            $missingTexts[] = $text;
            $allTextsFound = false;
        }
    }

    return [
        'found' => $foundTexts,
        'missing' => $missingTexts,
        'all_found' => $allTextsFound,
        'ocr_text' => $ocrText, // Full OCR output for debugging
    ];
}

// Example usage:
$imagePath = 'NUR COR.png'; // Path to your image
$searchTexts = ['College of Computing Studies', 'NUR BALLA', '2021-00168']; // Texts to search for

// On Windows, you might need to specify the full path to tesseract.exe
// $result = findTextInImage($imagePath, $searchTexts, 'C:\Program Files\Tesseract-OCR\tesseract.exe');

// On Linux/Mac (assuming tesseract is in PATH)
$result = findTextInImage($imagePath, $searchTexts);

echo "<pre>";
print_r($result);
echo "</pre>";
?>