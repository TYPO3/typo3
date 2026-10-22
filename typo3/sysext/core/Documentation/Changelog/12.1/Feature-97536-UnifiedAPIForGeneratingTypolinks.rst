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

Previously, this functionality resided in :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink()`
and :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink_URL()` but was extracted into a
specific class, only dealing with the generation of links.

This class works with two main methods:

:php-short:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->create()`
:php-short:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->createUri()`

Both methods return a :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface` instance, which can be used
programmatically to render the results of the link generation for HTML output
via :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResult->getHtml()` or as JSON with :php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResult->getJson()`.


Impact
======

For TypoScript or Fluid-based renderings, the base functionality for using
:php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typoLink()` is still recommended. However, when
an extension developer wants to work with the raw result, the
:php-short:`\TYPO3\CMS\Frontend\Typolink\LinkResultInterface` and corresponding implementations for JSON and HTML
rendering allow for much more flexibility by accessing more information than
just the raw anchor tag.

.. index:: Frontend, PHP-API, ext:frontend
