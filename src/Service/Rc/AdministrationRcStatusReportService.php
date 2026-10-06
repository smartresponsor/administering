<?php

declare(strict_types=1);

namespace App\Administering\Service\Rc;

use Symfony\Component\Yaml\Yaml;

/**
 * Builds the read-only Administering 3RC status report from captured artifacts.
 *
 * Artifact parsing, contract checks, status validation, and hash verification live
 * here so the Symfony Console command remains orchestration-only.
 */
final class AdministrationRcStatusReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReport(
        string $manifestFile,
        string $proofFile,
        string $indexFile,
        string $validationFile,
        string $ownerReviewFile,
        string $finalSealFile,
        string $finalSealValidationFile,
        string $receiptFile,
        string $receiptTextFile,
        string $receiptValidationFile,
        string $handoffIndexFile,
        string $handoffIndexTextFile,
        string $handoffIndexValidationFile,
        string $finalStatusValidationFile,
        string $handoffBundleFile,
        string $handoffBundleTextFile,
        string $handoffBundleValidationFile,
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
    ): array {
        $checks = [];
        $errors = [];

        $manifest = $this->readYaml($manifestFile, $checks, $errors, 'manifest');
        $proof = $this->readJson($proofFile, $checks, $errors, 'proof');
        $index = $this->readJson($indexFile, $checks, $errors, 'index');
        $validation = $this->readJson($validationFile, $checks, $errors, 'validation');
        $ownerReview = $this->readJson($ownerReviewFile, $checks, $errors, 'owner_review');
        $finalSeal = $this->readJson($finalSealFile, $checks, $errors, 'final_seal');
        $finalSealValidation = $this->readJson($finalSealValidationFile, $checks, $errors, 'final_seal_validation');
        [
            $receipt,
            $receiptValidation,
            $handoffIndex,
            $handoffIndexValidation,
            $handoffBundle,
            $handoffBundleValidation,
        ] = $this->readOptionalArtifacts(
            $includeReceiptArtifacts,
            $receiptFile,
            $receiptValidationFile,
            $includeHandoffArtifacts,
            $handoffIndexFile,
            $handoffIndexValidationFile,
            $includeHandoffBundleArtifacts,
            $handoffBundleFile,
            $handoffBundleValidationFile,
            $checks,
            $errors,
        );

        $this->validateOptionalTextArtifacts(
            $includeReceiptArtifacts,
            $receiptTextFile,
            $includeHandoffArtifacts,
            $handoffIndexTextFile,
            $includeHandoffBundleArtifacts,
            $handoffBundleTextFile,
            $checks,
            $errors,
        );
        $this->validateManifestMetadata($manifest, $includeReceiptArtifacts, $includeHandoffArtifacts, $includeHandoffBundleArtifacts, $checks, $errors);
        $this->validateArtifactStatuses(
            $proof,
            $index,
            $validation,
            $ownerReview,
            $finalSeal,
            $finalSealValidation,
            $receipt,
            $receiptValidation,
            $handoffIndex,
            $handoffIndexValidation,
            $handoffBundle,
            $handoffBundleValidation,
            $includeReceiptArtifacts,
            $includeHandoffArtifacts,
            $includeHandoffBundleArtifacts,
            $checks,
            $errors,
        );
        $this->validateArtifactHashes(
            $finalSealValidation,
            $handoffIndexValidation,
            $handoffBundleValidation,
            $manifestFile,
            $proofFile,
            $indexFile,
            $validationFile,
            $ownerReviewFile,
            $finalSealFile,
            $handoffIndexFile,
            $handoffBundleFile,
            $handoffBundleTextFile,
            $includeHandoffArtifacts,
            $includeHandoffBundleArtifacts,
            $checks,
            $errors,
        );

        return $this->buildStatusReport(
            $includeReceiptArtifacts,
            $includeHandoffArtifacts,
            $includeHandoffBundleArtifacts,
            $manifestFile,
            $proofFile,
            $indexFile,
            $validationFile,
            $ownerReviewFile,
            $finalSealFile,
            $finalSealValidationFile,
            $receiptFile,
            $receiptTextFile,
            $receiptValidationFile,
            $handoffIndexFile,
            $handoffIndexTextFile,
            $handoffIndexValidationFile,
            $finalStatusValidationFile,
            $handoffBundleFile,
            $handoffBundleTextFile,
            $handoffBundleValidationFile,
            $proof,
            $index,
            $validation,
            $ownerReview,
            $finalSeal,
            $finalSealValidation,
            $receipt,
            $receiptValidation,
            $handoffIndex,
            $handoffIndexValidation,
            $handoffBundle,
            $handoffBundleValidation,
            $checks,
            $errors,
        );
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array{
     *   0: array<string, mixed>|null,
     *   1: array<string, mixed>|null,
     *   2: array<string, mixed>|null,
     *   3: array<string, mixed>|null,
     *   4: array<string, mixed>|null,
     *   5: array<string, mixed>|null
     * }
     */
    private function readOptionalArtifacts(
        bool $includeReceiptArtifacts,
        string $receiptFile,
        string $receiptValidationFile,
        bool $includeHandoffArtifacts,
        string $handoffIndexFile,
        string $handoffIndexValidationFile,
        bool $includeHandoffBundleArtifacts,
        string $handoffBundleFile,
        string $handoffBundleValidationFile,
        array &$checks,
        array &$errors,
    ): array {
        $receipt = $includeReceiptArtifacts ? $this->readJson($receiptFile, $checks, $errors, 'receipt') : null;
        $receiptValidation = $includeReceiptArtifacts ? $this->readJson($receiptValidationFile, $checks, $errors, 'receipt_validation') : null;
        $handoffIndex = $includeHandoffArtifacts ? $this->readJson($handoffIndexFile, $checks, $errors, 'handoff_index') : null;
        $handoffIndexValidation = $includeHandoffArtifacts ? $this->readJson($handoffIndexValidationFile, $checks, $errors, 'handoff_index_validation') : null;
        $handoffBundle = $includeHandoffBundleArtifacts ? $this->readJson($handoffBundleFile, $checks, $errors, 'handoff_bundle') : null;
        $handoffBundleValidation = $includeHandoffBundleArtifacts ? $this->readJson($handoffBundleValidationFile, $checks, $errors, 'handoff_bundle_validation') : null;

        return [$receipt, $receiptValidation, $handoffIndex, $handoffIndexValidation, $handoffBundle, $handoffBundleValidation];
    }

    /**
     * @param array<string, mixed>|null                           $proof
     * @param array<string, mixed>|null                           $index
     * @param array<string, mixed>|null                           $validation
     * @param array<string, mixed>|null                           $ownerReview
     * @param array<string, mixed>|null                           $finalSeal
     * @param array<string, mixed>|null                           $finalSealValidation
     * @param array<string, mixed>|null                           $receipt
     * @param array<string, mixed>|null                           $receiptValidation
     * @param array<string, mixed>|null                           $handoffIndex
     * @param array<string, mixed>|null                           $handoffIndexValidation
     * @param array<string, mixed>|null                           $handoffBundle
     * @param array<string, mixed>|null                           $handoffBundleValidation
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array<string, mixed>
     */
    private function buildStatusReport(
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        string $manifestFile,
        string $proofFile,
        string $indexFile,
        string $validationFile,
        string $ownerReviewFile,
        string $finalSealFile,
        string $finalSealValidationFile,
        string $receiptFile,
        string $receiptTextFile,
        string $receiptValidationFile,
        string $handoffIndexFile,
        string $handoffIndexTextFile,
        string $handoffIndexValidationFile,
        string $finalStatusValidationFile,
        string $handoffBundleFile,
        string $handoffBundleTextFile,
        string $handoffBundleValidationFile,
        ?array $proof,
        ?array $index,
        ?array $validation,
        ?array $ownerReview,
        ?array $finalSeal,
        ?array $finalSealValidation,
        ?array $receipt,
        ?array $receiptValidation,
        ?array $handoffIndex,
        ?array $handoffIndexValidation,
        ?array $handoffBundle,
        ?array $handoffBundleValidation,
        array $checks,
        array $errors,
    ): array {
        $ready = [] === $errors;

        return [
            'schema_version' => '1.0',
            'component' => 'Administering',
            'package' => 'administering/admin',
            'namespace' => 'App\\Administering',
            'rc_stage' => '3RC-candidate',
            'status' => $ready ? 'sealed_3rc_validated' : 'blocked',
            'sealed_3rc_validated' => $ready,
            'reported_at_utc' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'include_receipt_artifacts' => $includeReceiptArtifacts,
            'include_handoff_artifacts' => $includeHandoffArtifacts,
            'include_handoff_bundle_artifacts' => $includeHandoffBundleArtifacts,
            'artifact_status' => $this->buildArtifactStatus(
                $includeReceiptArtifacts,
                $includeHandoffArtifacts,
                $includeHandoffBundleArtifacts,
                $proof,
                $index,
                $validation,
                $ownerReview,
                $finalSeal,
                $finalSealValidation,
                $receipt,
                $receiptValidation,
                $handoffIndex,
                $handoffIndexValidation,
                $handoffBundle,
                $handoffBundleValidation,
            ),
            'artifacts' => $this->buildArtifactInventory(
                $includeReceiptArtifacts,
                $includeHandoffArtifacts,
                $includeHandoffBundleArtifacts,
                $manifestFile,
                $proofFile,
                $indexFile,
                $validationFile,
                $ownerReviewFile,
                $finalSealFile,
                $finalSealValidationFile,
                $receiptFile,
                $receiptTextFile,
                $receiptValidationFile,
                $handoffIndexFile,
                $handoffIndexTextFile,
                $handoffIndexValidationFile,
                $finalStatusValidationFile,
                $handoffBundleFile,
                $handoffBundleTextFile,
                $handoffBundleValidationFile,
            ),
            'checks' => $checks,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed>|null $proof
     * @param array<string, mixed>|null $index
     * @param array<string, mixed>|null $validation
     * @param array<string, mixed>|null $ownerReview
     * @param array<string, mixed>|null $finalSeal
     * @param array<string, mixed>|null $finalSealValidation
     * @param array<string, mixed>|null $receipt
     * @param array<string, mixed>|null $receiptValidation
     * @param array<string, mixed>|null $handoffIndex
     * @param array<string, mixed>|null $handoffIndexValidation
     * @param array<string, mixed>|null $handoffBundle
     * @param array<string, mixed>|null $handoffBundleValidation
     *
     * @return array<string, mixed>
     */
    private function buildArtifactStatus(
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        ?array $proof,
        ?array $index,
        ?array $validation,
        ?array $ownerReview,
        ?array $finalSeal,
        ?array $finalSealValidation,
        ?array $receipt,
        ?array $receiptValidation,
        ?array $handoffIndex,
        ?array $handoffIndexValidation,
        ?array $handoffBundle,
        ?array $handoffBundleValidation,
    ): array {
        return [
            'proof' => $this->jsonStatus($proof),
            'index' => $this->jsonStatus($index),
            'validation' => $this->jsonStatus($validation),
            'owner_review' => $this->jsonStatus($ownerReview),
            'final_seal' => $this->jsonStatus($finalSeal),
            'final_seal_validation' => $this->jsonStatus($finalSealValidation),
            'receipt' => $includeReceiptArtifacts ? $this->jsonStatus($receipt) : null,
            'receipt_validation' => $includeReceiptArtifacts ? $this->jsonStatus($receiptValidation) : null,
            'handoff_index' => $includeHandoffArtifacts ? $this->jsonStatus($handoffIndex) : null,
            'handoff_index_validation' => $includeHandoffArtifacts ? $this->jsonStatus($handoffIndexValidation) : null,
            'handoff_bundle' => $includeHandoffBundleArtifacts ? $this->jsonStatus($handoffBundle) : null,
            'handoff_bundle_validation' => $includeHandoffBundleArtifacts ? $this->jsonStatus($handoffBundleValidation) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function buildArtifactInventory(
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        string $manifestFile,
        string $proofFile,
        string $indexFile,
        string $validationFile,
        string $ownerReviewFile,
        string $finalSealFile,
        string $finalSealValidationFile,
        string $receiptFile,
        string $receiptTextFile,
        string $receiptValidationFile,
        string $handoffIndexFile,
        string $handoffIndexTextFile,
        string $handoffIndexValidationFile,
        string $finalStatusValidationFile,
        string $handoffBundleFile,
        string $handoffBundleTextFile,
        string $handoffBundleValidationFile,
    ): array {
        return [
            'manifest_file' => $manifestFile,
            'proof_file' => $proofFile,
            'index_file' => $indexFile,
            'validation_file' => $validationFile,
            'owner_review_file' => $ownerReviewFile,
            'final_seal_file' => $finalSealFile,
            'final_seal_validation_file' => $finalSealValidationFile,
            'receipt_file' => $includeReceiptArtifacts ? $receiptFile : null,
            'receipt_text_file' => $includeReceiptArtifacts ? $receiptTextFile : null,
            'receipt_validation_file' => $includeReceiptArtifacts ? $receiptValidationFile : null,
            'handoff_index_file' => $includeHandoffArtifacts ? $handoffIndexFile : null,
            'handoff_index_text_file' => $includeHandoffArtifacts ? $handoffIndexTextFile : null,
            'handoff_index_validation_file' => $includeHandoffArtifacts ? $handoffIndexValidationFile : null,
            'final_status_validation_file' => $includeHandoffArtifacts ? $finalStatusValidationFile : null,
            'handoff_bundle_file' => $includeHandoffBundleArtifacts ? $handoffBundleFile : null,
            'handoff_bundle_text_file' => $includeHandoffBundleArtifacts ? $handoffBundleTextFile : null,
            'handoff_bundle_validation_file' => $includeHandoffBundleArtifacts ? $handoffBundleValidationFile : null,
            'manifest_sha256' => $this->hashOrNull($manifestFile),
            'proof_sha256' => $this->hashOrNull($proofFile),
            'index_sha256' => $this->hashOrNull($indexFile),
            'validation_sha256' => $this->hashOrNull($validationFile),
            'owner_review_sha256' => $this->hashOrNull($ownerReviewFile),
            'final_seal_sha256' => $this->hashOrNull($finalSealFile),
            'final_seal_validation_sha256' => $this->hashOrNull($finalSealValidationFile),
            'receipt_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptFile) : null,
            'receipt_text_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptTextFile) : null,
            'receipt_validation_sha256' => $includeReceiptArtifacts ? $this->hashOrNull($receiptValidationFile) : null,
            'handoff_index_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexFile) : null,
            'handoff_index_text_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexTextFile) : null,
            'handoff_index_validation_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($handoffIndexValidationFile) : null,
            'final_status_validation_sha256' => $includeHandoffArtifacts ? $this->hashOrNull($finalStatusValidationFile) : null,
            'handoff_bundle_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleFile) : null,
            'handoff_bundle_text_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleTextFile) : null,
            'handoff_bundle_validation_sha256' => $includeHandoffBundleArtifacts ? $this->hashOrNull($handoffBundleValidationFile) : null,
        ];
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateOptionalTextArtifacts(
        bool $includeReceiptArtifacts,
        string $receiptTextFile,
        bool $includeHandoffArtifacts,
        string $handoffIndexTextFile,
        bool $includeHandoffBundleArtifacts,
        string $handoffBundleTextFile,
        array &$checks,
        array &$errors,
    ): void {
        if ($includeReceiptArtifacts) {
            $this->addCheck($checks, $errors, 'receipt_text_file_exists', is_file($receiptTextFile), $receiptTextFile);
        }

        if ($includeHandoffArtifacts) {
            $this->addCheck($checks, $errors, 'handoff_index_text_file_exists', is_file($handoffIndexTextFile), $handoffIndexTextFile);
        }

        if ($includeHandoffBundleArtifacts) {
            $this->addCheck($checks, $errors, 'handoff_bundle_text_file_exists', is_file($handoffBundleTextFile), $handoffBundleTextFile);
        }
    }

    /**
     * @param array<string, mixed>|null                           $manifest
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateManifestMetadata(
        ?array $manifest,
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        array &$checks,
        array &$errors,
    ): void {
        if (null === $manifest) {
            return;
        }

        $this->addCheck($checks, $errors, 'manifest_component', 'Administering' === ($manifest['component'] ?? null), 'component=Administering');
        $this->addCheck($checks, $errors, 'manifest_package', 'administering/admin' === ($manifest['package'] ?? null), 'package=administering/admin');
        $this->addCheck($checks, $errors, 'manifest_namespace', 'App\\Administering' === ($manifest['namespace'] ?? null), 'namespace=App\\Administering');
        $this->addCheck($checks, $errors, 'manifest_rc_stage', '3RC-candidate' === ($manifest['rc_stage'] ?? null), 'rc_stage=3RC-candidate');
        $this->addCheck($checks, $errors, 'manifest_status_artifact', isset($manifest['artifacts']['rc_status']), 'artifacts.rc_status exists');

        if ($includeReceiptArtifacts) {
            $this->addCheck($checks, $errors, 'manifest_receipt_artifact', isset($manifest['artifacts']['rc_receipt']), 'artifacts.rc_receipt exists');
            $this->addCheck($checks, $errors, 'manifest_receipt_validation_artifact', isset($manifest['artifacts']['rc_receipt_validation']), 'artifacts.rc_receipt_validation exists');
        }

        if ($includeHandoffArtifacts) {
            $this->addCheck($checks, $errors, 'manifest_handoff_index_artifact', isset($manifest['artifacts']['rc_handoff_index']), 'artifacts.rc_handoff_index exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_index_validation_artifact', isset($manifest['artifacts']['rc_handoff_index_validation']), 'artifacts.rc_handoff_index_validation exists');
        }

        if ($includeHandoffBundleArtifacts) {
            $this->addCheck($checks, $errors, 'manifest_handoff_bundle_artifact', isset($manifest['artifacts']['rc_handoff_bundle']), 'artifacts.rc_handoff_bundle exists');
            $this->addCheck($checks, $errors, 'manifest_handoff_bundle_validation_artifact', isset($manifest['artifacts']['rc_handoff_bundle_validation']), 'artifacts.rc_handoff_bundle_validation exists');
        }
    }

    /**
     * @param array<string, mixed>|null                           $proof
     * @param array<string, mixed>|null                           $index
     * @param array<string, mixed>|null                           $validation
     * @param array<string, mixed>|null                           $ownerReview
     * @param array<string, mixed>|null                           $finalSeal
     * @param array<string, mixed>|null                           $finalSealValidation
     * @param array<string, mixed>|null                           $receipt
     * @param array<string, mixed>|null                           $receiptValidation
     * @param array<string, mixed>|null                           $handoffIndex
     * @param array<string, mixed>|null                           $handoffIndexValidation
     * @param array<string, mixed>|null                           $handoffBundle
     * @param array<string, mixed>|null                           $handoffBundleValidation
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateArtifactStatuses(
        ?array $proof,
        ?array $index,
        ?array $validation,
        ?array $ownerReview,
        ?array $finalSeal,
        ?array $finalSealValidation,
        ?array $receipt,
        ?array $receiptValidation,
        ?array $handoffIndex,
        ?array $handoffIndexValidation,
        ?array $handoffBundle,
        ?array $handoffBundleValidation,
        bool $includeReceiptArtifacts,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        array &$checks,
        array &$errors,
    ): void {
        $this->assertJsonStatus($proof, 'proof', 'ready', 'ready', $checks, $errors);
        $this->assertJsonStatus($index, 'index', 'captured', null, $checks, $errors);
        $this->assertJsonStatus($validation, 'validation', 'valid', 'valid', $checks, $errors);
        $this->assertJsonStatus($ownerReview, 'owner_review', 'ready_for_owner_review', 'ready_for_owner_review', $checks, $errors);
        $this->assertJsonStatus($finalSeal, 'final_seal', 'sealed_3rc_candidate', 'sealed_3rc_candidate', $checks, $errors);
        $this->assertJsonStatus($finalSealValidation, 'final_seal_validation', 'final_seal_valid', 'final_seal_valid', $checks, $errors);

        if ($includeReceiptArtifacts) {
            $this->assertJsonStatus($receipt, 'receipt', '3rc_receipt_ready', 'receipt_ready', $checks, $errors);
            $this->assertJsonStatus($receiptValidation, 'receipt_validation', '3rc_receipt_valid', 'receipt_valid', $checks, $errors);
        }

        if ($includeHandoffArtifacts) {
            $this->assertJsonStatus($handoffIndex, 'handoff_index', '3rc_handoff_index_ready', 'handoff_index_ready', $checks, $errors);
            $this->assertJsonStatus($handoffIndexValidation, 'handoff_index_validation', '3rc_handoff_index_valid', 'handoff_index_valid', $checks, $errors);
        }

        if ($includeHandoffBundleArtifacts) {
            $this->assertJsonStatus($handoffBundle, 'handoff_bundle', '3rc_handoff_bundle_ready', 'handoff_bundle_ready', $checks, $errors);
            $this->assertJsonStatus($handoffBundleValidation, 'handoff_bundle_validation', '3rc_handoff_bundle_valid', 'handoff_bundle_valid', $checks, $errors);
        }
    }

    /**
     * @param array<string, mixed>|null                           $finalSealValidation
     * @param array<string, mixed>|null                           $handoffIndexValidation
     * @param array<string, mixed>|null                           $handoffBundleValidation
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateArtifactHashes(
        ?array $finalSealValidation,
        ?array $handoffIndexValidation,
        ?array $handoffBundleValidation,
        string $manifestFile,
        string $proofFile,
        string $indexFile,
        string $validationFile,
        string $ownerReviewFile,
        string $finalSealFile,
        string $handoffIndexFile,
        string $handoffBundleFile,
        string $handoffBundleTextFile,
        bool $includeHandoffArtifacts,
        bool $includeHandoffBundleArtifacts,
        array &$checks,
        array &$errors,
    ): void {
        $this->validateFinalSealHashes($finalSealValidation, $manifestFile, $proofFile, $indexFile, $validationFile, $ownerReviewFile, $finalSealFile, $checks, $errors);

        if ($includeHandoffArtifacts) {
            $this->validateHandoffIndexHashes($handoffIndexValidation, $manifestFile, $handoffIndexFile, $checks, $errors);
        }

        if ($includeHandoffBundleArtifacts) {
            $this->validateHandoffBundleHashes($handoffBundleValidation, $manifestFile, $handoffBundleFile, $handoffBundleTextFile, $checks, $errors);
        }
    }

    /**
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateFinalSealHashes(
        ?array $decoded,
        string $manifestFile,
        string $proofFile,
        string $indexFile,
        string $validationFile,
        string $ownerReviewFile,
        string $finalSealFile,
        array &$checks,
        array &$errors,
    ): void {
        if (null === $decoded) {
            return;
        }

        $artifacts = $decoded['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'final_seal_validation_artifacts_present', is_array($artifacts), 'final-seal validation artifacts map exists');
        if (!is_array($artifacts)) {
            return;
        }

        $this->addCheck($checks, $errors, 'final_seal_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
        $this->addCheck($checks, $errors, 'final_seal_validation_proof_hash_current', $this->hashMatches($proofFile, $artifacts['proof_sha256'] ?? null), 'proof hash matches current file');
        $this->addCheck($checks, $errors, 'final_seal_validation_index_hash_current', $this->hashMatches($indexFile, $artifacts['index_sha256'] ?? null), 'index hash matches current file');
        $this->addCheck($checks, $errors, 'final_seal_validation_validation_hash_current', $this->hashMatches($validationFile, $artifacts['validation_sha256'] ?? null), 'validation hash matches current file');
        $this->addCheck($checks, $errors, 'final_seal_validation_owner_review_hash_current', $this->hashMatches($ownerReviewFile, $artifacts['owner_review_sha256'] ?? null), 'owner-review hash matches current file');
        $this->addCheck($checks, $errors, 'final_seal_validation_final_seal_hash_current', $this->hashMatches($finalSealFile, $artifacts['final_seal_sha256'] ?? null), 'final-seal hash matches current file');
    }

    /**
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateHandoffIndexHashes(?array $decoded, string $manifestFile, string $handoffIndexFile, array &$checks, array &$errors): void
    {
        if (null === $decoded) {
            return;
        }

        $artifacts = $decoded['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'handoff_index_validation_artifacts_present', is_array($artifacts), 'handoff-index validation artifacts map exists');
        if (!is_array($artifacts)) {
            return;
        }

        $this->addCheck($checks, $errors, 'handoff_index_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
        $this->addCheck($checks, $errors, 'handoff_index_validation_final_status_hash_current', $this->hashMatches($artifacts['final_status_file'] ?? $handoffIndexFile, $artifacts['final_status_sha256'] ?? null), 'final-status hash matches current file when file path is recorded');
        $this->addCheck($checks, $errors, 'handoff_index_validation_handoff_index_hash_current', $this->hashMatches($handoffIndexFile, $artifacts['handoff_index_sha256'] ?? null), 'handoff-index hash matches current file');
    }

    /**
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function validateHandoffBundleHashes(?array $decoded, string $manifestFile, string $handoffBundleFile, string $handoffBundleTextFile, array &$checks, array &$errors): void
    {
        if (null === $decoded) {
            return;
        }

        $artifacts = $decoded['artifacts'] ?? null;
        $this->addCheck($checks, $errors, 'handoff_bundle_validation_artifacts_present', is_array($artifacts), 'handoff-bundle validation artifacts map exists');
        if (!is_array($artifacts)) {
            return;
        }

        $this->addCheck($checks, $errors, 'handoff_bundle_validation_manifest_hash_current', $this->hashMatches($manifestFile, $artifacts['manifest_sha256'] ?? null), 'manifest hash matches current file');
        $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_hash_current', $this->hashMatches($handoffBundleFile, $artifacts['handoff_bundle_sha256'] ?? null), 'handoff-bundle hash matches current file');
        $this->addCheck($checks, $errors, 'handoff_bundle_validation_handoff_bundle_text_hash_current', $this->hashMatches($handoffBundleTextFile, $artifacts['handoff_bundle_text_sha256'] ?? null), 'handoff-bundle text hash matches current file');
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array<string, mixed>|null
     */
    private function readJson(string $file, array &$checks, array &$errors, string $label): ?array
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }

        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);
        $decoded = json_decode((string) file_get_contents($file), true);
        $ok = JSON_ERROR_NONE === json_last_error() && is_array($decoded);
        $this->addCheck($checks, $errors, $label.'_json_parseable', $ok, $ok ? 'valid JSON object' : json_last_error_msg());

        return $ok ? $decoded : null;
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     *
     * @return array<string, mixed>|null
     */
    private function readYaml(string $file, array &$checks, array &$errors, string $label): ?array
    {
        if (!is_file($file)) {
            $this->addCheck($checks, $errors, $label.'_file_exists', false, $file);

            return null;
        }

        $this->addCheck($checks, $errors, $label.'_file_exists', true, $file);

        try {
            $decoded = Yaml::parseFile($file);
            $ok = is_array($decoded);
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', $ok, $ok ? 'valid YAML map' : 'YAML root is not a map');

            return $ok ? $decoded : null;
        } catch (\Throwable $throwable) {
            $this->addCheck($checks, $errors, $label.'_yaml_parseable', false, $throwable->getMessage());

            return null;
        }
    }

    /**
     * @param array<string, mixed>|null                           $decoded
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function assertJsonStatus(?array $decoded, string $label, string $expectedStatus, ?string $expectedBoolKey, array &$checks, array &$errors): void
    {
        if (!is_array($decoded)) {
            $this->addCheck($checks, $errors, $label.'_available_for_status_check', false, 'artifact missing or not parseable');

            return;
        }

        $this->addCheck($checks, $errors, $label.'_status', $expectedStatus === ($decoded['status'] ?? null), sprintf('status=%s', $expectedStatus));
        if (null !== $expectedBoolKey) {
            $this->addCheck($checks, $errors, $label.'_'.$expectedBoolKey.'_true', true === ($decoded[$expectedBoolKey] ?? null), sprintf('%s=true', $expectedBoolKey));
        }

        if (array_key_exists('errors', $decoded)) {
            $this->addCheck($checks, $errors, $label.'_errors_empty', [] === ($decoded['errors'] ?? null), 'errors=[]');
        }
    }

    /** @param array<string, mixed>|null $decoded */
    private function jsonStatus(?array $decoded): ?string
    {
        return is_array($decoded) && is_string($decoded['status'] ?? null) ? $decoded['status'] : null;
    }

    private function hashMatches(string $file, mixed $expectedHash): bool
    {
        return is_string($expectedHash) && is_file($file) && hash_file('sha256', $file) === strtolower($expectedHash);
    }

    private function hashOrNull(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $hash = hash_file('sha256', $file);

        return false === $hash ? null : $hash;
    }

    /**
     * @param list<array{name: string, ok: bool, detail: string}> $checks
     * @param list<string>                                        $errors
     */
    private function addCheck(array &$checks, array &$errors, string $nameEntity, bool $ok, string $detail): void
    {
        $checks[] = [
            'name' => $nameEntity,
            'ok' => $ok,
            'detail' => $detail,
        ];

        if (!$ok) {
            $errors[] = sprintf('%s failed: %s', $nameEntity, $detail);
        }
    }
}
