<?php
// Full path to pdftoppm.exe
$pdftoppmPath = "C:\\poppler\\Library\\bin\\pdftoppm.exe"; // <- adjust if needed

// PDF input and output
$pdfPath = "C:\\xampp\\htdocs\\VS-NEW\\html\\pdf-sample_0.pdf";
$outputPrefix = "C:\\xampp\\htdocs\\VS-NEW\\html\\output_test";

// Construct the command (no escapeshellcmd!)
$command = "\"$pdftoppmPath\" -png -f 1 -l 1 \"$pdfPath\" \"$outputPrefix\"";

// Run it
exec($command, $output, $returnVar);

// Output results
echo "<pre>";
echo "Command: $command\n";
echo "Return Value: $returnVar\n";
print_r($output);
echo "</pre>";

// Check if the image was created
$generatedFile = $outputPrefix . "-1.png";
if (file_exists($generatedFile)) {
    echo "✅ Image generated: $generatedFile";
} else {
    echo "❌ Image not found. Something failed.";
}
