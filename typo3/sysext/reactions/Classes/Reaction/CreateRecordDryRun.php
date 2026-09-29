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

namespace TYPO3\CMS\Reactions\Reaction;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;

/**
 * Tells what a create-record reaction would do with a payload, without writing anything.
 *
 * @internal This is a specific reaction implementation and is not considered part of the Public TYPO3 API.
 */
readonly class CreateRecordDryRun
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private LanguageServiceFactory $languageServiceFactory,
        private Context $context,
    ) {}

    /**
     * Resolves the payload as the user the reaction impersonates. Its groups are fetched,
     * since the field permissions depend on them, but it is not started: starting it writes
     * its uc. It is made the global user for as long as the payload is resolved, as the
     * endpoint does, since item processors read it from there, and the one who asks is
     * restored afterwards.
     *
     * A payload the endpoint would read as empty is passed as null and not resolved: every
     * field would only be reported as skipped for it.
     */
    public function run(ReactionInstruction $reaction, CreateRecordReaction $implementation, ?array $payload): CreateRecordDryRunResult
    {
        $user = GeneralUtility::makeInstance(ReactionUserAuthentication::class);
        $user->setReactionInstruction($reaction);
        if (!empty($user->user['uid'])) {
            $user->fetchGroupData();
        }

        $resolution = null;
        $fieldLabels = [];
        if ($payload !== null) {
            $askingUser = $GLOBALS['BE_USER'];
            $askingLanguageService = $GLOBALS['LANG'];
            $GLOBALS['BE_USER'] = $user;
            $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($user);
            try {
                $resolution = $implementation->resolve($payload, $reaction, $user);
            } finally {
                $GLOBALS['BE_USER'] = $askingUser;
                $GLOBALS['LANG'] = $askingLanguageService;
            }
            $fieldLabels = $this->getFieldLabels($resolution);
        }

        return new CreateRecordDryRunResult(
            $resolution,
            $this->findObstacles($reaction, $user),
            (string)($user->user['username'] ?? ''),
            $fieldLabels,
        );
    }

    /**
     * These are the checks DataHandler rejects a record for most often. The allowed record
     * types of a page and the root level of a table are left to DataHandler.
     *
     * @return list<DryRunObstacle>
     */
    protected function findObstacles(ReactionInstruction $reaction, BackendUserAuthentication $user): array
    {
        $record = $reaction->toArray();
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $endtime = (int)($record['endtime'] ?? 0);
        $obstacles = [];
        if ((bool)($record['disabled'] ?? false)) {
            $obstacles[] = DryRunObstacle::DISABLED;
        }
        if ((int)($record['starttime'] ?? 0) > $now) {
            $obstacles[] = DryRunObstacle::SCHEDULED;
        }
        if ($endtime > 0 && $endtime <= $now) {
            $obstacles[] = DryRunObstacle::EXPIRED;
        }
        if (empty($user->user['uid'])) {
            $obstacles[] = DryRunObstacle::NO_USER;
            return $obstacles;
        }

        $table = (string)($record['table_name'] ?? '');
        if (!$this->tcaSchemaFactory->has($table)) {
            // Answered by the resolution, which names the table as invalid.
            return $obstacles;
        }
        if (!$user->isAdmin()
            && ($this->tcaSchemaFactory->get($table)->hasCapability(TcaSchemaCapability::AccessAdminOnly) || !$user->check('tables_modify', $table))
        ) {
            $obstacles[] = DryRunObstacle::TABLE_NOT_PERMITTED;
        }
        $storagePid = (int)($record['storage_pid'] ?? 0);
        if ($storagePid === 0) {
            if (!$user->isAdmin()) {
                $obstacles[] = DryRunObstacle::PAGE_NOT_PERMITTED;
            }
            return $obstacles;
        }
        $page = BackendUtility::getRecord('pages', $storagePid);
        if ($page === null) {
            $obstacles[] = DryRunObstacle::PAGE_MISSING;
        } elseif (!$user->isInWebMount($page)
            || !$user->doesUserHaveAccess($page, $table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT)
        ) {
            $obstacles[] = DryRunObstacle::PAGE_NOT_PERMITTED;
        }
        return $obstacles;
    }

    /**
     * @return array<string, string>
     */
    protected function getFieldLabels(CreateRecordResolution $resolution): array
    {
        $schema = $this->tcaSchemaFactory->has($resolution->table) ? $this->tcaSchemaFactory->get($resolution->table) : null;
        $labels = [];
        foreach (array_keys($resolution->fields + $resolution->skippedFields) as $fieldName) {
            $labels[$fieldName] = $schema?->hasField($fieldName) ? $schema->getField($fieldName)->getLabel() : '';
        }
        return $labels;
    }
}
