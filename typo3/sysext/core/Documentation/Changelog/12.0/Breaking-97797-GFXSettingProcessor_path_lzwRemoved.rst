.. include:: /Includes.rst.txt

.. _breaking-97797-1655730428:

=========================================================
Breaking: #97797 - GFX setting processor_path_lzw removed
=========================================================

See :issue:`97797`

Description
===========

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw']`
was used to compress GIF and TIFF files with a different ImageMagick version,
as LZW compression was removed from the distributed ImageMagick binaries back in
2004-2006.

Since then, both GIF and TIFF have had reduced impact on the web we know today.

For this reason, the value is removed. If GIF compression via LZW is wanted,
it should be pointing to the main `processor_path` setting.

Impact
======

Compression via LZW for GIF files is now only applied when the corresponding
ImageMagick version, found in `processor_path` is supporting LZW compression.

The GFX setting `processor_path_lzw` is not used anymore, and can safely be
removed. When accessing the Install Tool, the setting is automatically removed
from :file:`LocalConfiguration.php`.

Affected installations
======================

TYPO3 installations actively using GIF compression or GIF thumbnails over PNG
thumbnails (if `GFX/thumbnails_png` is set to false), which might result in
GIF files with a larger file size.

Migration
=========

It is recommended to switch to PNG thumbnails (TYPO3 setting `GFX/thumbnails_png`),
or use an ImageMagick version supporting LZW compression for GIF files, if this
functionality is explicitly needed.

In addition, solutions such as `gifsicle` can be used instead to optimize
GIF images.

.. index:: Frontend, PartiallyScanned, ext:core
