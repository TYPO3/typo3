
.. include:: ../../Includes.txt

=============================================================
Deprecation: #66906 - Functionality for png_to_gif conversion
=============================================================

See :issue:`66906`

Description
===========

The global option `$TYPO3_CONF_VARS[GFX][png_to_gif]` has been removed. The according functionality within
`GraphicalFunctions->pngToGifByImagemagick()` has been marked for deprecation.


Impact
======

Any direct calls using `pngToGifByImagemagick()` will now throw a deprecation warning. All installations having the
option `png_to_gif` activated will now always show png files instead of gifs when resizing PNG images in the
TYPO3 Frontend.


Affected Installations
======================

Any installation having png_to_gif activated or having third-party extensions calling
`GraphicalFunctions->pngToGifByImagemagick()` directly.


Migration
=========

Remove calls to the functionality, as the result will be a PNG. If GIF conversion is needed, the functionality needs
to be implemented in a custom FAL Processor inside an extension.


.. index:: PHP-API, Backend, LocalConfiguration, FAL
