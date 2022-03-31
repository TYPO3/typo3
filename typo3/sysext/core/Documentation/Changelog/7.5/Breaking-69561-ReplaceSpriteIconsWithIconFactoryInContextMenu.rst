
.. include:: /Includes.rst.txt

=======================================================================
Breaking: #69561 - Replace sprite icons with IconFactory in ContextMenu
=======================================================================

See :issue:`69561`

Description
===========

SpriteIcon and standalone image support have been replaced with `IconFactory` in
the context menu. All menu icons now need to be registered through the `IconRegistry`.


Impact
======

The `UserTsConfig` options for items `icon` and `spriteIcon` have no effect anymore,
and will deliver a blank placeholder image if `iconName` is not set.


Affected Installations
======================

All installations that add or modify items in the ContextMenu.


Migration
=========

Register the icon through the `IconRegistry` and set the `iconName` in the
item configuration.

.. code-block:: php

	// Register Icon
	$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
	$iconRegistry->registerIcon(
		'contextmenu-example',
		\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
		array(
			'source' => 'EXT:example/Resources/Public/Icons/contextmenu-example.svg'
		))
	);


.. code-block:: typoscript

	options.contextMenu.table {
		virtual_root.items {
			9999 = ITEM
			9999 {
				name = contextmenuExample
				label = LLL:EXT:example/Resources/Private/Language/locallang.xlf:contextmenu-example
				iconName = contextmenu-example
				callbackAction = exampleCallback
			}
		}
	}


.. index:: PHP-API, TSConfig, Backend
