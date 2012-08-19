<?php

if (TYPO3_MODE == 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	/**
	 * Registers a Backend Module
	 */
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'tools', // Make module a submodule of 'tools'
		'language', // Submodule key
		'after:em', // Position
		array(
			// An array holding the controller-action-combinations that are accessible
			'Language' => 'index, saveSelectedLocale, updateTranslation'
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/moduleicon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod_language.xml',
		)
	);
}

?>