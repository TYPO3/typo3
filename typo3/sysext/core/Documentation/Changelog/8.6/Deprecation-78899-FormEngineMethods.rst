.. include:: ../../Includes.txt

========================================
Deprecation: #78899 - FormEngine Methods
========================================

See :issue:`78899`

Description
===========

The following methods have been marked as deprecated:

* :code:`TYPO3\CMS\Core\Database\RelationHandler->readyForInterface()`
* :code:`TYPO3\CMS\Backend\Form\FormDataProvider->sanitizeMaxItems()`
* :code:`TYPO3\CMS\Backend\Utility::getSpecConfParts()`
* :code:`TYPO3\CMS\Backend\Controller\Wizard\ColorpickerController` and backend route :code:`wizard_colorpicker`
* :code:`TYPO3\CMS\Backend\Form\Wizard\SuggestWizard` and template :code:`typo3/sysext/backend/Resources/Private/Templates/Wizards/SuggestWizard.html`
* :code:`TYPO3\CMS\Backend\Form\AbstractNode->getValidationDataAsDataAttribute()`
* :code:`TYPO3\CMS\Backend\Form\Element->renderWizards()`

Impact
======

Using above methods will throw a deprecation warning.


Affected Installations
======================

Extensions using above methods.


Migration
=========

* :code:`sanitizeMaxItems()` has been merged into calling methods using a default value and sanitizing with :code:`MathUtility::forceIntegerInRange()`.
* :code:`readyForInterface()` has been substituted with the easier to parse method :code:`getResolvedItemArray()`.
* :code:`getSpecConfParts()` is obsolete with the removal of :code:`defaultExtras` TCA
* :code:`ColorpickerController` is obsolete with the JavaScript based colorpicker in the backend
* :code:`SuggestWizard` has been merged into :code:`GroupElement` directly, the standalone class is obsolete
* :code:`getValidationDataAsDataAttribute()` - use :code:`getValidationDataAsJsonString()` and :code:`htmlspecialchars()` the result or use `GeneralUtility::implodeAttributes()` with second argument set to true.
* :code:`renderWizards()` has been substituted with the new API :code:`NodeExpansion`. Old :code:`popup, userFunc, script` wizards are still called and rendered, but the method usage should be avoided and extensions should switch to the new API.


Extensions using above methods should consider to switch away from those methods.


.. index:: Backend, PHP-API