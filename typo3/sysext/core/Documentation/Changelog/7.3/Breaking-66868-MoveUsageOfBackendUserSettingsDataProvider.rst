
.. include:: /Includes.rst.txt

================================================================
Breaking: #66868 - Move usage of BackendUserSettingsDataProvider
================================================================

See :issue:`66868`

Description
===========

The ExtDirect API `BackendUserSettingsDataProvider` has been removed.


Impact
======

Third party code using either `BackendUserSettingsDataProvider` or `top.TYPO3.BackendUserSettings.ExtDirect` will fatal.


Affected Installations
======================

Any installation using `BackendUserSettingsDataProvider` or `top.TYPO3.BackendUserSettings.ExtDirect` is affected.


Migration
=========

In JavaScript, use `TYPO3.Storage.Persistent` API. In PHP, use `\TYPO3\CMS\Backend\Controller\UserSettingsController`:

.. code-block:: php

	/** @var $userSettingsController \TYPO3\CMS\Backend\Controller\UserSettingsController */
	$userSettingsController = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\UserSettingsController::class);
	$state = $userSettingsController->process('get', 'BackendComponents.States.' . $stateId);


.. index:: PHP-API, Backend, JavaScript
