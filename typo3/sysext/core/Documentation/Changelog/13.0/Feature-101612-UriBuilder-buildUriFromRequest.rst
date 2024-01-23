.. include:: /Includes.rst.txt

.. _feature-101612-1691425003:

==================================================
Feature: #101612 - UriBuilder->buildUriFromRequest
==================================================

See :issue:`101612`

Description
===========

A new method within :php:`\TYPO3\CMS\Backend\Routing\UriBuilder` named
`buildUriFromRequest` is added which allows to generate a URL to a backend route
of this request.


Impact
======

This is typically useful when linking to the current route or module in the TYPO3
backend for extension authors to avoid internals with any PSR-7 request attribute.

Usage within a PHP module controller in the TYPO3 backend context:

:php:`$this->uriBuilder->buildUriFromRequest($request, ['id' => $id]);`

.. index:: Backend, PHP-API, ext:backend
