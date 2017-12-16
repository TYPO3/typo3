
.. include:: ../../Includes.txt

=================================================================
Breaking: #62670 - Removal of deprecated code in multiple sysexts
=================================================================

See :issue:`62670`

Description
===========

DBAL DatabaseConnection
-----------------------

All connection parameters (host, username, password, db) for methods :code:`sql_connect()` and :code:`sql_select_db()` are removed.


CSS Styled Content
------------------

The old frontend plugin file `pi1/class.tx_cssstyledcontent_pi1.php` is removed.
Refer to `Classes/Controller/CssStyledContentController.php` instead.


Install Tool
------------

The check for PHP's magic_quotes_gpc settings is removed, as the feature is disabled since PHP 5.4.


Removed files
-------------

* lang/lang.php is removed. You don't need to include this file anymore, just use :code:`\TYPO3\CMS\Lang\LanguageService` directly.
* :file:`rtehtmlarea/htmlarea/plugins/DynamicCSS/dynamiccss.css` is removed. The file was not used.


Removed PHP classes
-------------------

* TYPO3\CMS\Scheduler\Task\FileIndexingTask is removed without replacement.


Removed PHP class members
-------------------------

* TypoScriptFrontendController::$absRefPrefix_force is removed without replacement.


Removed PHP methods
-------------------

* LanguageService::JScharCode is removed, use GeneralUtility::quoteJSvalue instead.
* ContentObjectRenderer::joinTSarrays is removed without replacement.
* TypoScriptFrontendController::tidyHTML is removed without replacement. You may use the tidy extension from TER.
* ElementBrowser::isWebFolder is removed without replacement.
* ElementBrowser::checkFolder is removed without replacement.
* AbstractDatabaseRecordList::getTreeObject is removed without replacement.
* FileList::dirData is removed without replacement.
* FilesContentObject::stdWrapValue is removed, use ContentObjectRenderer::stdWrapValue instead.
* ImportExportController::userTempFolder is removed, use getDefaultImportExportFolder instead.
* ImportExportController::userSaveFolder is removed, use getDefaultImportExportFolder instead.
* CrawlerHook::loadIndexerClass is removed without replacement.
* DatabaseIntegrityView::func_filesearch is removed without replacement.
* DatabaseIntegrityView::findFile is removed without replacement.
* RteHtmlAreaBase::buildStyleSheet is removed without replacement.
* RteHtmlAreaBase::loremIpsumInsert is removed without replacement.
* StagesService::checkCustomStagingForWS is removed without replacement.


Removed JS functions
--------------------

* tx_rsaauth_encrypt is removed without replacement.
* tx_rsaauth_feencrypt is removed without replacement.


Impact
======

A call to any of the aforementioned methods by third party code will result in a fatal PHP error.


Affected installations
======================

Any installation which contains third party code still using these deprecated methods.


Migration
=========

Replace the calls with the suggestions outlined above.
