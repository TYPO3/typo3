
.. include:: /Includes.rst.txt

======================================================================
Breaking: #72361 - Removed deprecated methods in ContentObjectRenderer
======================================================================

See :issue:`72361`

Description
===========

The following methods have been removed:

* `cleanFormName`
* `stdWrap_offsetWrap`
* `textStyle`
* `tableStyle`

The TypoScript `jumpurl` configuration has been removed for file links.
The TypoScript property `andWhere` from `.select` has been removed.


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to ContentObjects via the methods above.


Migration
=========

`cleanFormName` has been moved to `FormContentObject`.
`stdWrap_offsetWrap` has not been replaced by a new function.
`textStyle` TypoScript option should be done with CSS.
`tableStyle` TypoScript option should be done with CSS.

TypoScript option `jumpurl` can be passed in the `typolinkConfiguration` property.

TypoScript property `andWhere` can be migrated to `where`.

.. index:: PHP-API, TypoScript, Frontend
