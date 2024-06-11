.. include:: /Includes.rst.txt

.. _feature-102155-1717653944:

======================================================================
Feature: #102155 - User TSconfig option for default resources ViewMode
======================================================================

See :issue:`102155`

Description
===========

The listing of resources in the TYPO3 Backend, e.g. in the
:guilabel:`File > Filelist` module or the `FileBrowser` can be changed
between `list` and `tiles`. TYPO3 serves by default `tiles`, if the user
has not already made a choice.

A new User TSconfig option :typoscript:`options.defaultResourcesViewMode` has
been introduced, which allows to define the initial display mode. Valid
values are therefore `list` and `tiles`, e.g.:

..  code-block:: typoscript

    options.defaultResourcesViewMode = list

Impact
======

Integrators can now define the default display mode for resources via
User TSconfig.

.. index:: Backend, TSConfig, ext:filelist
