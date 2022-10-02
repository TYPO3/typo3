.. include:: /Includes.rst.txt

.. _feature-96904:

=========================================================
Feature: #96904 - Backend toolbar items are request aware
=========================================================

See :issue:`96904`

Description
===========

When registering own toolbar items to the TYPO3 backend top bar, classes can
now retrieve the current PSR-7 request by implementing
:php:`TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface`. This
is especially useful when rendering views using
:php:`TYPO3\CMS\Backend\View\BackendViewFactory` which depends on
current request.

Impact
======

The TYPO3 Core encourages to use :php:`BackendViewFactory` instead of
:php:`StandaloneView` when toolbar items of extensions use Fluid templates.
:php:`BackendViewFactory` has a dependency to current request, so
:php:`RequestAwareToolbarItemInterface` should be implemented
to receive the current request from TYPO3 EXT:backend.

Doing so enables the :doc:`template overrides by TSconfig
feature <../12.0/Feature-96812-OverrideBackendTemplatesWithTSconfig>`.

.. index:: Backend, PHP-API, ext:backend
