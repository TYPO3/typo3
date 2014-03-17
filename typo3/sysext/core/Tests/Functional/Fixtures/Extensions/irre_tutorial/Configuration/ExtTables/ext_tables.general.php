<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'pages',
	 array(
		'tx_irretutorial_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.tx_irretutorial_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
				),
			)
		),
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.doktype.div.irre, tx_irretutorial_hotels;;;;1-1-1'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'pages_language_overlay',
	 array(
		'tx_irretutorial_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.tx_irretutorial_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
				),
			)
		),
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages_language_overlay',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.doktype.div.irre, tx_irretutorial_hotels;;;;1-1-1'
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'tt_content',
	 array(
		 'tx_irretutorial_1nff_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_1nff_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
			)
		),
		 'tx_irretutorial_1ncsv_hotels' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_1ncsv_hotels',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1ncsv_hotel',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
			)
		),
		'tx_irretutorial_flexform' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_flexform',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => 'FILE:EXT:irre_tutorial/Configuration/FlexForms/tt_content_flexform.xml',
				),
			)
		),
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'tt_content',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.div.irre, tx_irretutorial_1nff_hotels;;;;1-1-1, tx_irretutorial_1ncsv_hotels, tx_irretutorial_flexform'
);

$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] = 'tx_irretutorial_1ncsv_hotels';
$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForMovePlaceholders'] = 'tx_irretutorial_1ncsv_hotels';

?>