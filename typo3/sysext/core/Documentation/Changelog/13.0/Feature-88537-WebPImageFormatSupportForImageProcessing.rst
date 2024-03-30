.. include:: /Includes.rst.txt

.. _feature-88537-1702764465:

================================================================
Feature: #88537 - WebP image format support for Image Processing
================================================================

See :issue:`88537`

Description
===========

WebP [https://en.wikipedia.org/wiki/WebP] is a modern image format for the web
that comes with several advantages over PNG or JPEG image files:

- WebP images have roughly 30% smaller file size compared to JPEG or PNG files
- WebP images support an alpha channel (transparency) which JPEG files do not support

WebP is support by all modern browsers [https://caniuse.com/webp], and is
available for processing / generation in most ImageMagick / GraphicsMagick
versions.

TYPO3 can now generate WebP images, if the underlying
ImageMagick / GraphicsMagick library supports WebP.


Impact
======

By default, WebP images can now be generated, as TYPO3's configuration setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']` is now extended with
"webp".

Integrators can now use the file extension `webp` in their Fluid template or
Fluid templates or PHP code when interacting with the underlying Processing API
or the Graphical Functions API.

The Install Tool / Environment Module displays if support for generating WebP
image files is possible. In addition, a new report in the :guilabel:`System > Reports`
module of TYPO3 backend shows, if TYPO3 is properly configured for generating WebP
image files.

If the underlying ImageMagick / GraphicsMagick library is not built with
WebP support, the server administrators can install or recompile the library
with WebP support by installing the `cwebp` or `dwebp` libraries.

The default quality of generated WebP image files can be defined via
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['webp_quality']` which requires a value
between 1 (low quality, small file size) and 100 (best quality, large file size),
or set to `lossless` which uses the lossless compression format. Even lossless
compression for converting, for example,  PNG files will result in smaller file
sizes as WebP [https://developers.google.com/speed/webp/gallery2].

Depending on the target audience of the TYPO3 Frontend, it may be valid to
disable WebP support by removing "webp" from the `imagefile_ext` setting.

.. index:: FAL, Fluid, Frontend, LocalConfiguration, ext:core
