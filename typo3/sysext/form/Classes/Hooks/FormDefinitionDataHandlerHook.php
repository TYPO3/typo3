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

namespace TYPO3\CMS\Form\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Form\Storage\Security\FormDefinitionPersistenceCommand;
use TYPO3\CMS\Form\Storage\Security\FormDefinitionPersistenceGuard;

/**
 * Denies direct DataHandler write access to the form_definition table unless
 * FormDefinitionPersistenceGuard has granted a matching invocation. This
 * ensures form definitions can only be persisted through DatabaseStorageAdapter,
 * which applies the form persistence manager's validation and permission checks.
 *
 * Registered as processDatamapClass (create/update) and processCmdmapClass
 * (delete) so each path carries command, identifier, and field-level checks.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class FormDefinitionDataHandlerHook
{
    public function __construct(
        private FormDefinitionPersistenceGuard $guard,
    ) {}

    /**
     * Verifies create/update operations against the pending invocation grant.
     *
     * The command is derived from $id: a NEW-prefixed string means create,
     * an integer means update. The full incoming field array is matched against
     * the stored field names and HMAC. Setting $incomingFieldArray to null
     * cancels the record in DataHandler.
     *
     * @param array<string, mixed>|null $incomingFieldArray
     * @param-out array<string, mixed>|null $incomingFieldArray
     */
    public function processDatamap_preProcessFieldArray(
        ?array &$incomingFieldArray,
        string $table,
        string|int $id,
        DataHandler $dataHandler,
    ): void {
        if ($table !== 'form_definition') {
            return;
        }

        $isUpdate = MathUtility::canBeInterpretedAsInteger($id);
        $command = $isUpdate
            ? FormDefinitionPersistenceCommand::Update
            : FormDefinitionPersistenceCommand::Create;
        $recordIdentifier = $isUpdate ? (int)$id : (string)$id;

        if (!$this->guard->isInvocationAllowed($command, $recordIdentifier, $incomingFieldArray)) {
            $incomingFieldArray = null;
            $dataHandler->log(
                $table,
                $isUpdate ? (int)$id : 0,
                $isUpdate ? SystemLogDatabaseAction::UPDATE : SystemLogDatabaseAction::INSERT,
                null,
                SystemLogErrorClassification::USER_ERROR,
                'Persisting form definition "%s" via DataHandler is denied',
                null,
                [$id],
            );
            return;
        }

        $this->guard->consumeInvocation($command, $recordIdentifier, $incomingFieldArray);
    }

    /**
     * Blocks unauthorised delete commands on form_definition records.
     *
     * Sets $commandIsProcessed to true (preventing DataHandler's built-in
     * delete) when no COMMAND_FORM_DELETE grant is pending. This mirrors the
     * FilePersistenceSlot pattern for FAL-based form definitions.
     */
    public function processCmdmap(
        string $command,
        string $table,
        int|string $id,
        mixed $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler,
    ): void {
        if ($table !== 'form_definition' || $command !== 'delete') {
            return;
        }

        if (!$this->guard->isInvocationAllowed(FormDefinitionPersistenceCommand::Delete, (int)$id)) {
            $commandIsProcessed = true;
            $dataHandler->log(
                $table,
                (int)$id,
                SystemLogDatabaseAction::DELETE,
                null,
                SystemLogErrorClassification::USER_ERROR,
                'Deleting form definition "%s" via DataHandler is denied',
                null,
                [$id],
            );
            return;
        }

        $this->guard->consumeInvocation(FormDefinitionPersistenceCommand::Delete, (int)$id);
    }
}
