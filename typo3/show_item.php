<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
$GLOBALS['BACK_PATH'] = '';
require_once 'init.php';
/**
 * Extension of transfer data class
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class transferData extends \TYPO3\CMS\Backend\Form\DataPreprocessor {

	/**
	 * @var string
	 * @todo Define visibility
	 */
	public $formname = 'loadform';

	/**
	 * @var boolean
	 * @todo Define visibility
	 */
	public $loading = 1;

	/**
	 * Extra for show_item.php:
	 *
	 * @var array
	 * @todo Define visibility
	 */
	public $theRecord = array();

	/**
	 * Register item function.
	 *
	 * @param string $table Table name
	 * @param integer $id Record uid
	 * @param string $field Field name
	 * @param string $content Content string.
	 * @return void
	 * @todo Define visibility
	 */
	public function regItem($table, $id, $field, $content) {
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		switch ($config['type']) {
		case 'input':
			if (isset($config['checkbox']) && $content == $config['checkbox']) {
				$content = '';
				break;
			}
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($config['eval'], 'date')) {
				$content = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $content);
			}
			break;
		case 'group':

		case 'select':
			break;
		}
		$this->theRecord[$field] = $content;
	}

}

/*
 * @deprecated since 6.0, the classname SC_show_item and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/ContentElement/ElementInformationController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/ContentElement/ElementInformationController.php';
/**
 * @var $SOBE \TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController
 */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementInformationController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>