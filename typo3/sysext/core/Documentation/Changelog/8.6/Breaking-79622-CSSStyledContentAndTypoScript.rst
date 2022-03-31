.. include:: /Includes.rst.txt

====================================================
Breaking: #79622 - CSS Styled Content and TypoScript
====================================================

See :issue:`79622`

Description
===========

In order to streamline CSS Styled Content and Fluid Styled Content several options
of CSS Styled Content have been dropped without replacement.

Options Dropped:

* TCA image_compression
* TCA image_effects
* TCA image_noRows
* TypoScript IMAGE noRows dropped
* TypoScript IMAGE noCols dropped
* TypoScript IMAGE noRowsStdWrap dropped
* TypoScript IMGTEXT captionAlign dropped


Impact
======

The options mentioned above will have no effect.


Affected Installations
======================

All Installations that use the options mentioned above.


Migration
=========

Image Compression
-----------------

Use global image compression configuration of TYPO3 instead of deciding
compression on content element level.


Image Effects
-------------

Use CSS to apply effects on Images.


No Rows
-------

Use CSS to style the output.


Caption Alignment
-----------------

Use CSS to align the caption text to your preference.


.. index:: Frontend, TypoScript, ext:css_styled_content
