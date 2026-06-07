<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$checkedFiles = [
    $root.'/src/Value/Form/Managing/AdministrationManagingFieldAccessMutationReviewData.php',
    $root.'/src/Form/Managing/AdministrationManagingFieldAccessMutationReviewFormType.php',
];

foreach ($checkedFiles as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, sprintf("Missing expected file: %s\n", $file));
        exit(1);
    }

    $contents = file_get_contents($file);
    if (false === $contents) {
        fwrite(STDERR, sprintf("Unable to read file: %s\n", $file));
        exit(1);
    }

    if (str_contains($contents, 'App\\Managing\\Value\\Administration\\ManagingFieldPermissionVocabulary')) {
        fwrite(STDERR, sprintf("Forbidden optional Managing vocabulary reference in %s\n", $file));
        exit(1);
    }

    if (preg_match('/(?<!Administration)ManagingFieldPermissionVocabulary::/', $contents)) {
        fwrite(STDERR, sprintf("Forbidden non-owned ManagingFieldPermissionVocabulary usage in %s\n", $file));
        exit(1);
    }
}

$vocabulary = $root.'/src/Value/Form/Managing/AdministrationManagingFieldPermissionVocabulary.php';
if (!is_file($vocabulary)) {
    fwrite(STDERR, "Missing Administering-owned Managing field permission vocabulary.\n");
    exit(1);
}

$contents = file_get_contents($vocabulary);
if (false === $contents || !str_contains($contents, "FIELD_VIEW = 'managing.field.view'")) {
    fwrite(STDERR, "Administering-owned Managing vocabulary must define FIELD_VIEW.\n");
    exit(1);
}

fwrite(STDOUT, "Administering Managing vocabulary cleanup guard passed.\n");
