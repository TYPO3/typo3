<?php
namespace TYPO3\CMS\Reports\Report\Status;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * The status report
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class Status implements \TYPO3\CMS\Reports\ReportInterface {

	/**
	 * @var array
	 */
	protected $statusProviders = array();

	/**
	 * Constructor for class tx_reports_report_Status
	 */
	public function __construct() {
		$this->getStatusProviders();
		$GLOBALS['LANG']->includeLLFile('EXT:reports/reports/locallang.xml');
	}

	/**
	 * Takes care of creating / rendering the status report
	 *
	 * @return string The status report as HTML
	 */
	public function getReport() {
		$content = '';
		$status = $this->getSystemStatus();
		$highestSeverity = $this->getHighestSeverity($status);
		// Updating the registry
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
		$content .= '<p class="help">' . $GLOBALS['LANG']->getLL('status_report_explanation') . '</p>';
		return $content . $this->renderStatus($status);
	}

	/**
	 * Gets all registered status providers and creates instances of them.
	 *
	 * @return void
	 */
	protected function getStatusProviders() {
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'] as $key => $statusProvidersList) {
			$this->statusProviders[$key] = array();
			foreach ($statusProvidersList as $statusProvider) {
				$statusProviderInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($statusProvider);
				if ($statusProviderInstance instanceof \TYPO3\CMS\Reports\StatusProviderInterface) {
					$this->statusProviders[$key][] = $statusProviderInstance;
				}
			}
		}
	}

	/**
	 * Runs through all status providers and returns all statuses collected.
	 *
	 * @return array An array of tx_reports_reports_status_Status objects
	 */
	public function getSystemStatus() {
		$status = array();
		foreach ($this->statusProviders as $statusProviderId => $statusProviderList) {
			$status[$statusProviderId] = array();
			foreach ($statusProviderList as $statusProvider) {
				$statuses = $statusProvider->getStatus();
				$status[$statusProviderId] = array_merge($status[$statusProviderId], $statuses);
			}
		}
		return $status;
	}

	/**
	 * Determines the highest severity from the given statuses.
	 *
	 * @param array $statusCollection An array of tx_reports_reports_status_Status objects.
	 * @return integer The highest severity found from the statuses.
	 */
	public function getHighestSeverity(array $statusCollection) {
		$highestSeverity = \TYPO3\CMS\Reports\Status::NOTICE;
		foreach ($statusCollection as $statusProvider => $providerStatuses) {
			/** @var $status \TYPO3\CMS\Reports\Status */
			foreach ($providerStatuses as $status) {
				if ($status->getSeverity() > $highestSeverity) {
					$highestSeverity = $status->getSeverity();
				}
				// Reached the highest severity level, no need to go on
				if ($highestSeverity == \TYPO3\CMS\Reports\Status::ERROR) {
					break;
				}
			}
		}
		return $highestSeverity;
	}

	/**
	 * Renders the system's status
	 *
	 * @param array $statusCollection An array of statuses as returned by the available status providers
	 * @return string The system status as an HTML table
	 */
	protected function renderStatus(array $statusCollection) {
		// TODO refactor into separate methods, status list and single status
		$content = '';
		$template = '
		<div class="typo3-message message-###CLASS###">
			<div class="header-container">
				<div class="message-header message-left">###HEADER###</div>
				<div class="message-header message-right">###STATUS###</div>
			</div>
			<div class="message-body">###CONTENT###</div>
		</div>';
		$statuses = $this->sortStatusProviders($statusCollection);
		foreach ($statuses as $provider => $providerStatus) {
			$providerState = $this->sortStatuses($providerStatus);
			$id = str_replace(' ', '-', $provider);
			$classes = array(
				\TYPO3\CMS\Reports\Status::NOTICE => 'notice',
				\TYPO3\CMS\Reports\Status::INFO => 'information',
				\TYPO3\CMS\Reports\Status::OK => 'ok',
				\TYPO3\CMS\Reports\Status::WARNING => 'warning',
				\TYPO3\CMS\Reports\Status::ERROR => 'error'
			);
			$icon[\TYPO3\CMS\Reports\Status::WARNING] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-warning');
			$icon[\TYPO3\CMS\Reports\Status::ERROR] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-error');
			$messages = '';
			$headerIcon = '';
			$sectionSeverity = 0;
			/** @var $status \TYPO3\CMS\Reports\Status */
			foreach ($providerState as $status) {
				$severity = $status->getSeverity();
				$sectionSeverity = $severity > $sectionSeverity ? $severity : $sectionSeverity;
				$messages .= strtr($template, array(
					'###CLASS###' => $classes[$severity],
					'###HEADER###' => $status->getTitle(),
					'###STATUS###' => $status->getValue(),
					'###CONTENT###' => $status->getMessage()
				));
			}
			if ($sectionSeverity > 0) {
				$headerIcon = $icon[$sectionSeverity];
			}
			$content .= $GLOBALS['TBE_TEMPLATE']->collapseableSection($headerIcon . $provider, $messages, $id, 'reports.states');
		}
		return $content;
	}

	/**
	 * Sorts the status providers (alphabetically and puts primary status providers at the beginning)
	 *
	 * @param array $statusCollection A collection of statuses (with providers)
	 * @return array The collection of statuses sorted by provider (beginning with provider "_install")
	 */
	protected function sortStatusProviders(array $statusCollection) {
		// Extract the primary status collections, i.e. the status groups
		// that must appear on top of the status report
		// Change their keys to localized collection titles
		$primaryStatuses = array(
			$GLOBALS['LANG']->getLL('status_typo3') => $statusCollection['typo3'],
			$GLOBALS['LANG']->getLL('status_system') => $statusCollection['system'],
			$GLOBALS['LANG']->getLL('status_security') => $statusCollection['security'],
			$GLOBALS['LANG']->getLL('status_configuration') => $statusCollection['configuration']
		);
		unset($statusCollection['typo3'], $statusCollection['system'], $statusCollection['security'], $statusCollection['configuration']);
		// Assemble list of secondary status collections with left-over collections
		// Change their keys using localized labels if available
		// TODO extract into getLabel() method
		$secondaryStatuses = array();
		foreach ($statusCollection as $statusProviderId => $collection) {
			$label = '';
			if (strpos($statusProviderId, 'LLL:') === 0) {
				// Label provided by extension
				$label = $GLOBALS['LANG']->sL($statusProviderId);
			} else {
				// Generic label
				$label = $GLOBALS['LANG']->getLL('status_' . $statusProviderId);
			}
			$providerLabel = empty($label) ? $statusProviderId : $label;
			$secondaryStatuses[$providerLabel] = $collection;
		}
		// Sort the secondary status collections alphabetically
		ksort($secondaryStatuses);
		$orderedStatusCollection = array_merge($primaryStatuses, $secondaryStatuses);
		return $orderedStatusCollection;
	}

	/**
	 * Sorts the statuses by severity
	 *
	 * @param array $statusCollection A collection of statuses per provider
	 * @return array The collection of statuses sorted by severity
	 */
	protected function sortStatuses(array $statusCollection) {
		$statuses = array();
		$sortTitle = array();
		/** @var $status \TYPO3\CMS\Reports\Status */
		foreach ($statusCollection as $status) {
			if ($status->getTitle() === 'TYPO3') {
				$header = $status;
				continue;
			}
			$statuses[] = $status;
			$sortTitle[] = $status->getSeverity();
		}
		array_multisort($sortTitle, SORT_DESC, $statuses);
		// Making sure that the core version information is always on the top
		if (is_object($header)) {
			array_unshift($statuses, $header);
		}
		return $statuses;
	}

}


?>