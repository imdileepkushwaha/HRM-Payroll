<?php

/**
 * Generate Admin & Employee portal documentation PDFs.
 * Usage: php scripts/generate_portal_docs_pdf.php
 */

require_once __DIR__ . '/../includes/portal_docs_pdf.php';

$outputDir = __DIR__ . '/../docs/pdf';

try {
    $files = write_portal_documentation_pdfs($outputDir);
    echo "Generated documentation PDFs:\n";
    foreach ($files as $path) {
        $size = round(filesize($path) / 1024, 1);
        echo "  - {$path} ({$size} KB)\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
