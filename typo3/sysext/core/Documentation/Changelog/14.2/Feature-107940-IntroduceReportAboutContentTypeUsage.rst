..  include:: /Includes.rst.txt

..  _feature-107940-1761853022:

============================================================
Feature: #107940 - Introduce report about content type usage
============================================================

See :issue:`107940`

Description
===========

A new :guilabel:`Content statistics` module has been introduced in the TYPO3
backend under `guilabel:`Reports`. This module provides data about the usage of
content elements in the TYPO3 site.

Overview
========

The overview displays all available content element types along with the number
of times each type is used.
For each element, all associated fields are listed, including key details such as:

*   The field type
*   Whether it is marked as required
*   Whether it is configurable as excludable via user group permissions

Detail view
===========

The detail view for each content element type lists all corresponding records
that are not marked as deleted.

Impact
======

The new report offers a convenient way to analyze and optimize content structures
within a TYPO3 installation.
It helps administrators and developers to:

*   Identify unused content element types
*   Understand which fields are utilized by specific content element types
*   Gain insights into the overall configuration and diversity of content elements

..  index:: Backend, ext:reports
