.. include:: ../../Includes.txt

=========================================================================
Deprecation: #80532 - GifBuilder-related methods in ContentObjectRenderer
=========================================================================

See :issue:`80532`

Description
===========

The following methods related to :php:`GifBuilder` within :php:`ContentObjectRenderer` have been marked
as deprecated.

* :php:`clearTSProperties()`
* :php:`gifBuilderTextBox()`
* :php:`linebreaks()`


Impact
======

Calling any of the methods above will trigger a deprecation message.


Affected Installations
======================

Any installation using these methods in custom extensions.

.. index:: Frontend, PHP-API
