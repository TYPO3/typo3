.. include:: ../../Includes.txt

=======================================================
Feature: #80263 - Add a new signal slot for user switch
=======================================================

See :issue:`80263`

Description
===========

A new signal is emitted once an admin user switches into another user via the Switch-To functionality within TYPO3 core.

Use the following code to use the signal

.. code-block:: php

   $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
   $dispatcher->connect(
       \TYPO3\CMS\Beuser\Controller\BackendUserController::class,
       'switchUser',
       \MyVendor\MyExtension\Slots\BackendUserController::class,
       'switchUser'
   );

.. index:: Backend, PHP-API, ext:beuser