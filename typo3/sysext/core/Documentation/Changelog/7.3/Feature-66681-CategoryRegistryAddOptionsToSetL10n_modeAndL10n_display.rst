
.. include:: ../../Includes.txt

=================================================================================
Feature: #66681 - CategoryRegistry: add options to set l10n_mode and l10n_display
=================================================================================

See :issue:`66681`

Description
===========

Class `CategoryRegistry->addTcaColumn` got options to set  `l10n_mode` and `l10n_display`.
The values can be set via:

.. code-block:: php

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
		$extensionKey,
		$tableName,
		'categories',
		array(
			'l10n_mode' => 'string (keyword)',
			'l10n_display' => 'list of keywords'
		)
	);
