.. include:: ../../Includes.txt

===================================================================
Deprecation: #90348 - PageLayoutView class (internal) is deprecated
===================================================================

See :issue:`90348`

Description
===========

The :php:`PageLayoutView` class, which is considered internal API, has been deprecated in favor of the new Fluid-based alternative which renders the "page" BE module.


Impact
======

Implementations which depend on :php:`PageLayoutView` should prepare to use the alternative implementation (by overlaying and overriding Fluid templates of EXT:backend).


Affected Installations
======================

* Any site which overrides the ``PageLayoutView`` class. The overridden class will still be instanced when rendering previews in BE page module - but no methods will be called on the instance **unless** they are called by a third party hook subscriber.
* Any site which depends on PSR-14 events associated with ``PageLayoutView`` will only have those events dispatched if the ``fluidBasedPageModule`` feature flag is ``false``.
  * Affects ``\TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent``.
  * Affects ``\TYPO3\CMS\Backend\View\Event\BeforeSectionMarkupGeneratedEvent``.

Migration
=========

Fluid templates can be extended or replaced to render custom header, footer or preview of a given :php:`CType`, see feature description for feature 90348.

.. index:: Backend, Fluid, NotScanned, ext:backend
