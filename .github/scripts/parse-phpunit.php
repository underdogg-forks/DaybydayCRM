#!/usr/bin/env php
<?php

/**
 * PHPUnit Results Cleaner & Parser
 * Usage: phpunit-command | php parse-phpunit.php
 */

$input = file_get_contents('php://stdin');

// 1. Remove ANSI escape codes (colors)
$clean = preg_replace('/\x1b[[()#;?]*[0-9,.;\/]*[0-ac-m-pqrstvuwy]|[\x07\x08\x0c\x0e\x0f]/', '', $input);

// 2. Remove timestamps (2026-05-14T03:17:38.4840913Z)
$clean = preg_replace('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z/', '', $clean);

$lines = explode("\n", $clean);
$output = [];
$isBufferingTrace = false;
$currentTrace = [];

foreach ($lines as $line) {
    $trimmedLine = trim($line);

    // Strip lines representing successful tests (starting with ✔)
    if (str_starts_with($trimmedLine, '✔')) {
        continue;
    }

    // Identify the start of a stack trace (usually starts with a file path and line number)
    if (preg_match('/^#\d+\s+.*\.php\(\d+\):/', $trimmedLine) || str_contains($trimmedLine, 'vendor/phpunit/phpunit')) {
        $isBufferingTrace = true;
        $currentTrace[] = $line;
        continue;
    }

    // If we were buffering a trace and hit a non-trace line (blank or new error)
    if ($isBufferingTrace && ($trimmedLine === '' || preg_match('/^\d+\)/', $trimmedLine))) {
        // Output only the LAST 3 lines of the buffered trace
        $lastThree = array_slice($currentTrace, -3);
        foreach ($lastThree as $traceLine) {
            $output[] = $traceLine;
        }
        $currentTrace = [];
        $isBufferingTrace = false;
    }

    if (!$isBufferingTrace) {
        $output[] = $line;
    }
}

// Catch final trace if file ends on one
if (!empty($currentTrace)) {
    $lastThree = array_slice($currentTrace, -3);
    foreach ($lastThree as $traceLine) {
        $output[] = $traceLine;
    }
}

// Final cleanup: Remove double blank lines and trim
$result = implode("\n", $output);
$result = preg_replace("/\n{3,}/", "\n\n", $result);

$hasErrors = preg_match('/\b(FAILURES!|ERRORS!)\b/i', $clean) === 1
    || preg_match('/\b(Failures|Errors|Warnings|Risky|Incomplete):\s*[1-9]\d*/i', $clean) === 1
    || str_contains($clean, 'There was 1 failure:')
    || str_contains($clean, 'There were ');

if ($hasErrors) {
    echo rtrim($clean) . "\n";
    exit(1);
}

echo rtrim($result) . "\n";
