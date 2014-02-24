<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_irretutorial_mnasym_hotel');

$TCA['tx_irretutorial_mnasym_hotel'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_mnasym_hotel',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/Tca/tca.mnasym.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_tx_irretutorial_hotel.gif',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		'dividers2tabs' => TRUE,
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, title, offers',
	)
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_irretutorial_mnasym_hotel_offer_rel');

$TCA['tx_irretutorial_mnasym_hotel_offer_rel'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_mnasym_hotel_offer_rel',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/Tca/tca.mnasym.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_tx_irretutorial_hotel_offer_rel.gif',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		// @see http://forge.typo3.org/issues/29278 which solves it implicitly in the Core
		// 'shadowColumnsForNewPlaceholders' => 'hotelid,offerid',
		'dividers2tabs' => TRUE,
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelid, offerid, prices, hotelsort, offersort',
	)
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_irretutorial_mnasym_offer');

$TCA['tx_irretutorial_mnasym_offer'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_mnasym_offer',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/Tca/tca.mnasym.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_tx_irretutorial_offer.gif',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		'dividers2tabs' => TRUE,
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, title, hotels',
	)
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_irretutorial_mnasym_price');

$TCA['tx_irretutorial_mnasym_price'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tx_irretutorial_mnasym_price',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',
		'transOrigPointerField'    => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Configuration/Tca/tca.mnasym.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'Resources/Public/Icons/icon_tx_irretutorial_price.gif',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		// @see http://forge.typo3.org/issues/29278 which solves it implicitly in the Core
		// 'shadowColumnsForNewPlaceholders' => 'parentid',
		'dividers2tabs' => TRUE,
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, title, parentid, price',
	)
);
?>