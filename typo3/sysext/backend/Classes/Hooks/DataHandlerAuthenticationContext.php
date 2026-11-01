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
use Psr\Http\Message\ServerRequestInterface;
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

    /**
     * Copying a record writes child records (e.g. file references) before the
     * copied record itself is passed through the datamap. Verify the step-up
     * authentication upfront, so nothing is written if it is still required.
     *
     * Only direct copy commands are checked; records copied as part of a page
     * or as inline children are not.
     */
    public function processCmdmap_preProcess(
        string $command,
        string $tableName,
        int $id,
        mixed $value,
        DataHandler $dataHandler,
        mixed $pasteUpdate
    ): void {
        if ($command !== 'copy' || $dataHandler->isImporting) {
            return;
        }
        $request = $this->sudoModeInterceptor->currentRequest;
        if ($request === null) {
            // Not in a backend request where the sudoMode interceptor middleware was active
            return;
        }

        $requiredSubjects = [];
        foreach ($this->buildSubjects($tableName, 'NEW') as $subjectInfo) {
            if (!$subjectInfo['hasGrant']) {
                $subject = $subjectInfo['subject'];
                $requiredSubjects[$subject->getIdentity()] = $subject;
            }
        }
        $this->requireVerification($request, $requiredSubjects);
    }

    public function processDatamap_postProcessFieldArray(
        string $mode,
        string $tableName,
        string|int $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        if ($dataHandler->isImporting) {
            return;
        }
        $request = $this->sudoModeInterceptor->currentRequest;
        if ($request === null) {
            // Not in a backend request where the sudoMode interceptor middleware was active
            return;
        }

        $id = (string)$id;
        $subjects = $this->buildSubjects($tableName, str_starts_with($id, 'NEW') ? 'NEW' : $id);

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

        $this->requireVerification($request, $requiredSubjects);

        $this->consumeNonRepeatableGrants($subjects);
    }

    private function buildSubjects(string $tableName, string $id): array
    {
        $schema = $this->tcaSchemaFactory->get($tableName);
        $subjects = [];
        foreach ($schema->getFields() as $columnName => $fieldInformation) {
            $authenticationContextConfig = $fieldInformation->getConfiguration()['authenticationContext'] ?? null;
            if ($authenticationContextConfig === null) {
                continue;
            }
            $subject = $this->factory->buildTableAccessSubject(
                $tableName,
                $columnName,
                $id,
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
        return $subjects;
    }

    private function requireVerification(ServerRequestInterface $request, array $requiredSubjects): void
    {
        if ($requiredSubjects === []) {
            return;
        }
        $claim = $this->factory->buildClaimForSubjectRequest($request, self::class, ...array_values($requiredSubjects));

        $event = new SudoModeRequiredEvent($claim);
        $this->eventDispatcher->dispatch($event);
        if ($event->isVerificationRequired()) {
            throw new VerificationRequiredException(
                'Authentication Context Confirmation Required',
                1743597646
            )->withClaim($claim);
        }
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
