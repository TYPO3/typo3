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

namespace TYPO3\CMS\Redirects\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Redirects\Security\RedirectPermissionGuard;

/**
 * @internal This class is a specific TYPO3 hook implementation and is not part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class DataHandlerPermissionGuardHook
{
    public function __construct(
        private RedirectPermissionGuard $redirectPermissionGuard,
    ) {}

    /**
     * @param array<string, mixed>|null $incomingFieldArray
     */
    public function processDatamap_preProcessFieldArray(
        ?array &$incomingFieldArray,
        string $table,
        string|int $id,
        DataHandler $dataHandler,
    ): void {
        if ($table === 'sys_redirect' && !$this->redirectPermissionGuard->isAllowedRedirect($incomingFieldArray ?? [])) {
            // Reset incoming field array to avoid further processing in DataHandler
            // in case the given source host is not allowed for the current user
            $incomingFieldArray = null;

            if (MathUtility::canBeInterpretedAsInteger($id)) {
                // Record update
                $dataHandler->log(
                    'sys_redirect',
                    (int)$id,
                    SystemLogDatabaseAction::UPDATE,
                    null,
                    SystemLogErrorClassification::USER_ERROR,
                    'Attempt to modify sys_redirect record "%d" is disallowed',
                    null,
                    [$id],
                );
            } else {
                // New record
                $dataHandler->log(
                    'sys_redirect',
                    0,
                    SystemLogDatabaseAction::INSERT,
                    null,
                    SystemLogErrorClassification::USER_ERROR,
                    'Attempt to create a new sys_redirect record is disallowed',
                );
            }
        }
    }
}
