
.. include:: ../../Includes.txt

=================================================================
Feature: #72904 - Add preProcessStorage signal to ResourceFactory
=================================================================

See :issue:`72904`

Description
===========

This patch introduces a new signal before a resource storage is initialized.

Register the class which implements your logic in `ext_localconf.php`:

.. code-block:: php

   $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
   $dispatcher->connect(
       \TYPO3\CMS\Core\Resource\ResourceFactory::class,
       ResourceFactoryInterface::SIGNAL_PreProcessStorage,
       \MY\ExtKey\Slots\ResourceFactorySlot::class,
       'preProcessStorage'
   );

The method is called with the following arguments:

* int `$uid` the uid of the record
* array `$recordData` all record data as array
* string `$fileIdentifier` the file identifier

.. index:: PHP-API, FAL
