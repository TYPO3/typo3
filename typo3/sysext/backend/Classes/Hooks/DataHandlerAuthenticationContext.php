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

namespace TYPO3\CMS\Backend\Hooks;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Middleware\SudoModeInterceptor;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessFactory;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeRequiredEvent;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * DataHandler hook to ensure that columns that require a specific
 * authenticationContext are covered by a recent step-up authentication
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class DataHandlerAuthenticationContext
{
    public function __construct(
        private AccessFactory $factory,
        private AccessStorage $storage,
        private TcaSchemaFactory $tcaSchemaFactory,
        private SudoModeInterceptor $sudoModeInterceptor,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function processDatamap_postProcessFieldArray(
        string $mode,
        string $tableName,
        string|int $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($dataHandler->isImporting || $dataHandler->bypassAccessCheckForRecords) {
            return;
        }
        $request = $this->sudoModeInterceptor->currentRequest;
        if ($request === null) {
            // Not in a backend request where the sudoMode interceptor middleware was active
            return;
        }

        $schema = $this->tcaSchemaFactory->get($tableName);
        $subjects = [];
        $id = (string)$id;
        foreach ($schema->getFields() as $columnName => $fieldInformation) {
            $authenticationContextConfig = $fieldInformation->getConfiguration()['authenticationContext'] ?? null;
            if ($authenticationContextConfig === null) {
                continue;
            }
            $subject = $this->factory->buildTableAccessSubject(
                $tableName,
                $columnName,
                str_starts_with($id, 'NEW') ? 'NEW' : $id,
                $authenticationContextConfig
            );
            $grants = $this->storage->findGrantsBySubject($subject);
            $hasGrant = $grants !== [];
            $subjects[$columnName] = [
                'subject' => $subject,
                'grants' => $grants,
                'hasGrant' => $hasGrant,
            ];
        }

        $requiredSubjects = [];
        foreach ($fieldArray as $identifier => $value) {
            $subjectInfo = $subjects[$identifier] ?? null;
            if ($subjectInfo === null) {
                continue;
            }
            if ($subjectInfo['hasGrant']) {
                continue;
            }

            $subject = $subjectInfo['subject'];
            $requiredSubjects[$subject->getIdentity()] = $subject;
        }

        if ($requiredSubjects !== []) {
            $claim = $this->factory->buildClaimForSubjectRequest($request, self::class, ...array_values($requiredSubjects));

            $event = new SudoModeRequiredEvent($claim);
            $this->eventDispatcher->dispatch($event);
            if ($event->isVerificationRequired()) {
                throw (new VerificationRequiredException(
                    'Authentication Context Confirmation Required',
                    1743597646
                ))->withClaim($claim);
            }
        }

        $this->consumeNonRepeatableGrants($subjects);
    }

    private function consumeNonRepeatableGrants(array $subjects): void
    {
        // Consume (remove from storage) non-repeatable grants.
        // Non-repeatable grants have been marked with `once` in the subject configration.
        foreach ($subjects as $columnName => $subjectInfo) {
            if ($subjectInfo['subject']->isOnce()) {
                foreach ($subjectInfo['grants'] as $grant) {
                    $this->storage->removeGrant($grant);
                }
            }
        }
    }
}
