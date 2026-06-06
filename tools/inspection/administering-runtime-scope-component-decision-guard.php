<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionType.php',
    'src/Value/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionData.php',
    'src/Service/RuntimeScope/AdministrationRuntimeScopeComponentDecisionApplyService.php',
    'src/Controller/Admin/RuntimeScope/AdministrationRuntimeScopeComponentDecisionController.php',
];

foreach ($requiredFiles as $relativePath) {
    $path = $root.'/'.$relativePath;
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("Missing runtime-scope component decision file: %s\n", $relativePath));
        exit(1);
    }
}

$formSource = file_get_contents($root.'/src/Form/RuntimeScope/AdministrationRuntimeScopeComponentDecisionType.php') ?: '';
if (!str_contains($formSource, 'final class AdministrationRuntimeScopeComponentDecisionType extends AbstractType')) {
    fwrite(STDERR, "Runtime-scope component decision form must use the Type suffix and extend AbstractType.\n");
    exit(1);
}

if (str_contains($formSource, 'AdministrationRuntimeScopeComponentDecisionFormType')) {
    fwrite(STDERR, "Runtime-scope component decision form must not use the FormType suffix.\n");
    exit(1);
}

$crudSource = file_get_contents($root.'/src/Controller/Admin/Crud/AdministrationConnectedComponentRecordCrudController.php') ?: '';
foreach (['enabledForDev', 'enabledForProd', 'changeRuntimeScopeDecision'] as $needle) {
    if (!str_contains($crudSource, $needle)) {
        fwrite(STDERR, sprintf("Enabled Components CRUD is missing expected decision marker: %s\n", $needle));
        exit(1);
    }
}

$applySource = file_get_contents($root.'/src/Service/RuntimeScope/AdministrationRuntimeScopeComponentDecisionApplyService.php') ?: '';
foreach (['enabledBundleTokens', 'disabledComponents', 'runtime_scope.component_decision', 'recordSyncService->synchronize()'] as $needle) {
    if (!str_contains($applySource, $needle)) {
        fwrite(STDERR, sprintf("Runtime-scope decision apply service is missing marker: %s\n", $needle));
        exit(1);
    }
}

fwrite(STDOUT, "Administering runtime-scope component decision guard passed.\n");
