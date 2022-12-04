.. include:: /Includes.rst.txt

.. _feature-97536-1651523601:

======================================================
Feature: #97536 - Unified API for generating typolinks
======================================================

See :issue:`97536`

Description
===========

A new :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory` class is added to TYPO3
Core, which allows to generate any kind of links in the TYPO3 frontend - links
to files, pages, URLs, email, telephone links, or links to specific records,
such as news entries.

Previously, this functionality resided in :php:`ContentObjectRenderer->typoLink()`
and :php:`ContentObjectRenderer->typoLink_URL()` but was extracted into a
specific class, only dealing with the generation of links.

This class works with two main methods:

:php:`LinkFactory->create()`
:php:`LinkFactory->createUri()`

Both methods return a :php:`LinkResultInterface` instance, which can be used
programmatically to render the results of the link generation for HTML output
via :php:`LinkResult->getHtml()` or as JSON with :php:`LinkResult->getJson()`.


Impact
======

For TypoScript or Fluid-based renderings, the base functionality for using
:php:`ContentObjectRenderer->typoLink()` is still recommended. However, when
an extension developer wants to work with the raw result, the
:php:`LinkResultInterface` and corresponding implementations for JSON and HTML
rendering allow for much more flexibility by accessing more information than
just the raw anchor tag.

.. index:: Frontend, PHP-API, ext:frontend
