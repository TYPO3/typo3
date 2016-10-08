.. include:: ../../Includes.txt

==========================================================================================
Breaking: #75031 - Fluidification of TypoScriptTemplateInformationModuleFunctionController
==========================================================================================

Description
===========

:php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tableRow();` has been removed.


Impact
======

If you call the removed method a fatal error will occur.


Affected Installations
======================

Any installations calling :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tableRow();`


Migration
=========

There is no migration available.

.. index:: PHP-API, Backend
