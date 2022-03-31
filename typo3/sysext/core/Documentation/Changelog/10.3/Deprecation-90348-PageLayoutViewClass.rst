.. include:: /Includes.rst.txt

==========================================
Deprecation: #90348 - PageLayoutView class
==========================================

See :issue:`90348`

Description
===========

The :php:`PageLayoutView` class, which is considered internal API, has been marked as deprecated in favor
of the new Fluid-based alternative which renders the "page" BE module.


Impact
======

Implementations which depend on :php:`PageLayoutView` should prepare to use the alternative implementation (by overlaying and overriding Fluid templates of EXT:backend).


Affected Installations
======================

* Any site which overrides the :php:`PageLayoutView` class. The overridden class will
  still be instantiated when rendering previews in BE page module - but no methods
  will be called on the instance **unless** they are called by a third party hook subscriber.
* Any site which depends on PSR-14 events associated with :php:`PageLayoutView` will only
  have those events dispatched if the :php:`fluidBasedPageModule` feature flag is :php:`false`.
  * Affects :php:`\TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent`.
  * Affects :php:`\TYPO3\CMS\Backend\View\Event\BeforeSectionMarkupGeneratedEvent`.


Migration
=========

Fluid templates can be extended or replaced to render custom header, footer or preview of
a given :typoscript:`CType`, see feature description for feature :issue:`90348`.

.. index:: Backend, Fluid, NotScanned, ext:backend
