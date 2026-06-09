<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Storage\Security;

use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Crypto\HashService;

/**
 * Guards direct DataHandler access to the form_definition table.
 *
 * FormDefinitionRepository grants a per-invocation token before each
 * DataHandler call. FormDefinitionDataHandlerHook verifies and consumes that
 * token; unauthorised DataHandler operations are rejected. This prevents
 * backend users from bypassing form persistence validation by writing directly
 * to the table (e.g. list module, impexp).
 *
 * Each token covers the command, the record identifier, and an HMAC of all
 * field-pairs (ksort-ordered), so neither the operation nor any individual
 * field value can be tampered with independently.
 *
 * @internal
 */
final class FormDefinitionPersistenceGuard
{
    private array $allowedInvocations = [];

    public function __construct(private readonly HashService $hashService) {}

    /**
     * Allows a single DataHandler invocation for the given command and record.
     * Write commands (create, update) must supply the exact field-pairs that
     * will be passed to DataHandler; delete passes null.
     *
     * Returns false if an identical invocation is already pending (duplicate).
     */
    public function allowInvocation(
        FormDefinitionPersistenceCommand $command,
        string|int $identifier,
        ?array $fields = null,
    ): bool {
        if ($this->findInvocationIndex($command, $identifier) !== null) {
            return false;
        }
        $item = [
            'command' => $command,
            'identifier' => $identifier,
        ];
        if ($fields !== null) {
            $processed = $this->processFields($fields);
            $item['names'] = $processed['names'];
            $item['hmac'] = $processed['hmac'];
        }
        $this->allowedInvocations[] = $item;
        return true;
    }

    /**
     * Returns true if a matching invocation has been granted and not yet consumed.
     * The provided fields must produce the same sorted key list and HMAC as
     * the fields that were registered via allowInvocation().
     */
    public function isInvocationAllowed(
        FormDefinitionPersistenceCommand $command,
        string|int $identifier,
        ?array $fields = null,
    ): bool {
        $index = $this->findInvocationIndex($command, $identifier);
        if ($index === null) {
            return false;
        }
        if ($fields === null) {
            return true;
        }
        $item = $this->allowedInvocations[$index];
        $processed = $this->processFields($fields);
        return $item['names'] === $processed['names'] && $item['hmac'] === $processed['hmac'];
    }

    /**
     * Consumes a matching invocation (removes it from the pending list).
     * Called both by the hook after successful verification (single-use
     * enforcement) and by the repository's finally block (cleanup).
     */
    public function consumeInvocation(
        FormDefinitionPersistenceCommand $command,
        string|int $identifier,
        ?array $fields = null,
    ): void {
        $index = $this->findInvocationIndex($command, $identifier);
        if ($index === null) {
            return;
        }
        if ($fields === null) {
            unset($this->allowedInvocations[$index]);
            return;
        }
        $item = $this->allowedInvocations[$index];
        $processed = $this->processFields($fields);
        if ($item['names'] === $processed['names'] && $item['hmac'] === $processed['hmac']) {
            unset($this->allowedInvocations[$index]);
        }
    }

    private function findInvocationIndex(FormDefinitionPersistenceCommand $command, string|int $identifier): ?int
    {
        foreach ($this->allowedInvocations as $index => $invocation) {
            if ($invocation['command'] === $command && $invocation['identifier'] === $identifier) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Sorts fields alphabetically and returns an array with keys 'names' and 'hmac'.
     *
     * @return array{names: list<string>, hmac: string}
     */
    private function processFields(array $fields): array
    {
        ksort($fields);
        return [
            'names' => array_keys($fields),
            'hmac' => $this->hashService->hmac(
                json_encode($fields, JSON_THROW_ON_ERROR),
                FormDefinitionPersistenceGuard::class,
                HashAlgo::SHA3_384
            ),
        ];
    }
}
