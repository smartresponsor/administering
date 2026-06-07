<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$files = [
    'src/Controller/Admin/Surface/AdministrationRollingAclMutationController.php',
    'src/ServiceInterface/Rolling/AdministrationAclMutationReviewRecorderInterface.php',
    'src/Recorder/Rolling/AdministrationDoctrineAclMutationReviewRecorder.php',
    'src/Recorder/Rolling/DoctrineAdministrationAclMutationReviewRecorder.php',
];

$forbidden = [
    'App\\Rolling\\ServiceInterface\\Administration\\RollingAclMutationReviewBuilderInterface',
    'App\\Rolling\\Value\\Administration\\RollingAclMutationRequest',
    'App\\Rolling\\Value\\Administration\\RollingAclMutationReview',
    'App\\Rolling\\Value\\Administration\\RollingFieldAccessDecisionRequest',
    'App\\Rolling\\Value\\Administration\\RollingFieldAccessScopeSet',
];

$errors = [];
foreach ($files as $relativePath) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        $errors[] = sprintf('Missing expected file: %s', $relativePath);
        continue;
    }

    $contents = file_get_contents($path);
    if (false === $contents) {
        $errors[] = sprintf('Unable to read file: %s', $relativePath);
        continue;
    }

    foreach ($forbidden as $needle) {
        if (str_contains($contents, $needle)) {
            $errors[] = sprintf('Forbidden optional Rolling dependency in %s: %s', $relativePath, $needle);
        }
    }
}

$required = [
    'src/Value/Rolling/AdministrationRollingAclMutationRequest.php',
    'src/Value/Rolling/AdministrationRollingAclMutationReview.php',
];
foreach ($required as $relativePath) {
    if (!is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath))) {
        $errors[] = sprintf('Missing local Administering Rolling value: %s', $relativePath);
    }
}

if ([] !== $errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error.PHP_EOL);
    }

    exit(1);
}

echo 'Administering Rolling ACL mutation surface cleanup guard passed.'.PHP_EOL;
