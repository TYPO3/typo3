.. include:: /Includes.rst.txt

.. _deprecation-101559-1721761906:

==========================================================
Deprecation: #101559 - Extbase uses ext:core ViewInterface
==========================================================

See :issue:`101559`

Description
===========

The default view of ext:extbase now returns a view that implements
:php:`\TYPO3\CMS\Core\View\ViewInterface` and not only
:php:`\TYPO3Fluid\Fluid\View\ViewInterface` anymore. This allows
implementing any view that implements :php-short:`\TYPO3\CMS\Core\View\ViewInterface`,
and frees the direct dependency to Fluid.

The default return object is an instance of
:php:`\TYPO3\CMS\Core\View\FluidViewAdapter` which implements all
special methods tailored for Fluid. Extbase controllers should
check for instance of this object before calling these methods,
especially:

* :php:`getRenderingContext()`
* :php:`setRenderingContext()`
* :php:`renderSection()`
* :php:`renderPartial()`

Method calls not being part of :php-short:`\TYPO3\CMS\Core\View\ViewInterface` or the above
listed method names have been marked as deprecated and will be removed in TYPO3 v14.

Impact
======

Extbase controllers that extend :php-short:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController`
and call methods not part of :php-short:`\TYPO3\CMS\Core\View\ViewInterface`, should
test for :php:`$view instanceof FluidViewAdapter` before calling
:php:`getRenderingContext()`, :php:`setRenderingContext()`, php:`renderSection()`
and :php:`renderPartial()`.

All other Fluid related methods called on :php:`$view` have been marked as
deprecated and will log a deprecation level error message.


Affected installations
======================

Instances with Extbase based extensions that call :php:`$view` methods without
testing for :php-short:`\TYPO3\CMS\Core\View\FluidViewAdapter`.


Migration
=========

Methods on "old" Fluid instances were wrapper methods for
:php-short:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext`. Controllers
should call :php:`$view->getRenderingContext()`
to perform operations instead.


.. index:: Fluid, PHP-API, NotScanned, ext:extbase
