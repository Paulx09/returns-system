<?php

declare(strict_types=1);

$reportPath = $argv[1] ?? null;
$configurationPath = __DIR__ . '/../phpunit.xml';

if ($reportPath === null || !is_file($reportPath)) {
    fwrite(STDERR, "Coverage report not found. Run the coverage command first.\n");
    exit(1);
}

$configuration = simplexml_load_file($configurationPath);
$report = simplexml_load_file($reportPath);

if ($configuration === false || $report === false) {
    fwrite(STDERR, "Unable to read the PHPUnit coverage configuration or report.\n");
    exit(1);
}

$minimumStatements = 100.0;
$minimumBranches = 100.0;

foreach ($configuration->php->env as $environment) {
    $name = (string) $environment['name'];
    $value = (float) $environment['value'];

    if ($name === 'RETURN_STATEMENT_COVERAGE_MIN') {
        $minimumStatements = $value;
    }

    if ($name === 'RETURN_BRANCH_COVERAGE_MIN') {
        $minimumBranches = $value;
    }
}

$statements = 0;
$coveredStatements = 0;
$branches = 0;
$coveredBranches = 0;

foreach ($report->project->package->file as $file) {
    $filePath = str_replace('\\', '/', (string) $file['name']);

    if (!str_ends_with($filePath, 'app/Services/ExternalOrderService.php')) {
        continue;
    }

    $metrics = $file->metrics;
    $statements += (int) $metrics['statements'];
    $coveredStatements += (int) $metrics['coveredstatements'];
    $branches += (int) $metrics['conditionals'];
    $coveredBranches += (int) $metrics['coveredconditionals'];
}

if ($statements === 0 || $branches === 0) {
    fwrite(STDERR, "The report does not contain statement and branch metrics for ExternalOrderService.\n");
    exit(1);
}

$statementCoverage = $coveredStatements / $statements * 100;
$branchCoverage = $coveredBranches / $branches * 100;

printf("ExternalOrderService statements: %.2f%%\n", $statementCoverage);
printf("ExternalOrderService branches: %.2f%%\n", $branchCoverage);

if ($statementCoverage < $minimumStatements || $branchCoverage < $minimumBranches) {
    fwrite(STDERR, "Configured coverage threshold was not met.\n");
    exit(1);
}