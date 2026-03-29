<?php
// Path to your image
$imagePath = 'img20250603_00161013_0022.png';

// Optional: crop using ImageMagick to focus only on the lower-left area
$croppedImage = 'cropped.png';
exec("convert $imagePath -crop 400x100+50+1100 $croppedImage");

// Run Tesseract OCR on cropped image
$outputFile = 'output';
exec("tesseract $croppedImage $outputFile");

// Get text output
$ocrText = file_get_contents($outputFile . '.txt');

// Extract Student ID using regex (starts with XT followed by numbers)
preg_match('/XT\d{9}/i', $ocrText, $matches);

if (!empty($matches)) {
    echo "Student ID: " . $matches[0];
} else {
    echo "Student ID not found.";
}
?>
