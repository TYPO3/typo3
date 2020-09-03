.. include:: ../../Includes.txt

========================================
Breaking: #29342 - Improve ValidatorTask
========================================

See :issue:`29342`

Description
===========

In TYPO3 version 10 `ext:linkvalidator` has been improved a lot. The
:php:`\TYPO3\CMS\Linkvalidator\Task\ValidatorTask` - a scheduler task for reporting
broken links via email has been refactored now.

The old marker templates have been replaced by `FluidEmail`. A Fluid templates is now
used for generating the report email. The marker template has been removed completely
along with corresponding functionality.

The following property of the :php:`ValidatorTask` class has been removed:

* :php:`$emailTemplateFile`

The following hooks have been removed and won't be executed anymore:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers']`

The following properties of the :php:`ValidatorTask` class have changed their type:

* :php:`$page` is now :php`int`
* :php:`$depth` is now :php`int`
* :php:`$emailOnBrokenLinkOnly` is now :php:`bool`
* :php:`$configuration` is now :php:`string`


Impact
======

It is no longer possible to set a custom marker based template file with
`emailTemplateFile`. Instead, the new field `emailTemplateName` can be used to
specify a Fluid template file, see Migration section below.


Affected Installations
======================

All installations which use:

* the task by providing a custom template file.
* one of the hooks mentioned above.


Migration
=========

If you currently don't use a custom template or one of the hooks mentioned above,
you don't need to migrate anything.

Otherwiese you have to provide your custom templates using the new field
`emailTemplateName` in the task configuration and adding your custom template
path to :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']`.

Furthermore use the new PSR-14 event
:php:`\TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent` to adjust the
:php:`\TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult` along with the `FluidEmail`
object.

.. index:: Backend, CLI, NotScanned, ext:linkvalidator
