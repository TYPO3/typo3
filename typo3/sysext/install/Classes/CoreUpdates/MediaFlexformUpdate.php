<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Migrates the old media FlexForm to the new
 */
class MediaFlexformUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'FlexForm Data from Media Element';

	/**
	 * Checks whether updates need to be performed
	 *
	 * @param string &$description The description for the update
	 * @param integer &$showUpdate 0=dont show update; 1=show update and next button; 2=only show description
	 * @return boolean
	 */
	public function checkForUpdate(&$description, &$showUpdate = 0) {
		$mediaElements = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'], 'CType = "media" AND pi_flexform LIKE "%<sheet index=\\"sDEF\\">%"');
		if ($mediaElements > 0) {
			$description = 'You have media elements within your installation. As the structure of the flexform changed, your data needs to be migrated.';
			$showUpdate = 1;
		} else {
			$description = 'You currently have no media elements within your installation. Therefore nothing to be migrated';
			$showUpdate = 0;
		}
		return $showUpdate > 0;
	}

	/**
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		whether the updated was made or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pi_flexform', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'], 'CType = "media" AND pi_flexform LIKE "%<sheet index=\\"sDEF\\">%"');
		/** @var $flexformTools \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
		$flexformTools = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
		foreach ($rows as $row) {
			$flexFormXML = $row['pi_flexform'];
			$data = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($flexFormXML);
			$sDEF = $data['data']['sDEF']['lDEF'];
			unset($data['data']['sDEF']);
			$type = $sDEF['mmType']['vDEF'];
			$data['data']['sGeneral'] = array(
				'lDEF' => array(
					'mmType' => array('vDEF' => $type)
				)
			);
			$width = $sDEF['mmWidth']['vDEF'];
			if ($width) {
				$data['data']['sGeneral']['lDEF']['mmWidth'] = array('vDEF' => intval($width));
			}
			$height = $sDEF['mmHeight']['vDEF'];
			if ($height) {
				$data['data']['sGeneral']['lDEF']['mmHeight'] = array('vDEF' => intval($height));
			}
			switch ($type) {
			case 'video':
				$data['data']['sVideo'] = array('lDEF' => array('mmFile' => array('vDEF' => $sDEF['mmFile']['vDEF'])));
				break;
			case 'audio':
				$data['data']['sAudio'] = array('lDEF' => array('mmAudioFallback' => array('vDEF' => $sDEF['mmFile']['vDEF'])));
				break;
			default:
				continue;
			}
			$newXML = $flexformTools->flexArray2Xml($data, TRUE);
			$newXML = str_replace('encoding=""', 'encoding="utf-8"', $newXML);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'], 'uid = ' . $row['uid'], array('pi_flexform' => $newXML));
		}
		return TRUE;
	}

}


?>