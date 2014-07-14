<?php
defined('TYPO3_MODE') or die();

if (!isset($GLOBALS['TCA']['fe_groups']['ctrl']['type'])) {
	$tca = array(
		'ctrl' => array(
			'type' => 'tx_extbase_type',
		),
		'columns' => array(
			'tx_extbase_type' => array(
				'exclude' => 1,
				'label' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type',
				'config' => array(
					'type' => 'select',
					'items' => array(
						array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type.0', '0'),
						array('LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type.Tx_Extbase_Domain_Model_FrontendUserGroup', 'Tx_Extbase_Domain_Model_FrontendUserGroup')
					),
					'size' => 1,
					'maxitems' => 1,
					'default' => '0'
				)
			)
		),
		'types' => array(
			'Tx_Extbase_Domain_Model_FrontendUserGroup' => $GLOBALS['TCA']['fe_groups']['types']['0'],
		),
	);
	$GLOBALS['TCA']['fe_groups'] = array_replace_recursive($GLOBALS['TCA']['fe_groups'], $tca);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'tx_extbase_type');
} else {
	$GLOBALS['TCA']['fe_groups']['types']['Tx_Extbase_Domain_Model_FrontendUserGroup'] = $GLOBALS['TCA']['fe_groups']['types']['0'];
}
