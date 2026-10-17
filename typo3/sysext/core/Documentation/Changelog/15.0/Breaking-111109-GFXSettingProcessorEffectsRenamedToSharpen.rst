..  include:: /Includes.rst.txt

..  _breaking-111109-1792229915:

====================================================================
Breaking: #111109 - GFX setting processor_effects renamed to sharpen
====================================================================

See :issue:`111109`

Description
===========

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects']`
has been renamed to :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['sharpen']`,
which is what it does. Its description claimed that it enabled blurring and
sharpening, which it did not.

What the setting does has changed as well. ImageMagick and GraphicsMagick
used to sharpen every scaled JPEG, WebP and AVIF image with
`-sharpen 1x2`, which made edges noticeably harder and the files a quarter
larger. They now sharpen these images with the lighter `-unsharp 1x2+0.25+0`,
and only when they scale them down. A scaled-up image gains next to nothing
from sharpening and is no longer sharpened. Which formats are sharpened does
not change: JPEG, WebP and AVIF, as before; PNG and GIF never were.

The setting is enabled by default, and both the ImageMagick and the
GraphicsMagick preset of the Install Tool enable it. GraphicsMagick sharpens
with the same parameters as ImageMagick.

The upgrade migrates the setting in :file:`config/system/settings.php`.

Impact
======

Extensions that read the setting `processor_effects` no longer find it.

Installations that sharpened images look slightly softer after the processed
files are generated again, and the processed JPEG, WebP and AVIF files get
smaller. Installations that did not sharpen and have not set the option now
sharpen scaled-down images slightly.

:php:`GraphicalFunctions->cmds` no longer carries the sharpening parameters.

Affected installations
======================

Extensions that read the setting. The extension scanner reports them.

Installations that process images with ImageMagick or GraphicsMagick.

Migration
=========

Read :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['sharpen']` instead.

To keep images unsharpened, set
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['sharpen']` to :php:`false`.

..  index:: LocalConfiguration, PartiallyScanned, ext:core
