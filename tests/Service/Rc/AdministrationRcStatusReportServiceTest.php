<?php

declare(strict_types=1);

namespace App\Administering\Tests\Service\Rc;

use App\Administering\Service\Rc\AdministrationRcStatusReportService;
use PHPUnit\Framework\TestCase;

final class AdministrationRcStatusReportServiceTest extends TestCase
{
    public function testBuildReportAcceptsCurrentCoreRcArtifacts(): void
    {
        $files = $this->createCoreArtifactSet();

        try {
            $report = $this->buildReport($files);

            self::assertSame('sealed_3rc_validated', $report['status']);
            self::assertTrue($report['sealed_3rc_validated']);
            self::assertSame([], $report['errors']);
            self::assertSame('ready', $report['artifact_status']['proof']);
            self::assertSame('final_seal_valid', $report['artifact_status']['final_seal_validation']);
            self::assertSame(hash_file('sha256', $files['proof']), $report['artifacts']['proof_sha256']);
        } finally {
            $this->removeArtifactSet($files);
        }
    }

    public function testBuildReportBlocksWhenFinalSealValidationHashIsStale(): void
    {
        $files = $this->createCoreArtifactSet();

        try {
            file_put_contents($files['proof'], json_encode([
                'status' => 'ready',
                'ready' => true,
                'errors' => [],
                'changed_after_seal' => true,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            $report = $this->buildReport($files);

            self::assertSame('blocked', $report['status']);
            self::assertFalse($report['sealed_3rc_validated']);
            self::assertContains(
                'final_seal_validation_proof_hash_current failed: proof hash matches current file',
                $report['errors'],
            );
        } finally {
            $this->removeArtifactSet($files);
        }
    }

    public function testBuildReportAcceptsReceiptAndTerminalHandoffArtifacts(): void
    {
        $files = $this->createCoreArtifactSet();
        $this->createTerminalArtifacts($files);

        try {
            $report = $this->buildReport($files, true, true, true);

            self::assertSame('sealed_3rc_validated', $report['status']);
            self::assertTrue($report['sealed_3rc_validated']);
            self::assertSame('3rc_receipt_valid', $report['artifact_status']['receipt_validation']);
            self::assertSame('3rc_handoff_index_valid', $report['artifact_status']['handoff_index_validation']);
            self::assertSame('3rc_handoff_bundle_valid', $report['artifact_status']['handoff_bundle_validation']);
            self::assertSame(hash_file('sha256', $files['handoffBundleText']), $report['artifacts']['handoff_bundle_text_sha256']);
        } finally {
            $this->removeArtifactSet($files);
        }
    }

    /**
     * @param array<string, string> $files
     *
     * @return array<string, mixed>
     */
    private function buildReport(
        array $files,
        bool $includeReceiptArtifacts = false,
        bool $includeHandoffArtifacts = false,
        bool $includeHandoffBundleArtifacts = false,
    ): array {
        return (new AdministrationRcStatusReportService())->buildReport(
            $files['manifest'],
            $files['proof'],
            $files['index'],
            $files['validation'],
            $files['ownerReview'],
            $files['finalSeal'],
            $files['finalSealValidation'],
            $files['receipt'],
            $files['receiptText'],
            $files['receiptValidation'],
            $files['handoffIndex'],
            $files['handoffIndexText'],
            $files['handoffIndexValidation'],
            $files['handoffBundle'],
            $files['handoffBundleText'],
            $files['handoffBundleValidation'],
            $includeReceiptArtifacts,
            $includeHandoffArtifacts,
            $includeHandoffBundleArtifacts,
        );
    }

    /** @return array<string, string> */
    private function createCoreArtifactSet(): array
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'administering-rc-status-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0777, true));

        $files = [
            'directory' => $directory,
            'manifest' => $directory.DIRECTORY_SEPARATOR.'manifest.yaml',
            'proof' => $directory.DIRECTORY_SEPARATOR.'proof.json',
            'index' => $directory.DIRECTORY_SEPARATOR.'index.json',
            'validation' => $directory.DIRECTORY_SEPARATOR.'validation.json',
            'ownerReview' => $directory.DIRECTORY_SEPARATOR.'owner-review.json',
            'finalSeal' => $directory.DIRECTORY_SEPARATOR.'final-seal.json',
            'finalSealValidation' => $directory.DIRECTORY_SEPARATOR.'final-seal-validation.json',
            'receipt' => $directory.DIRECTORY_SEPARATOR.'receipt.json',
            'receiptText' => $directory.DIRECTORY_SEPARATOR.'receipt.txt',
            'receiptValidation' => $directory.DIRECTORY_SEPARATOR.'receipt-validation.json',
            'handoffIndex' => $directory.DIRECTORY_SEPARATOR.'handoff-index.json',
            'handoffIndexText' => $directory.DIRECTORY_SEPARATOR.'handoff-index.txt',
            'handoffIndexValidation' => $directory.DIRECTORY_SEPARATOR.'handoff-index-validation.json',
            'handoffBundle' => $directory.DIRECTORY_SEPARATOR.'handoff-bundle.json',
            'handoffBundleText' => $directory.DIRECTORY_SEPARATOR.'handoff-bundle.txt',
            'handoffBundleValidation' => $directory.DIRECTORY_SEPARATOR.'handoff-bundle-validation.json',
            'finalStatus' => $directory.DIRECTORY_SEPARATOR.'final-status.json',
        ];

        file_put_contents($files['manifest'], implode("\n", [
            "schema_version: '1.0'",
            'component: Administering',
            'package: administering/admin',
            'namespace: App\\Administering',
            'rc_stage: 3RC-candidate',
            'artifacts:',
            '  rc_status: status.json',
            '  rc_receipt: receipt.json',
            '  rc_receipt_validation: receipt-validation.json',
            '  rc_handoff_index: handoff-index.json',
            '  rc_handoff_index_validation: handoff-index-validation.json',
            '  rc_handoff_bundle: handoff-bundle.json',
            '  rc_handoff_bundle_validation: handoff-bundle-validation.json',
            '',
        ]));
        $this->writeJson($files['proof'], ['status' => 'ready', 'ready' => true, 'errors' => []]);
        $this->writeJson($files['index'], ['status' => 'captured', 'errors' => []]);
        $this->writeJson($files['validation'], ['status' => 'valid', 'valid' => true, 'errors' => []]);
        $this->writeJson($files['ownerReview'], ['status' => 'ready_for_owner_review', 'ready_for_owner_review' => true, 'errors' => []]);
        $this->writeJson($files['finalSeal'], ['status' => 'sealed_3rc_candidate', 'sealed_3rc_candidate' => true, 'errors' => []]);

        $this->writeJson($files['finalSealValidation'], [
            'status' => 'final_seal_valid',
            'final_seal_valid' => true,
            'errors' => [],
            'artifacts' => [
                'manifest_sha256' => hash_file('sha256', $files['manifest']),
                'proof_sha256' => hash_file('sha256', $files['proof']),
                'index_sha256' => hash_file('sha256', $files['index']),
                'validation_sha256' => hash_file('sha256', $files['validation']),
                'owner_review_sha256' => hash_file('sha256', $files['ownerReview']),
                'final_seal_sha256' => hash_file('sha256', $files['finalSeal']),
            ],
        ]);

        return $files;
    }

    /** @param array<string, string> $files */
    private function createTerminalArtifacts(array $files): void
    {
        $this->writeJson($files['receipt'], [
            'status' => '3rc_receipt_ready',
            'receipt_ready' => true,
            'errors' => [],
        ]);
        file_put_contents($files['receiptText'], "receipt ready\n");
        $this->writeJson($files['receiptValidation'], [
            'status' => '3rc_receipt_valid',
            'receipt_valid' => true,
            'errors' => [],
        ]);

        $this->writeJson($files['finalStatus'], [
            'status' => 'sealed_3rc_validated',
            'sealed_3rc_validated' => true,
            'errors' => [],
        ]);
        $this->writeJson($files['handoffIndex'], [
            'status' => '3rc_handoff_index_ready',
            'handoff_index_ready' => true,
            'errors' => [],
        ]);
        file_put_contents($files['handoffIndexText'], "handoff index ready\n");
        $this->writeJson($files['handoffIndexValidation'], [
            'status' => '3rc_handoff_index_valid',
            'handoff_index_valid' => true,
            'errors' => [],
            'artifacts' => [
                'manifest_sha256' => hash_file('sha256', $files['manifest']),
                'final_status_file' => $files['finalStatus'],
                'final_status_sha256' => hash_file('sha256', $files['finalStatus']),
                'handoff_index_sha256' => hash_file('sha256', $files['handoffIndex']),
            ],
        ]);

        $this->writeJson($files['handoffBundle'], [
            'status' => '3rc_handoff_bundle_ready',
            'handoff_bundle_ready' => true,
            'errors' => [],
        ]);
        file_put_contents($files['handoffBundleText'], "handoff bundle ready\n");
        $this->writeJson($files['handoffBundleValidation'], [
            'status' => '3rc_handoff_bundle_valid',
            'handoff_bundle_valid' => true,
            'errors' => [],
            'artifacts' => [
                'manifest_sha256' => hash_file('sha256', $files['manifest']),
                'handoff_bundle_sha256' => hash_file('sha256', $files['handoffBundle']),
                'handoff_bundle_text_sha256' => hash_file('sha256', $files['handoffBundleText']),
            ],
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function writeJson(string $file, array $payload): void
    {
        file_put_contents($file, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /** @param array<string, string> $files */
    private function removeArtifactSet(array $files): void
    {
        foreach ($files as $name => $file) {
            if ('directory' === $name) {
                continue;
            }
            @unlink($file);
        }

        @rmdir($files['directory']);
    }
}
