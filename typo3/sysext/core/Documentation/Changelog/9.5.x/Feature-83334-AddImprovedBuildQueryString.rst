.. include:: /Includes.rst.txt

========================================================
Feature: #83334 - Add improved building of query strings
========================================================

See :issue:`83334`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\HttpUtility::buildQueryString()` has been added as an enhancement to the `PHP function`_ :php:`http_build_query()`.
It implodes multidimensional parameter arrays and properly encodes parameter names as well as values to a valid query string.
with an optional prepend of :php:`?` or :php:`&` If the query is not empty, `?` or `&` are prepended in the correct sequence.
Empty parameters are skipped.

.. _`PHP function`: https://secure.php.net/manual/de/function.http-build-query.php

Impact
======

Parameter arrays can be safely transformed into HTTP GET query strings using the new method.

.. index:: PHP-API
