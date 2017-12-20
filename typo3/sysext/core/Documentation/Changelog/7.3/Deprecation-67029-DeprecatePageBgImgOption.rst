
.. include:: ../../Includes.txt

=================================================
Deprecation: #67029 - Deprecate page.bgImg option
=================================================

See :issue:`67029`

Description
===========

The option `page.bgImg` has been marked for deprecation and will be removed with TYPO3 CMS 8.


Impact
======

Using `page.bgImg` will throw a deprecation message.


Affected Installations
======================

Any installation which uses this TypoScript option.


Migration
=========

Use CSS to set a background on the body.


.. index:: TypoScript, Frontend
