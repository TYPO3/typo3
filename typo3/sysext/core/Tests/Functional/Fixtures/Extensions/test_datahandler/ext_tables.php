<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_testdatahandler_element');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'tt_content',
	 array(
		 'tx_testdatahandler_select' => array(
			'exclude' => 1,
			'label' => 'DataHandler Test Select',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_testdatahandler_element',
				'minitems' => 1,
				'maxitems' => 10,
				'autoSizeMax' => '10',
			),
		),
		 'tx_testdatahandler_group' => array(
			'exclude' => 1,
			'label' => 'DataHandler Test Group',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_testdatahandler_element',
				'minitems' => 1,
				'maxitems' => 10,
				'autoSizeMax' => '10',
			),
		),
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'tt_content',
	'--div--;DataHandler Test, tx_testdatahandler_select, tx_testdatahandler_group'
);
