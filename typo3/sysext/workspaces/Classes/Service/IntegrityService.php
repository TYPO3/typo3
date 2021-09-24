<?php

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
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;

/**
 * Service for integrity
 */
class IntegrityService
{
    /**
     * Success status - everything is fine
     *
     * @var int
     */
    const STATUS_Succes = 100;
    /**
     * Info status - nothing is wrong, but a notice is shown
     *
     * @var int
     */
    const STATUS_Info = 101;
    /**
     * Warning status - user interaction might be required
     *
     * @var int
     */
    const STATUS_Warning = 102;
    /**
     * Error status - user interaction is required
     *
     * @var int
     */
    const STATUS_Error = 103;
    /**
     * @var array
     */
    protected $statusRepresentation = [
        self::STATUS_Succes => 'success',
        self::STATUS_Info => 'info',
        self::STATUS_Warning => 'warning',
        self::STATUS_Error => 'error',
    ];

    /**
     * @var CombinedRecord[]
     */
    protected $affectedElements;

    /**
     * Array storing all issues that have been checked and
     * found during runtime in this object. The array keys
     * are identifiers of table and the version-id.
     *
     * 'tx_table:123' => array(
     * array(
     * 'status' => 'warning',
     * 'message' => 'Element cannot be...',
     * )
     * )
     *
     * @var array
     */
    protected $issues = [];

    /**
     * Sets the affected elements.
     *
     * @param CombinedRecord[] $affectedElements
     */
    public function setAffectedElements(array $affectedElements)
    {
        $this->affectedElements = $affectedElements;
    }

    /**
     * Checks integrity of affected records.
     */
    public function check()
    {
        foreach ($this->affectedElements as $affectedElement) {
            $this->checkElement($affectedElement);
        }
    }

    /**
     * Checks a single element.
     *
     * @param CombinedRecord $element
     */
    public function checkElement(CombinedRecord $element)
    {
        $this->checkLocalization($element);
    }

    /**
     * Checks workspace localization integrity of a single elements.
     * If current record is a localization and its localization parent
     * is new in this workspace,
     * then both (localization and localization parent) should be published.
     *
     * @param CombinedRecord $element
     */
    protected function checkLocalization(CombinedRecord $element)
    {
        $table = $element->getTable();
        if (BackendUtility::isTableLocalizable($table)) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $languageParentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
            $versionRow = $element->getVersionRecord()->getRow();
            // If element is a localization:
            if ($versionRow[$languageField] > 0) {
                // Get localization parent from live workspace
                $languageParentRecord = BackendUtility::getRecord($table, $versionRow[$languageParentField], 'uid,t3ver_state');
                // If localization parent is a new version....
                if (VersionState::cast($languageParentRecord['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER)) {
                    $title = BackendUtility::getRecordTitle($table, $versionRow);
                    $languageService = $this->getLanguageService();
                    // Add warning for current versionized record:
                    $this->addIssue(
                        $element->getLiveRecord()->getIdentifier(),
                        self::STATUS_Warning,
                        sprintf($languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:integrity.dependsOnDefaultLanguageRecord'), $title)
                    );
                    // Add info for related localization parent record:
                    $this->addIssue(
                        $table . ':' . $languageParentRecord['uid'],
                        self::STATUS_Info,
                        sprintf($languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:integrity.isDefaultLanguageRecord'), $title)
                    );
                }
            }
        }
    }

    /**
     * Gets the status of the most important severity.
     * (low << success, info, warning, error >> high)
     *
     * @param string $identifier Record identifier (table:id) for look-ups
     * @return string
     */
    public function getStatus($identifier = null)
    {
        $status = self::STATUS_Succes;
        if ($identifier === null) {
            foreach ($this->issues as $idenfieriferIssues) {
                foreach ($idenfieriferIssues as $issue) {
                    if ($status < $issue['status']) {
                        $status = $issue['status'];
                    }
                }
            }
        } else {
            foreach ($this->getIssues($identifier) as $issue) {
                if ($status < $issue['status']) {
                    $status = $issue['status'];
                }
            }
        }
        return $status;
    }

    /**
     * Gets the (human readable) representation of the status with the most
     * important severity (wraps $this->getStatus() and translates the result).
     *
     * @param string $identifier Record identifier (table:id) for look-ups
     * @return string One out of success, info, warning, error
     */
    public function getStatusRepresentation($identifier = null)
    {
        return $this->statusRepresentation[$this->getStatus($identifier)];
    }

    /**
     * Gets issues, all or specific for one identifier.
     *
     * @param string $identifier Record identifier (table:id) for look-ups
     * @return array
     */
    public function getIssues($identifier = null)
    {
        if ($identifier === null) {
            return $this->issues;
        }
        if (isset($this->issues[$identifier])) {
            return $this->issues[$identifier];
        }
        return [];
    }

    /**
     * Gets the message of all issues.
     *
     * @param string $identifier Record identifier (table:id) for look-ups
     * @param bool $asString Return results as string instead of array
     * @return array|string
     */
    public function getIssueMessages($identifier = null, $asString = false)
    {
        $messages = [];
        if ($identifier === null) {
            foreach ($this->issues as $idenfieriferIssues) {
                foreach ($idenfieriferIssues as $issue) {
                    $messages[] = $issue['message'];
                }
            }
        } else {
            foreach ($this->getIssues($identifier) as $issue) {
                $messages[] = $issue['message'];
            }
        }
        if ($asString) {
            $messages = implode('<br/>', $messages);
        }
        return $messages;
    }

    /**
     * Adds an issue.
     *
     * @param string $identifier Record identifier (table:id)
     * @param int $status Status code (see constants)
     * @param string $message Message/description of the issue
     */
    protected function addIssue($identifier, $status, $message)
    {
        if (!isset($this->issues[$identifier])) {
            $this->issues[$identifier] = [];
        }
        $this->issues[$identifier][] = [
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
