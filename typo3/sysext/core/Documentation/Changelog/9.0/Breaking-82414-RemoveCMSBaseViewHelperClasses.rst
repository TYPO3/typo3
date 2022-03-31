.. include:: /Includes.rst.txt

======================================================
Breaking: #82414 - CMS ViewHelper base classes removed
======================================================

See :issue:`82414`

Description
===========

The following ViewHelper base classes have been removed:

- :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper`
- :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper`
- :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper`
- :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition`

Aliases are in place, but the following key differences may break your code:

- Render method arguments are no longer possible at all
- The property :php:`$this->controllerContext` is no longer defined


Impact
======

Render method arguments have been deprecated for a long time and should already have been migrated
in your code. If you still have ViewHelpers using render method arguments, these will break
after this change.


Affected Installations
======================

All instances which use a ViewHelper that either contains render method arguments, extends from one
of the base classes above, or or accesses :php:`$this->controllerContext`.


Migration
=========

Migrate to use `renderStatic` methods (see examples in TYPO3 Core, EXT:fluid) to not use
render method arguments.

ViewHelpers which access :php:`$this->controllerContext` can instead access
:php:`$this->renderingContext->getControllerContext()`.

Instead of using the (now removed) abstract classes from ext:fluid, use the classes
supplied in :file:`vendor/typo3fluid/fluid`:

* :php:`TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper`
* :php:`TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper`
* :php:`TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`
* :php:`TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition`

and change your code accordingly.

Migrating this can be done with search-and-replace for all common use cases.

.. index:: Fluid, NotScanned
