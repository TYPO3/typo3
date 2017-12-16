
.. include:: ../../Includes.txt

===================================================
Feature: #66822 - Allow Sprites For Backend Modules
===================================================

See :issue:`66822`

Description
===========

Backend Modules (both Main Modules like "Web" and Submodules like "Filelist") can now use sprites instead of images for
displaying the icons in the module menu on the left side of the TYPO3 Backend.

Registering a module can now look like this (as an example the "Page" module):

.. code-block:: php

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'layout',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Layout/',
		array(
			'script' => '_DISPATCH',
			'access' => 'user,group',
			'name' => 'web_layout',
			'iconIdentifier' => 'module-web',
			'labels' => array(
				'll_ref' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
			),
		)
	);

One can use any available sprite icon known to TYPO3.
