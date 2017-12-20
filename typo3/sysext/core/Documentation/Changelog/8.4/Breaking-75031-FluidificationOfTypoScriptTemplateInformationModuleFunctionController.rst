.. include:: ../../Includes.txt

==========================================================================================
Breaking: #75031 - Fluidification of TypoScriptTemplateInformationModuleFunctionController
==========================================================================================

See :issue:`75301`

Description
===========

:php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tableRow();` has been removed.


Impact
======

Calling this method will result in a fatal error.


Affected Installations
======================

Any installations calling :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tableRow();`


Migration
=========

There is no migration available.

.. index:: PHP-API, Backend, ext:tstemplate
