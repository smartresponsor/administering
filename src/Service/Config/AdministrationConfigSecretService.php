<?php

declare(strict_types=1);

namespace App\Administering\Service\Config;

use App\Administering\ServiceInterface\Credential\AdministrationCredentialOperatorInterface;

final readonly class AdministrationConfigSecretService
{
    public function __construct(private AdministrationCredentialOperatorInterface $credentialOperator)
    {
    }

    /**
     * @param array<string, string|null> $replacementValues  field key => plain replacement value
     * @param array<string, string>      $secretNamesByField field key => secret name
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, string>}
     */
    public function replace(string $environment, array $replacementValues, array $secretNamesByField): array
    {
        $messages = [];
        $maskedChanges = [];
        foreach ($replacementValues as $fieldKey => $plainValue) {
            if (!array_key_exists($fieldKey, $secretNamesByField)) {
                return [
                    'status' => 'failed',
                    'messages' => [sprintf('Secret field "%s" is not allowed by the descriptor.', $fieldKey)],
                    'masked_changes' => [],
                ];
            }

            $secretName = $secretNamesByField[$fieldKey];
            if (!preg_match('/^[A-Z0-9_]+$/', $secretName)) {
                return [
                    'status' => 'failed',
                    'messages' => [sprintf('Secret name "%s" is invalid.', $secretName)],
                    'masked_changes' => [],
                ];
            }

            if (null === $plainValue || '' === trim($plainValue)) {
                continue;
            }

            $result = $this->credentialOperator->set($environment, $secretName, $plainValue);
            $messages = array_merge($messages, $this->redactMessages($result->messages()));
            $maskedChanges[$fieldKey] = '********';

            if (!$result->successful()) {
                return [
                    'status' => 'failed',
                    'messages' => array_merge(['Secret replacement failed for '.$fieldKey.'.'], $messages),
                    'masked_changes' => $maskedChanges,
                ];
            }
        }

        return [
            'status' => 'applied',
            'messages' => $messages,
            'masked_changes' => $maskedChanges,
        ];
    }

    /**
     * @param list<string> $messages
     *
     * @return list<string>
     */
    private function redactMessages(array $messages): array
    {
        return array_map(
            static fn (string $message): string => (string) preg_replace('/(=|:|\s)([^\s]{12,})/', '$1********', $message),
            $messages,
        );
    }
}
