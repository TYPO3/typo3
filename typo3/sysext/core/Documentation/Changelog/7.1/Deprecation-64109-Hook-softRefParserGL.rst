
.. include:: /Includes.rst.txt

============================================================
Deprecation: #64109 - Deprecate global hook softRefParser_GL
============================================================

See :issue:`64109`


Description
===========

The hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL']` has been marked as deprecated.
It was a hook to add a general softRefParser which parsed every SoftReference regardless of its type.
The `softRefParser_GL`-hook was undocumented and used neither in core nor in any known extension.


Impact
======

Creating a global softRefParser by adding a hook to
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL']` will trigger a deprecation log message.


Affected installations
======================

Instances with extensions using a `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL']`-hook


Migration
=========

A `softRefParser_GL` hook in an extension has to be replaced with multiple `softRefParser` hooks for each type the
parser can handle.


.. index:: PHP-API
