<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionType.php',
    'src/Value/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionData.php',
    'tests/Unit/RuntimeScope/AdministrationRuntimeScopeComponentDecisionDataTest.php',
    'tests/Unit/RuntimeScope/AdministrationRuntimeScopeComponentDecisionTypeTest.php',
];

foreach ($requiredFiles as $relativePath) {
    if (!is_file($root.'/'.$relativePath)) {
        fwrite(STDERR, sprintf("Missing runtime-scope component decision form coverage file: %s\n", $relativePath));
        exit(1);
    }
}

$forbiddenFiles = [
    'src/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionFormType.php',
    'src/Form/RuntimeScope/AdministrationRuntimeScopeComponentToggleType.php',
    'src/Form/RuntimeScope/AdministrationRuntimeScopeComponentToggleFormType.php',
];

foreach ($forbiddenFiles as $relativePath) {
    if (is_file($root.'/'.$relativePath)) {
        fwrite(STDERR, sprintf("Forbidden runtime-scope decision form naming remains: %s\n", $relativePath));
        exit(1);
    }
}

$formSource = (string) file_get_contents($root.'/src/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionType.php');
foreach ([
    'final class AdministrationRuntimeScopeComponentDecisionType extends AbstractType',
    "'data_class' => AdministrationRuntimeScopeComponentDecisionData::class",
    "'method' => 'POST'",
    "'csrf_token_id' => 'administering.runtime_scope.component_decision'",
    "->add('enabled', CheckboxType::class",
    "'required' => false",
    'Unchecked means the component is explicitly disabled',
] as $needle) {
    if (!str_contains($formSource, $needle)) {
        fwrite(STDERR, sprintf("Runtime-scope component decision Type is missing expected marker: %s\n", $needle));
        exit(1);
    }
}

if (str_contains($formSource, 'AdministrationRuntimeScopeComponentDecisionFormType')) {
    fwrite(STDERR, "Runtime-scope component decision Type must not use the FormType suffix.\n");
    exit(1);
}

$typeTest = (string) file_get_contents($root.'/tests/Unit/RuntimeScope/AdministrationRuntimeScopeComponentDecisionTypeTest.php');
foreach (['testUncheckedEnabledCheckboxMapsToFalse', 'testCheckedEnabledCheckboxMapsToTrue', 'Forms::createFormFactory'] as $needle) {
    if (!str_contains($typeTest, $needle)) {
        fwrite(STDERR, sprintf("Runtime-scope component decision Type test is missing expected marker: %s\n", $needle));
        exit(1);
    }
}

$dataTest = (string) file_get_contents($root.'/tests/Unit/RuntimeScope/AdministrationRuntimeScopeComponentDecisionDataTest.php');
foreach (['testItReadsDevDecisionFromConnectedComponentRecord', 'testItReadsProdDecisionFromConnectedComponentRecord'] as $needle) {
    if (!str_contains($dataTest, $needle)) {
        fwrite(STDERR, sprintf("Runtime-scope component decision Data test is missing expected marker: %s\n", $needle));
        exit(1);
    }
}

fwrite(STDOUT, "Administering runtime-scope component decision form guard passed.\n");
