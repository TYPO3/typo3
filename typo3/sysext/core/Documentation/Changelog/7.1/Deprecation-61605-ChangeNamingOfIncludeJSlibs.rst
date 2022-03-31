
.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #61605 - Change naming of TypoScript property page.includeJSlibs
=============================================================================

See :issue:`61605`

Description
===========

The existing TypoScript option `page.includeJSlibs` has been renamed
to `page.includeJSLibs` to follow the lower camel case naming scheme.
The existing property has been marked as deprecated.

Impact
======

The old property will be removed with CMS 8 and should be avoided if
it has been used before.

Affected Installations
======================

Any installation using the `page.includeJSlibs` option.

Migration
=========

Search and replace all TypoScript code of the installation from
`includeJSlibs` to `includeJSLibs`.


.. index:: TypoScript, Frontend
