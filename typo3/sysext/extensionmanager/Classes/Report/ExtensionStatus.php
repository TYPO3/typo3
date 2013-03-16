<?php
namespace TYPO3\CMS\Extensionmanager\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Extension status reports
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ExtensionStatus implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * @var string
	 */
	protected $ok = '';

	/**
	 * @var string
	 */
	protected $upToDate = '';

	/**
	 * @var string
	 */
	protected $error = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
	 */
	protected $repositoryRepository = NULL;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility = NULL;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->repositoryRepository = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository');
		$this->listUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
	}

	/**
	 * Determines extension manager status
	 *
	 * @return array List of statuses
	 */
	public function getStatus() {
		$status = array();
		$status['mainRepositoryStatus'] = $this->getMainRepositoryStatus();

		$extensionStatus = $this->getSecurityStatusOfExtensions();
		$status['extensionsSecurityStatusInstalled'] = $extensionStatus->loaded;
		$status['extensionsSecurityStatusNotInstalled'] = $extensionStatus->existing;

		return $status;
	}

	/**
	 * Check main repository status: existance, has extensions, last update younger than 7 days
	 *
	 * @return \TYPO3\CMS\Reports\Report\Status\Status
	 */
	protected function getMainRepositoryStatus() {
		/** @var $mainRepository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository */
		$mainRepository = $this->repositoryRepository->findOneTypo3OrgRepository();

		if (is_null($mainRepository) === TRUE) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.notFound.value');
			$message = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.notFound.message');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
		} elseif ($mainRepository->getLastUpdate()->getTimestamp() < $GLOBALS['EXEC_TIME'] - 24 * 60 * 60 * 7) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.notUpToDate.value');
			$message = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.notUpToDate.message');
			$severity = \TYPO3\CMS\Reports\Status::NOTICE;
		} else {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.upToDate.value');
			$message = '';
			$severity = \TYPO3\CMS\Reports\Status::OK;
		}

		/** @var $status \TYPO3\CMS\Reports\Status */
		$status = $this->objectManager->get(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.mainRepository.title'),
			$value,
			$message,
			$severity
		);

		return $status;
	}

	/**
	 * Get security status of loaded and installed extensions
	 *
	 * @return \stdClass with properties 'loaded' and 'existing' containing a TYPO3\CMS\Reports\Report\Status\Status object
	 */
	protected function getSecurityStatusOfExtensions() {
		$extensionInformation = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
		$loadedInsecure = array();
		$existingInsecure = array();
		foreach ($extensionInformation as $extensionKey => $information) {
			if (
				array_key_exists('terObject', $information)
				&& $information['terObject'] instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
			) {
				/** @var $terObject \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
				$terObject = $information['terObject'];
				$insecureStatus = $terObject->getReviewState();
				if ($insecureStatus === -1) {
					if (
						array_key_exists('installed', $information)
						&& $information['installed'] === TRUE
					) {
						$loadedInsecure[] = array(
							'extensionKey' => $extensionKey,
							'version' => $terObject->getVersion(),
						);
					} else {
						$existingInsecure[] = array(
							'extensionKey' => $extensionKey,
							'version' => $terObject->getVersion(),
						);
					}
				}
			}
		}

		$result = new \stdClass();

		if (count($loadedInsecure) === 0) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.loadedExtensions.noInsecureExtensionLoaded.value');
			$message = '';
			$severity = \TYPO3\CMS\Reports\Status::OK;
		} else {
			$value = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.loadedExtensions.insecureExtensionLoaded.value'),
				count($loadedInsecure)
			);
			$extensionList = array();
			foreach ($loadedInsecure as $insecureExtension) {
				$extensionList[] = sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.loadedExtensions.insecureExtensionLoaded.message.extension'),
					$insecureExtension['extensionKey'],
					$insecureExtension['version']
				);
			}
			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.loadedExtensions.insecureExtensionLoaded.message'),
				implode('', $extensionList)
			);
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
		}
		$result->loaded = $this->objectManager->get(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.loadedExtensions.title'),
			$value,
			$message,
			$severity
		);

		if (count($existingInsecure) === 0) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.existingExtensions.noInsecureExtensionExists.value');
			$message = '';
			$severity = \TYPO3\CMS\Reports\Status::OK;
		} else {
			$value = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.existingExtensions.insecureExtensionExists.value'),
				count($existingInsecure)
			);
			$extensionList = array();
			foreach ($existingInsecure as $insecureExtension) {
				$extensionList[] = sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.existingExtensions.insecureExtensionExists.message.extension'),
					$insecureExtension['extensionKey'],
					$insecureExtension['version']
				);
			}
			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.existingExtensions.insecureExtensionExists.message'),
				implode('', $extensionList)
			);
			$severity = \TYPO3\CMS\Reports\Status::WARNING;
		}
		$result->existing = $this->objectManager->get(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:report.status.existingExtensions.title'),
			$value,
			$message,
			$severity
		);

		return $result;
	}
}
?>