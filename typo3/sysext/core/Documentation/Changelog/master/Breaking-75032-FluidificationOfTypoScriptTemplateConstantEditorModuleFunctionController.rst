.. include:: ../../Includes.txt

=============================================================================================
Breaking: #75032 - Fluidification of TypoScriptTemplateConstantEditorModuleFunctionController
=============================================================================================

Description
===========

:php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->displayExample();` has been removed.


Impact
======

Using this function will throw a fatal error.


Affected Installations
======================

All installations using :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->displayExample();`


Migration
=========

There is no migration available, pleas write your own function.