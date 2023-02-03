.. include:: /Includes.rst.txt

.. _deprecation-99717-1674654675:

=========================================================================
Deprecation: #99717 - Deprecated "modifyBlindedConfigurationOptions" hook
=========================================================================

See :issue:`99717`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Lowlevel\Controller\ConfigurationController']['modifyBlindedConfigurationOptions']`
has been deprecated in favor of the new PSR-14
:php:`\TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent`,
which acts as a direct replacement.


Impact
======

Using the hook will trigger a deprecation log entry and any hook
implementation registered will not be executed anymore in TYPO3 v13.


Affected installations
======================

All installations using the deprecated hook. The extension scanner
will report possible usages.


Migration
=========

Use the :ref:`PSR-14 event <feature-99717-1674654720>` as a direct replacement.

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:lowlevel
