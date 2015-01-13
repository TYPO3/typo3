<?php
namespace TYPO3\CMS\Install\Updates;

/**
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

/**
 * Migrates the old media FlexForm to the new
 */
class MediaFlexformUpdate extends AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'FlexForm Data from Media Element';

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * Creates this object
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Checks whether updates need to be performed
	 *
	 * @param string &$description The description for the update
	 * @param integer &$showUpdate 0=dont show update; 1=show update and next button; 2=only show description
	 * @return boolean
	 */
	public function checkForUpdate(&$description, &$showUpdate = 0) {
		$mediaElements = $this->db->exec_SELECTcountRows('*', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'], 'CType = ' . $this->db->fullQuoteStr('media', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']) . ' AND pi_flexform LIKE ' . $this->db->fullQuoteStr('%<sheet index="sDEF">%', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']));
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
	 * Perform update
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether the updated was made or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$rows = $this->db->exec_SELECTgetRows(
			'uid,pi_flexform',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'],
			'CType = ' . $this->db->fullQuoteStr('media', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']) . ' AND pi_flexform LIKE ' . $this->db->fullQuoteStr('%<sheet index="sDEF">%', $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'])
		);
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
				$data['data']['sGeneral']['lDEF']['mmWidth'] = array('vDEF' => (int)$width);
			}
			$height = $sDEF['mmHeight']['vDEF'];
			if ($height) {
				$data['data']['sGeneral']['lDEF']['mmHeight'] = array('vDEF' => (int)$height);
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
			$this->db->exec_UPDATEquery(
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'],
				'uid = ' . $row['uid'],
				array('pi_flexform' => $newXML)
			);
		}
		return TRUE;
	}

}
