.. include:: /Includes.rst.txt

.. _breaking-PageTsBackendLayoutDataProvider-1687440947:

=================================================================
Breaking: #101149 - Mark PageTsBackendLayoutDataProvider as final
=================================================================

See :issue:`101149`

Description
===========

The class :php:`\TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider`
is marked as final.


Impact
======

It is no longer possible to extend the class
:php:`\TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider`.

Affected installations
======================

Classes extending :php:`\TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider`.

Migration
=========

Instead of extending the data provider, it is recommended to register a custom
DataProvider for backend layouts, which can already be used since TYPO3 v7.

.. index:: Backend, NotScanned, ext:backend
