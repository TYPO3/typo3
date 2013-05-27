<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Service for integrity
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class IntegrityService {

	/**
	 * Succes status - everything is fine
	 *
	 * @var integer
	 */
	const STATUS_Succes = 100;
	/**
	 * Info status - nothing is wrong, but a notice is shown
	 *
	 * @var integer
	 */
	const STATUS_Info = 101;
	/**
	 * Warning status - user interaction might be required
	 *
	 * @var integer
	 */
	const STATUS_Warning = 102;
	/**
	 * Error status - user interaction is required
	 *
	 * @var integer
	 */
	const STATUS_Error = 103;
	/**
	 * @var array
	 */
	protected $statusRepresentation = array(
		self::STATUS_Succes => 'success',
		self::STATUS_Info => 'info',
		self::STATUS_Warning => 'warning',
		self::STATUS_Error => 'error'
	);

	/**
	 * @var Tx_Workspaces_Domain_Model_CombinedRecord[]
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
	protected $issues = array();

	/**
	 * Sets the affected elements.
	 *
	 * @param Tx_Workspaces_Domain_Model_CombinedRecord[] $affectedElements
	 * @return void
	 */
	public function setAffectedElements(array $affectedElements) {
		$this->affectedElements = $affectedElements;
	}

	/**
	 * Checks integrity of affected records.
	 *
	 * @return void
	 */
	public function check() {
		foreach ($this->affectedElements as $affectedElement) {
			$this->checkElement($affectedElement);
		}
	}

	/**
	 * Checks a single element.
	 *
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $element
	 * @return void
	 */
	public function checkElement(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $element) {
		$this->checkLocalization($element);
	}

	/**
	 * Checks workspace localization integrity of a single elements.
	 * If current record is a localization and its localization parent
	 * is new in this workspace (has only a placeholder record in live),
	 * then boths (localization and localization parent) should be published.
	 *
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $element
	 * @return void
	 */
	protected function checkLocalization(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $element) {
		$table = $element->getTable();
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table)) {
			$languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$languageParentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
			$versionRow = $element->getVersionRecord()->getRow();
			// If element is a localization:
			if ($versionRow[$languageField] > 0) {
				// Get localization parent from live workspace:
				$languageParentRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $versionRow[$languageParentField], 'uid,t3ver_state');
				// If localization parent is a "new placeholder" record:
				if ($languageParentRecord['t3ver_state'] == 1) {
					$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $versionRow);
					// Add warning for current versionized record:
					$this->addIssue($element->getLiveRecord()->getIdentifier(), self::STATUS_Warning, sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('integrity.dependsOnDefaultLanguageRecord', 'workspaces'), $title));
					// Add info for related localization parent record:
					$this->addIssue($table . ':' . $languageParentRecord['uid'], self::STATUS_Info, sprintf(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('integrity.isDefaultLanguageRecord', 'workspaces'), $title));
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
	public function getStatus($identifier = NULL) {
		$status = self::STATUS_Succes;
		if ($identifier === NULL) {
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
	 * Gets the (human readable) represetation of the status with the most
	 * important severity (wraps $this->getStatus() and translates the result).
	 *
	 * @param string $identifier Record identifier (table:id) for look-ups
	 * @return string One out of success, info, warning, error
	 */
	public function getStatusRepresentation($identifier = NULL) {
		return $this->statusRepresentation[$this->getStatus($identifier)];
	}

	/**
	 * Gets issues, all or specific for one identifier.
	 *
	 * @param string $identifier Record identifier (table:id) for look-ups
	 * @return array
	 */
	public function getIssues($identifier = NULL) {
		if ($identifier === NULL) {
			return $this->issues;
		} elseif (isset($this->issues[$identifier])) {
			return $this->issues[$identifier];
		}
		return array();
	}

	/**
	 * Gets the message of all issues.
	 *
	 * @param string $identifier Record identifier (table:id) for look-ups
	 * @param boolean $asString Return results as string instead of array
	 * @return array|string
	 */
	public function getIssueMessages($identifier = NULL, $asString = FALSE) {
		$messages = array();
		if ($identifier === NULL) {
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
	 * @param integer $status Status code (see constants)
	 * @param string $message Message/description of the issue
	 * @return void
	 */
	protected function addIssue($identifier, $status, $message) {
		if (!isset($this->issues[$identifier])) {
			$this->issues[$identifier] = array();
		}
		$this->issues[$identifier][] = array(
			'status' => $status,
			'message' => $message
		);
	}

}


?>