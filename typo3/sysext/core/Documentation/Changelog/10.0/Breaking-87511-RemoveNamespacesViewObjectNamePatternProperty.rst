.. include:: /Includes.rst.txt

===================================================================
Breaking: #87511 - Remove $namespacesViewObjectNamePattern property
===================================================================

See :issue:`87511`

Description
===========

Property :php:`$namespacesViewObjectNamePattern` of class
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController` has been
removed without replacement.

Impact
======

Overriding the property :php:`$namespacesViewObjectNamePattern` in
controllers that extend :php:`ActionController` will no longer trigger
the instantiation of another view object, derived from the pattern.

Affected Installations
======================

All extensions that override the property :php:`$namespacesViewObjectNamePattern`.

Migration
=========

If an action needs a template object other than the default
:php:`\TYPO3\CMS\Fluid\View\TemplateView`, the property :php:`$defaultViewObjectName`
needs to be overridden.

.. index:: PHP-API, FullyScanned, ext:extbase
