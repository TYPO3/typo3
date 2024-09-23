.. include:: /Includes.rst.txt

.. _feature-102155-1717653944:

======================================================================
Feature: #102155 - User TSconfig option for default resources ViewMode
======================================================================

See :issue:`102155`

Description
===========

The listing of resources in the TYPO3 backend, e.g. in the
:guilabel:`File > Filelist` module or the `FileBrowser` can be switched
between `list` and `tiles`. TYPO3 serves `tiles` by default.

A new User TSconfig option :typoscript:`options.defaultResourcesViewMode` has
been introduced, which allows the initial display mode to be defined. Valid
values are therefore `list` and `tiles`, e.g.:

..  code-block:: typoscript

    options.defaultResourcesViewMode = list

Impact
======

Integrators can now define the default display mode for resources via
User TSconfig.

.. index:: Backend, TSConfig, ext:filelist
