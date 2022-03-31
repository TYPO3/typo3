.. include:: /Includes.rst.txt

=====================================================
Deprecation: #80510 - ContentObjectRenderer->URLqMark
=====================================================

See :issue:`80510`

Description
===========

The PHP method :php:`ContentObjectRenderer->URLqMark()` has been marked as deprecated. It was
used to add a ``?`` between two strings if the first one does contain a ``?`` already.

Its main purpose is to add query string parameters to a given URL.


Impact
======

Calling the method above will trigger a deprecation warning.


Affected Installations
======================

Any installation using custom extensions calling this method.


Migration
=========

Implement this functionality with PHP's native :php:`(strpos($haystack, '?') !== false ? '?' : '')`
one-liner directly.

.. index:: PHP-API, Frontend
