
.. include:: ../../Includes.txt

=============================================
Feature: #65767 - System Information Dropdown
=============================================

See :issue:`65767`

Description
===========

A new, extensible flyout menu item is introduced that contains information about
the system TYPO3 is installed on.


Impact
======

In a default installation. the new flyout item will be placed between the "help" and the "user"
flyout items and is accessible by administrators only.

Items
^^^^^

It is possible to add own system information items by creating a slot. The slot must be registered in
an extension's ext_localconf.php

.. code-block:: php

	$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
	$signalSlotDispatcher->connect(
		\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class,
		'getSystemInformation',
		\Vendor\Extension\SystemInformation\Item::class,
		'getItem'
	);

This requires the class `Item` and its method `getItem()` in EXT:extension\Classes\SystemInformation\Item.php:

.. code-block:: php

	class Item {
		public function getItem() {
			return array(array(
				'title' => 'The title shown on hover',
				'value' => 'Description shown in the list',
				'status' => SystemInformationHookInterface::STATUS_OK,
				'count' => 4,
				'icon' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-example-information-icon')
			));
		}
	}

Due to the SignalSlot internals, the data array must be encapsulated with another array! If there is no data to return, return `NULL`.

The icon `extensions-example-information-icon` must be registered in ext_localconf.php:

.. code-block:: php

	\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
		array(
			'information-icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/Icons/information-icon.png'
		),
		$_EXTKEY
	);

"extensions-" is a hardcoded prefix, combined with `$_EXTKEY` (e.g. "example") creates the prefix "extensions-example-" to
be used with every icon being registered. Since the first parameter of `SpriteManager::addSingleIcons()` is an array, multiple icons
can be registered at once.


Messages
^^^^^^^^

Messages are shown at the bottom og the dropdown. An extension can provide its own slot to fill the messages:

.. code-block:: php

	$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
	$signalSlotDispatcher->connect(
		\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class,
		'loadMessages',
		\Vendor\Extension\SystemInformation\Message::class,
		'getMessage'
	);

This requires the class `Message` and its method `getMessage()` in EXT:extension\Classes\SystemInformation\Message.php:

.. code-block:: php

	class Message {
		public function getMessage() {
			return array(array(
				'status' => SystemInformationHookInterface::STATUS_OK,
				'text' => 'Something went somewhere terribly wrong. Take a look at the reports module.'
			));
		}
	}

Due to the SignalSlot internals, the data array must be encapsulated with another array! If there is no data to return, return `NULL`.


.. index:: PHP-API, Backend
