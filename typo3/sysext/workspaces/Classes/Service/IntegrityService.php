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

namespace TYPO3\CMS\Workspaces\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;

/**
 * @internal
 */
readonly class IntegrityService
{
    // Success status - everything is fine
    protected const STATUS_Success = 100;

    // Info status - nothing is wrong, but a notice is shown
    protected const STATUS_Info = 101;

    // Warning status - user interaction might be required
    protected const STATUS_Warning = 102;

    // Error status - user interaction is required
    protected const STATUS_Error = 103;

    protected const STATUS_Representation = [
        self::STATUS_Success => 'success',
        self::STATUS_Info => 'info',
        self::STATUS_Warning => 'warning',
        self::STATUS_Error => 'error',
    ];

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory
    ) {}

    /**
     * Sets the affected elements.
     *
     * @param CombinedRecord[] $affectedElements
     */
    public function check(array $affectedElements): array
    {
        $issues = [];
        foreach ($affectedElements as $affectedElement) {
            $issues = $this->checkElement($affectedElement, $issues);
        }
        return $issues;
    }

    /**
     * Get integrity of a single element.
     *
     * Check workspace localization integrity of a single element.
     * If current record is a localization and its localization parent is new in this
     * workspace, then both (localization and localization parent) should be published.
     *
     * The method returns an array of below shape, array keys are identifiers of table and version-id.
     *
     * 'tx_table:123' => [
     *    [
     *      'status' => 102,
     *      'message' => 'Element cannot be...',
     *    ],
     * ],
     */
    public function checkElement(CombinedRecord $element, array $existingIssues): array
    {
        $languageService = $this->getLanguageService();
        $table = $element->getTable();
        if (!$this->tcaSchemaFactory->has($table)) {
            return [];
        }
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->isLanguageAware()) {
            return [];
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageField = $languageCapability->getLanguageField()->getName();
        $languageParentField = $languageCapability->getTranslationOriginPointerField()->getName();
        $versionRow = $element->getVersionRecord()->getRow();
        // If element is a localization:
        if ($versionRow[$languageField] > 0) {
            // Get localization parent from live workspace
            $languageParentRecord = BackendUtility::getRecord($table, $versionRow[$languageParentField], 'uid,t3ver_state');
            // If localization parent is a new version....
            if (is_array($languageParentRecord) && VersionState::tryFrom($languageParentRecord['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                $title = BackendUtility::getRecordTitle($table, $versionRow);
                // Add warning for current versionized record:
                $existingIssues = $this->addIssue(
                    $existingIssues,
                    $element->getLiveRecord()->getIdentifier(),
                    self::STATUS_Warning,
                    sprintf($languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:integrity.dependsOnDefaultLanguageRecord'), $title)
                );
                // Add info for related localization parent record:
                $existingIssues = $this->addIssue(
                    $existingIssues,
                    $table . ':' . $languageParentRecord['uid'],
                    self::STATUS_Info,
                    sprintf($languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:integrity.isDefaultLanguageRecord'), $title)
                );
            }
        }
        return $existingIssues;
    }

    /**
     * Gets the status of the most important severity.
     * (low << success, info, warning, error >> high)
     *
     * @param string|null $identifier Record identifier (table:id) for look-ups
     */
    public function getStatus(array $issues, ?string $identifier = null): int
    {
        $status = self::STATUS_Success;
        if ($identifier === null) {
            foreach ($issues as $identifierIssues) {
                foreach ($identifierIssues as $issue) {
                    if ($status < $issue['status']) {
                        $status = $issue['status'];
                    }
                }
            }
        } else {
            foreach ($this->getIssues($issues, $identifier) as $issue) {
                if ($status < $issue['status']) {
                    $status = $issue['status'];
                }
            }
        }
        return $status;
    }

    /**
     * Gets the (human-readable) representation of the status with the most
     * important severity (wraps $this->getStatus() and translates the result).
     *
     * @param string|null $identifier Record identifier (table:id) for look-ups
     * @return string One out of success, info, warning, error
     */
    public function getStatusRepresentation(array $issues, ?string $identifier = null): string
    {
        return self::STATUS_Representation[$this->getStatus($issues, $identifier)];
    }

    /**
     * Gets issues, all or specific for one identifier.
     *
     * @param string|null $identifier Record identifier (table:id) for look-ups
     */
    public function getIssues(array $issues, ?string $identifier = null): array
    {
        if ($identifier === null) {
            return $issues;
        }
        if (isset($issues[$identifier])) {
            return $issues[$identifier];
        }
        return [];
    }

    /**
     * Gets the message of all issues.
     *
     * @param string|null $identifier Record identifier (table:id) for look-ups
     */
    public function getIssueMessages(array $issues, ?string $identifier = null): array
    {
        $messages = [];
        if ($identifier === null) {
            foreach ($issues as $identifierIssues) {
                foreach ($identifierIssues as $issue) {
                    $messages[] = $issue['message'];
                }
            }
        } else {
            foreach ($this->getIssues($issues, $identifier) as $issue) {
                $messages[] = $issue['message'];
            }
        }
        return $messages;
    }

    /**
     * Adds an issue to array of existing issues.
     *
     * @param string $identifier Record identifier (table:id)
     * @param int $status Status code (see constants)
     * @param string $message Message/description of the issue
     */
    protected function addIssue(array $existingIssues, string $identifier, int $status, string $message): array
    {
        if (!isset($existingIssues[$identifier])) {
            $existingIssues[$identifier] = [];
        }
        $existingIssues[$identifier][] = [
            'status' => $status,
            'message' => $message,
        ];
        return $existingIssues;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
