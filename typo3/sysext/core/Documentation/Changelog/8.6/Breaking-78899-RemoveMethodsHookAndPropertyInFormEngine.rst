.. include:: ../../Includes.txt

==================================================================
Breaking: #78899 - Remove methods, hook and property in FormEngine
==================================================================

See :issue:`78899`

Description
===========

The following methods have been removed:

* :php:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->dbFileIcons()`
* :php:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->getClipboardElements()`
* :php:`TYPO3\CMS\Backend\Form\Container\SingleFieldContainer->getMergeBehaviourIcon()`
* :php:`TYPO3\CMS\Backend\Form\Container\SingleFieldContainer->renderDefaultLanguageDiff()`
* :php:`TYPO3\CMS\Backend\Form\Container\SingleFieldContainer->renderDefaultLanguageContent()`
* :php:`TYPO3\CMS\Backend\Form\Container\AbstractContainer->previewFieldValue()`

The following property has been removed:

* :php:`TYPO3\CMS\Backend\Form\Element\AbstractFormElement->clipboard`

The following hook interface has been removed and registered hooks in :php:`dbFileIcons` are no longer called:

* :php:`TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface`

TCA wizards registered as :php:`userFunc` no longer receive the element HTML by reference, so they can no longer change
given HTML string of a given element.


Impact
======

Using above methods, properties and hooks will result in fatal PHP errors or fail silently.


Affected Installations
======================

Check extensions for usages of above methods and especially implementations of the hook interface.


Migration
=========

The methods have been partially moved to the :php:`TcaGroup` data provider and merged to the two
FormEngine elements :php:`GroupElement` and :php:`SelectMultipleSideBySideElement`. Those can be
changed and extended via FormEngine's internal :php:`NodeFactory` and data provider resolvers.

.. index:: Backend, PHP-API, TCA
