.. include:: /Includes.rst.txt

==============================================
Breaking: #80929 - TYPO3_DB moved to extension
==============================================

See :issue:`80929`

Description
===========

The legacy class :php:`DatabaseConnection` better known as :php:`$GLOBALS['TYPO3_DB']` has been moved
to the new system extension ext:typo3db_legacy.

The following classes have been moved and renamed, including an alias to the original class name:

* :php:`TYPO3\CMS\Core\Database\DatabaseConnection` to :php:`TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection`
* :php:`TYPO3\CMS\Core\Database\PreparedStatement` to :php:`TYPO3\CMS\Typo3DbLegacy\Database\PreparedStatement`
* :php:`TYPO3\CMS\Core\Database\PostProcessQueryHookInterface` to :php:`TYPO3\CMS\Typo3DbLegacy\Database\PostProcessQueryHookInterface`
* :php:`TYPO3\CMS\Core\Database\PreProcessQueryHookInterface` to :php:`TYPO3\CMS\Typo3DbLegacy\Database\PreProcessQueryHookInterface`

Impact
======

:php:`$GLOBALS['TYPO3_DB']` is no longer initialized by the core bootstrap. Third party extensions
that rely on :php:`$GLOBALS['TYPO3_DB']` will trigger a fatal error if :code:`ext:typo3db_legacy`
is not loaded.


Affected Installations
======================

Installations with extensions which did not migrate to doctrine yet and need :php:`$GLOBALS['TYPO3_DB']`.


Migration
=========

`Migrate affected extensions to doctrine <https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/Database/Migration/Index.html>`_
or load extension `typo3db_legacy` as backwards compatible layer to :php:`$GLOBALS['TYPO3_DB']`
(the extension is available via TER and may be installed from there or directly
via the upgrade wizard `typo3DbLegacyExtension`).

.. index:: Database, PHP-API, FullyScanned
