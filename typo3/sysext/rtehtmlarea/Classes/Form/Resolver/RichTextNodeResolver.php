<?php
namespace TYPO3\CMS\Rtehtmlarea\Form\Resolver;

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

use TYPO3\CMS\Backend\Form\NodeResolverInterface;
use TYPO3\CMS\Rtehtmlarea\Form\Element\RichTextElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * This resolver will return the RichTextElement render class of ext:rtehtmlarea if RTE is enabled for this field.
 */
class RichTextNodeResolver implements NodeResolverInterface {

	/**
	 * Global options from NodeFactory
	 *
	 * @var array
	 */
	protected $globalOptions;

	/**
	 * Set global options from parent instance
	 *
	 * @param array $globalOptions Global options like 'readonly' for all elements
	 * @return $this
	 */
	public function setGlobalOptions(array $globalOptions) {
		$this->globalOptions = $globalOptions;
		return $this;
	}

	/**
	 * Returns RichTextElement as class name if RTE widget should be rendered.
	 *
	 * @return string|void New class name or void if this resolver does not change current class name.
	 */
	public function resolve() {
		$table = $this->globalOptions['table'];
		$fieldName = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];
		$backendUser = $this->getBackendUserAuthentication();

		if (
			// Whole thing is not read only
			empty($this->globalOptions['renderReadonly'])
			// This field is not read only
			&& !$parameterArray['fieldConf']['config']['readOnly']
			// If RTE is generally enabled by user settings and RTE object registry can return something valid
			&& $backendUser->isRTE()
		) {
			$specialConfiguration = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
			// If "richtext" is within defaultExtras
			if (isset($specialConfiguration['richtext'])) {
				// Operates by reference on $row! 'pid' is changed ...
				BackendUtility::fixVersioningPid($table, $row);
				list($recordPid, $tsConfigPid) = BackendUtility::getTSCpidCached($table, $row['uid'], $row['pid']);
				// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($tsConfigPid >= 0) {
					// Fetch page ts config and do some magic with it to find out if RTE is disabled on TS level.
					$rteSetup = $backendUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($recordPid));
					$rteTcaTypeValue = BackendUtility::getTCAtypeValue($table, $row);
					$rteSetupConfiguration = BackendUtility::RTEsetup($rteSetup['properties'], $table, $fieldName, $rteTcaTypeValue);
					if (!$rteSetupConfiguration['disabled']) {
						// Finally, we're sure the editor should really be rendered ...
						return RichtextElement::class;
					}
				}
			}
		}
		return NULL;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
