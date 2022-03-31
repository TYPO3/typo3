
.. include:: /Includes.rst.txt

======================================================
Breaking: #74031 - CharsetConverter parameters removed
======================================================

See :issue:`74031`

Description
===========

The second parameter for the method `CharsetConverter->entities_to_utf8()` has been removed.

The second and the third parameter of `CharsetConverter->utf8_to_numberarray()` has been removed.


Impact
======

When calling `CharsetConverter->entities_to_utf8()`, string-HTML entities (like &amp; or &pound;) will be converted
to UTF-8 as well at all times. Previously this behaviour was configurable.

When calling `CharsetConverter->utf8_to_numberarray()`, string-HTML entities (like
&amp; or &pound;) will be converted to UTF-8 as well at all times. Additionally instead
of integer numbers the real UTF-8 char is returned at any times. Previously these
behaviours were configurable.


Affected Installations
======================

Installations with custom extensions that used these methods directly in PHP code.


Migration
=========

Remove these parameters from the calling PHP code.

.. index:: PHP-API
