.. include:: /Includes.rst.txt

=============================================================================================
Breaking: #75032 - Fluidification of TypoScriptTemplateConstantEditorModuleFunctionController
=============================================================================================

See :issue:`75032`

Description
===========

:php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->displayExample();` has been removed.


Impact
======

Calling this method will result in a fatal error.


Affected Installations
======================

All installations using :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->displayExample();`


Migration
=========

There is no migration available, pleas write your own function.

.. index:: PHP-API, Backend, ext:tstemplate
