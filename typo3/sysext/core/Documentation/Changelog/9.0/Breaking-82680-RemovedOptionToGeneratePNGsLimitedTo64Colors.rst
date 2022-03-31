.. include:: /Includes.rst.txt

=======================================================================
Breaking: #82680 - Removed option to generate PNGs limited to 64 colors
=======================================================================

See :issue:`82680`

Description
===========

The option to generate PNGs with only 64 colors called :php:`$TYPO3_CONF_VARS[GFX][png_truecolor]` has been removed.

The public PHP property `GraphicalFunctions->png_truecolor` has been removed.


Impact
======

Setting the option has no effect anymore, as resized PNG images are always truecolor.


Affected Installations
======================

Any installation having this option disabled.


Migration
=========

Accessing the Install Tool removes the option. If necessary, the option can be set via TypoScript GIFBUILDER `reduceColors`.

.. index:: LocalConfiguration, PHP-API, NotScanned
