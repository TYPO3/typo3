.. include:: /Includes.rst.txt

.. _deprecation-100461-1680690006:

============================================================
Deprecation: #100461 - TypoScript option config.xhtmlDoctype
============================================================

See :issue:`100461`

Description
===========

The TypoScript option :typoscript:`config.xhtmlDoctype` has been marked as
deprecated. This is done in order to consolidate TypoScript options, as the
option  :typoscript:`config.doctype` is now the default.


Impact
======

Having :typoscript:`config.xhtmlDoctype` set, but not :typoscript:`config.doctype`
will trigger a TypoScript deprecation warning.


Affected installations
======================

TYPO3 installations having this TypoScript instruction set.


Migration
=========

If the property  :typoscript:`config.xhtmlDoctype` is set, replace it with
:typoscript:`config.doctype`.

.. index:: TypoScript, NotScanned, ext:frontend
