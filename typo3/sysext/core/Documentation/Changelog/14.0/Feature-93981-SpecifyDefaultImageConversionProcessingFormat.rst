..  include:: /Includes.rst.txt

..  _feature-93981-1734803053:

=============================================================
Feature: #93981 - Specify default image conversion processing
=============================================================

See :issue:`93981`

Description
===========

Image processing in TYPO3 can now configure default and specific formats to
use when images are rendered or converted in the frontend, for example in
Fluid:

..  code-block:: html
    :caption: Example of image rendering in Fluid

    <f:image src="{asset.path}" width="200" />
    <f:image src="fileadmin/someFile.jpg" width="200" />
    <f:image image="{someExtbaseObject.someAsset}" width="200" />

Depending on the TYPO3 version, processed images were rendered with
:file:`.png` (or earlier, :file:`.gif` or :file:`.png`) file extensions, as
long as the `fileExtension` parameter with a fixed output format was not
specified.

This default solution had two major drawbacks:

1.  The default file format was hardcoded and not configurable.
2.  Utilizing new file formats (like `webp` and `avif`) required code changes.

This has now been changed with the new configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats']`.

This variable defaults to:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats'] = [
        'jpg' => 'jpg',
        'jpeg' => 'jpeg',
        'gif' => 'gif',
        'png' => 'png',
        'svg' => 'svg',
        'default' => 'png',
    ];

This means:

* When resizing or cropping an image with a file extension of `jpg`, `jpeg`,
  `gif`, `png`, or `svg` (and not setting a specific
  :html:`fileExtension` target format), those images will retain their
  respective file formats.
* Otherwise, the file format `png` is used.

Related configuration options that still apply:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']`
  Current default:
  `gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp,avif`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext']`
  Current default:
  `txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf,yaml,yml`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']`
  Current default:
  `gif,jpg,jpeg,bmp,png,webp,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,`
  `youtube,vimeo,avif`

These still define, per installation, which files can be uploaded or used as
images.

If a new format like :file:`heic` becomes supported by the used graphics
engine (for example GraphicsMagick or ImageMagick), system maintainers can add
the file extension to :php:`imagefile_ext`. TYPO3 will then recognize this
format and convert it to the default target format (`png` by default) when
processing images.

If the format should also be available as a *target* format, add
:php:`'heic' => 'heic'` to
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats']`.
Otherwise, `heic` images can be selected, but processing will still produce
`png`.

If all image processing (resizing, thumbnails, PDF previews, etc.) should use
`heic`, set :php:`'default' => 'heic'` and remove other entries.

Currently, the option cannot be configured via
:guilabel:`System > Settings > Configure options ...` because it uses
an array syntax. It can be changed manually in :file:`settings.php`. GUI
support may be added in a future release.

The array notation allows defining a target (thumbnail) file extension for
each original file extension individually using the format
`{originalExtension} => {targetExtension}`.

Example:

..  code-block:: php
    :caption: Individual thumbnail formats per file extension

    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats'] = [
        'jpg' => 'jpg',
        'svg' => 'svg',
        'ai' => 'png',
        'heic' => 'webp',
        'default' => 'avif',
    ];

This configuration would produce the following behavior:

..  code-block:: html
    :caption: Fluid image rendering results

     <f:image src="somefile.jpg" width="80">
      -> renders an 80 px thumbnail from "somefile.jpg" to "somefile.jpg"
         (rule: "jpg => jpg")

    <f:image src="somefile.gif" width="80">
      -> renders an 80 px thumbnail from "somefile.gif" to "somefile.avif"
         (rule: "default => avif")

    <f:image src="somefile.png" width="80">
      -> renders an 80 px thumbnail from "somefile.png" to "somefile.avif"
         (rule: "default => avif")

    <f:image src="somefile.svg" width="80">
      -> renders the original SVG at 80 px width
         (rule: "svg => svg")

    <f:image src="somefile.pdf" width="80">
      -> renders an 80 px PDF thumbnail to "somefile.avif"
         (rule: "default => avif")

    <f:image src="somefile.heic" width="80">
      -> renders an 80 px thumbnail from "somefile.heic" to "somefile.webp"
         (rule: "heic => webp")

Impact
======

TYPO3 is now more future-proof regarding new image formats and allows modern
file formats to be used by configuration.

Projects can now specify precisely how each format should be converted or
which default format should be used for processed images.

..  index:: Frontend, ext:core, ext:frontend
