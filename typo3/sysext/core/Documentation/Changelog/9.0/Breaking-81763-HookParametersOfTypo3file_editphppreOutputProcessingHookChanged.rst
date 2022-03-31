.. include:: /Includes.rst.txt

================================================================================================
Breaking: #81763 - Hook parameters of ['typo3/file_edit.php']['preOutputProcessingHook'] changed
================================================================================================

See :issue:`81763`

Description
===========

The hook parameters passed into :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook']`
have been changed due to rewriting the edit file form to use FormEngine.


Impact
======

Any information added to modify the output may have no effect anymore.


Affected Installations
======================

Every installation using an extension that hooks into :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook']`
to modify the form's output is affected.


Migration
=========

As the form is based on FormEngine now, you may want to adjust the newly introduced hook parameter :php:`$dataColumnDefinition`,
representing the definition of the `data` field which contains the file content. An example can be found in EXT:t3editor.

.. index:: Backend, PHP-API, NotScanned
