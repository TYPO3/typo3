.. include:: /Includes.rst.txt

=============================================================
Breaking: #82398 - Remove special constant "TSConstantEditor"
=============================================================

See :issue:`82398`

Description
===========

The special functionality on the top level constant name :typoscript:`TSConstantEditor`
has been dropped. This rarely used feature makes this constant name a casual
constant without further added features.

A series of PHP class methods and properties has been dropped together with that removal:

* Method :php:`TYPO3\CMS\Core\TypoScript\ConfigurationForm->ext_makeHelpInformationForCategory()`
* Method :php:`TYPO3\CMS\Core\TypoScript\ConfigurationForm->ext_displayExample()`
* Method :php:`TYPO3\CMS\Core\TypoScript\ExtendedTemplateService->ext_getTSCE_config()`
* Property :php:`TYPO3\CMS\Core\TypoScript\ExtendedTemplateService->helpConfig`
* Method :php:`TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory->setHighlightText()`
* Method :php:`TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory->getHighlightText()`
* Method :php:`TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem->setHighlight()`
* Method :php:`TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem->getHighlight()`

Impact
======

The constants editor does not show any extending information (like bulletpoints) for a constant
anymore configured via the :typoscript:`TSConstantEditor` object.


Affected Installations
======================

All installations which have configured the special constant :typoscript:`TSConstantEditor`. Since this has
been a widely unknown feature, most instances should not be affected. On PHP side, the extension
scanner will find consuming extensions of the dropped API, but that is highly unlikely, too.

.. index:: Backend, TypoScript, FullyScanned
