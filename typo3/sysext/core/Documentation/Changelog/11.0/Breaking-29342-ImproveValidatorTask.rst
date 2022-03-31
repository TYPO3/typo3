.. include:: /Includes.rst.txt

=========================================================
Breaking: #29342 - Fluid Email Template for ValidatorTask
=========================================================

See :issue:`29342`

Description
===========

In TYPO3 v10 `ext:linkvalidator` has been improved a lot. The
:php:`\TYPO3\CMS\Linkvalidator\Task\ValidatorTask`, a scheduler task for reporting
broken links via email, has been refactored now.

The old marker template has been replaced by Fluid templates, which are now
used for generating the report email. The marker template has been removed completely
along with corresponding functionality.

The following property of the :php:`ValidatorTask` class has been removed:

* :php:`$emailTemplateFile`

The following hooks have been removed and won't be executed anymore:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers']`

The following properties of the :php:`ValidatorTask` class have changed their type:

* :php:`$page` is now :php:`int`
* :php:`$depth` is now :php:`int`
* :php:`$emailOnBrokenLinkOnly` is now :php:`bool`
* :php:`$configuration` is now :php:`string`


Impact
======

It is no longer possible to set a custom marker based template file with
:php:`emailTemplateFile`. Instead, the new field :php:`emailTemplateName` can be used to
specify a Fluid template file, see Migration section below.


Affected Installations
======================

All installations which use:

* the scheduler task and provide a custom template file
* one of the hooks mentioned above


Migration
=========

Provide your custom templates using the new field :php:`emailTemplateName`
in the scheduler task configuration and add your custom template
path to :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']`.

Use the new PSR-14 event :php:`\TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent` to adjust the
:php:`\TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult` along with the `FluidEmail` object.

.. index:: Backend, CLI, NotScanned, ext:linkvalidator
