.. include:: /Includes.rst.txt

===============================================================
Deprecation: #94953 - Edit panel related frontend functionality
===============================================================

See :issue:`94953`

Description
===========

With the extraction of the "feedit" extension from TYPO3 core in v10 a
couple of TypoScript related properties have been rendered unused. Extensions
that provide a frontend editing approach should implement these on their own.

The following TypoScript properties have been marked as deprecated and
will be removed in TYPO3 v12:

* :typoscript:`stdWrap.editPanel`
* :typoscript:`stdWrap.editPanel.`
* :typoscript:`stdWrap.editIcons`
* :typoscript:`stdWrap.editIcons.`
* :typoscript:`EDITPANEL` content object

Related PHP code has been marked as deprecated:

* Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_editIcons()` - scanned
* Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_editPanel()` - scanned
* Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->editPanel()` - scanned
* Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->editIcons()` - scanned
* Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->isDisabled()` - not scanned
* Class :php:`TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject` - scanned
* Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']` - scanned, logged
* Property :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.php->displayEditIcons` - scanned
* Property :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.php->displayFieldEditIcons` - scanned
* Method :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getEditPanel()` - scanned, logged
* Method :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getEditIcon()` - scanned, logged
* Property :php:`TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_EPtemp_cObj` - scanned


Impact
======

Some of the method usages will trigger a PHP :php:`E_USER_DEPRECATED` error upon use. The
core extension EXT:fluid_styled_content still sets stdWrap.editPanel and
stdWrap.editIcons properties for content elements, so the known frontend editing
related extensions EXT:feedit and EXT:frontend_editing will continue to work
in v11. Those properties will be removed with v12.


Affected Installations
======================

Instances that use frontend editing extensions - most notably EXT:feedit or
EXT:frontend_editing - may see deprecated functionality being logged. The
extension scanner will find PHP usages. Using the TypoScript properties is
not logged.


Migration
=========

Frontend editing related extensions like EXT:feedit and EXT:frontend_editing
should no longer rely on core provided preparation. The stdWrap functionality
can be integrated with stdWrap related hooks, the `EDITPANEL` cObj can be registered
as extension provided content object, which obsoleted the use of the
:php:`typo3/classes/class.frontendedit.php` hook.

.. index:: Frontend, PHP-API, TypoScript, PartiallyScanned, EXT:frontend
