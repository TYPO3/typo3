.. include:: ../../Includes.txt

=======================================================================
Deprecation: #95349 - TypoScript: page.includeCSS/includeCSSLibs.import
=======================================================================

See :issue:`95349`

Description
===========

The option to use the `@import` syntax for including
external CSS files through TypoScript has been deprecated.

This was possible through:

page = PAGE
page.includeCSSLibs.file1 = fileadmin/benni.css
page.includeCSSLibs.file1.import = 1

Through the "import = 1" option the output was

<style>
 @import url('fileadmin/benni.css');
</style>


Impact
======

A PHP deprecation warning is triggered when having the :typoscript:`import = 1`
flag enabled in TypoScript on :typoscript:`includeCSS` or :typoscript:`includeCSSLibs`
properties.


Affected Installations
======================

TYPO3 installation with the TypoScript settings:

:typoscript:`page.includeCSS.aFile.import = 1`
:typoscript:`page.includeCSSLibs.aFile.import = 1`

enabled.


Migration
=========

Using the :html:`<link>` tag syntax, which is the de-facto syntax these days,
allows to load a file directly when interpreting the HTML of the
browser, instead of first interpreting the HTML, then the CSS
and have a blocking call to an external URL to continue interpreting the CSS.

It is recommended to use the :html:`<link>` tag or create a inlineCSS TypoScript
manually to load such a file with the :css:`@import` syntax.

.. index:: TypoScript, NotScanned, ext:frontend
