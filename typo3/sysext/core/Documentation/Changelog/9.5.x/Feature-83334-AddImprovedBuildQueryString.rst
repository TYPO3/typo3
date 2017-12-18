.. include:: ../../Includes.txt

========================================================
Feature: #83334 - Add improved building of query strings
========================================================

See :issue:`83334`

Description
===========

The new method :php:`\TYPO3\CMS\Core\Utility\HttpUtility::buildQueryString()` has been added as an enhancement to the `PHP function`_ :php:`http_build_query()`
to implode multidimensional parameter arrays, properly encode parameter names as well as values with an optional prepend of :php:`?` or :php:`&` if the query
string is not empty and skipping empty parameters.

.. _`PHP function`: https://secure.php.net/manual/de/function.http-build-query.php

Impact
======

Parameter arrays can be safely transformed into HTTP GET query strings using the new method.

.. index:: PHP-API, NotScanned
