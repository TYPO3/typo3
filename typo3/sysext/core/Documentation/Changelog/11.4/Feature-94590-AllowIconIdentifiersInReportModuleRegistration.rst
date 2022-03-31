.. include:: /Includes.rst.txt

======================================================================
Feature: #94590 - Allow icon identifiers in report module registration
======================================================================

See :issue:`94590`

Description
===========

To further streamline the usage of the Icon Registry, the reports registration
array now allows to define icon identifiers for the :php:`icon` key. Absolute
paths and paths with `EXT:` prefix are still possible.

Example:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'] = [
       'title' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_title',
       'icon' => 'module-reports', // Icon identifiers are now possible here.
       'description' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_description',
       'report' => \TYPO3\CMS\Reports\Report\Status\Status::class
   ];

Impact
======

Developers are now able to provide icon identifiers in the reports module
registration array.

.. index:: Backend, PHP-API, ext:reports
