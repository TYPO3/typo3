.. include:: /Includes.rst.txt

.. _deprecation-104773-1724942036:

=====================================================
Deprecation: #104773 - Custom Fluid views and Extbase
=====================================================

See :issue:`104773`

Description
===========

These classes have been marked as deprecated in TYPO3 v13 and will be removed in v14:

* :php:`\TYPO3\CMS\Fluid\View\StandaloneView`
* :php:`\TYPO3\CMS\Fluid\View\TemplateView`
* :php:`\TYPO3\CMS\Fluid\View\AbstractTemplateView`
* :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewResolverInterface`
* :php:`\TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver`

This change is related to the general :ref:`View refactoring <feature-104773-1724939348>`.


Impact
======

Using one of the above classes triggers a deprecation level log entry.


Affected installations
======================

Instances with extensions that create view instances of
:php-short:`\TYPO3\CMS\Fluid\View\StandaloneView` or
:php-short:`\TYPO3\CMS\Fluid\View\TemplateView` are affected. The extension
scanner will find possible candidates.


Migration
=========

Extensions should no longer directly instantiate own views, but should get
:php:`\TYPO3\CMS\Core\View\ViewFactoryInterface` injected and use :php:`create()`
to retrieve a view.

Within Extbase, :php:`ActionController->defaultViewObjectName` should only be
set to Extbase :php:`JsonView` if needed, or not set at all. Custom view implementations
should implement an own :php-short:`\TYPO3\CMS\Core\View\ViewFactoryInterface` and configure
controllers to inject an instance, or can set :php:`$this->defaultViewObjectName = JsonView::class`
in a custom :php:`__construct()`.

.. index:: PHP-API, PartiallyScanned, ext:core
