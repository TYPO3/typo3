
.. include:: ../../Includes.txt

=========================================================
Breaking: #69863 - Removed deprecated code from EXT:fluid
=========================================================

See :issue:`69863`

Description
===========

Removed deprecated code from EXT:fluid

The ChangeLog file has been removed.

The renderMode option in `FlashMessagesViewHelper` has been removed.

The following methods have been removed:

`StandaloneView::setLayoutRootPath`
`StandaloneView::getLayoutRootPath`
`StandaloneView::setPartialRootPath`
`StandaloneView::getPartialRootPath`
`AbstractFormFieldViewHelper::getValue`

The following class has been removed:

`IconViewHelper`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.

Relying on the renderMode option might lead to different frontend output.


Affected Installations
======================

Instances which use calls to the methods above, use the removed `IconViewHelper or use the renderMode option in `FlashMessagesViewHelper``.


Migration
=========

For `StandaloneView::setLayoutRootPath` use `StandaloneView::setLayoutRootPaths` instead.
For `StandaloneView::getLayoutRootPath` use `StandaloneView::getLayoutRootPaths` instead.
For `StandaloneView::setPartialRootPath` use `StandaloneView::setPartialRootPaths` instead.
For `StandaloneView::getPartialRootPath` use `StandaloneView::setPartialRootPaths` instead.

Keep in mind that these methods expect an **array** instead of a string.

For `IconViewHelper` use `\TYPO3\CMS\Core\ViewHelpers\IconViewHelper` instead.

.. index:: PHP-API, Fluid
