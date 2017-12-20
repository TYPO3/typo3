
.. include:: ../../Includes.txt

=========================================================================
Feature: #61725 - Hook for BackendUtility::countVersionsOfRecordsOnPage()
=========================================================================

See :issue:`61725`

Description
===========

Hook to post-process `BackendUtility::countVersionsOfRecordsOnPage`
result. `BackendUtility::countVersionsOfRecordsOnPage` is used to
visualize workspace states in e.g. the page tree.

Register like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['countVersionsOfRecordsOnPage'][] = 'My\Package\HookClass->hookMethod';


.. index:: PHP-API, Backend
