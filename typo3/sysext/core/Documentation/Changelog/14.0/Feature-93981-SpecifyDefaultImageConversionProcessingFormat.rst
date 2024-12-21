..  include:: /Includes.rst.txt

..  _feature-93981-1734803053:

=============================================================
Feature: #93981 - Specify default image conversion processing
=============================================================

See :issue:`93981`

Description
===========

ImageProcessing in TYPO3 can now configure default and specific
formats to use when images are rendered / converted in the frontend,
like in Fluid:

..  code-block:: html
    :caption: Example of image rendering in Fluid

    <f:image src="{asset.path}" width="200" />
    <f:image src="fileadmin/someFile.jpg" width="200" />
    <f:image image="{someExtbaseObject.someAsset}" width="200" />

Depending on the TYPO3 version, processed images were rendered with
:file:`.png` (or earlier, :file:`.gif/.png`) file extensions, as long
as the `fileExtension` parameter with a fixed output format was
not specified.

This default solution had two major drawbacks:

1.  The default file format was hardcoded and not configurable.

2.  Utilizing new file formats (like `webp` and `avif`) required
    code changes.

This has now been changed with the new configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats']`.

This variable is set to the array of
:php:`['jpg' => 'jpg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'png' => 'png', 'svg' => 'svg', 'default' => 'png']`
by default, which means:

*  When resizing/cropping an image with a file extension `jpg`, `jpeg`, `gif`, `png`, `svg`
   (and not setting a specific :html:`fileExtension` target format), all these
   images will retain their respective file formats.
*  Otherwise, the file format `png` is used.

Related to this, the following configuration options still apply:

*  :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']`
    current default: `gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp,avif`
*  :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext']`
    current default: `txt,ts,typoscript,html,htm,css,tmpl,js,sql,xml,csv,xlf,yaml,yml`
*  :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']`
    current default: `gif,jpg,jpeg,bmp,png,webp,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,youtube, vimeo,avif`

These are still used to indicate on a per-installation level, which
kind of files can be uploaded/uses as image files.

If in the future a new file format like :file:`heic` can be used on all platforms,
and the used graphic engine (like GraphicsMagick or ImageMagick) supports writing these formats,
system maintainers can enable TYPO3 to utilize this format in TYPO3 by adding that file
extension to the list of `imagefile_ext`. This would recognize that new image format,
and by default on any image crop/conversion operation, would convert that to the
default target format (`png` for now).

If additionally that format should be usable by image processing operations with
that output format, this can be achieved by adding `heic => heic` to the option
:php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats']`. Without
adding the file extension to that list, `heic` images could be selected as images,
but default file processing would always enforce `png`.

If the image format `heic` should be used for ALL default image processing (even cropping
or resizing `jpg`, creating `pdf` thumbnails and any other images), this can be achieved
by setting the special key `default => heic` (instead of `default => png`) and dropping
all other image formats from the array.

The option can not yet be configured in the :guilabel:`Admin Tools > Settings > Configure options ...`
backend module due to its array syntax. The ability to set the option in the GUI
may be planned for a follow-up feature. This means, for now, it can only be set
by editing the :file:`settings.php` file manually.

The array notation allows to not only set the `default => {fileExtension}` key and file extensions, but
also to set a processed (thumbnail) target file extension for each original file extension
individually. This can be achieved by using the notation `{originalFileExtension} => {processingFileExtension}`.

For example this configuration:

..  code-block:: php
    :caption: Individual thumbnail formats per file extension

    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imageFileConversionFormats'] = [
        'jpg' => 'jpg',
        'svg' => 'svg',
        'ai' => 'png',
        'heic' => 'webp',
        'default' => 'avif',
    ];

would render images like this:

..  code-block:: html
    :caption: Fluid image rendering

    <f:image src="somefile.jpg" width="80">
      -> would render a 80px thumbnail from
         "somefile.jpg" to "somefile.jpg" due to "jpg => jpg"

    <f:image src="somefile.gif" width="80">
      -> would render a 80px thumbnail
         from "somefile.gif" to "somefile.avif" due to "default => avif"
         and no distinct format listing

    <f:image src="somefile.png" width="80">
      -> would render a 80px thumbnail
         from "somefile.png" to "somefile.avif" due to "default => avif"
         and no distinct format listing

    <f:image src="somefile.svg" width="80">
      -> would render the original SVG at 80px width
         from "somefile.svg" to "somefile.svg" due to "svg => svg"

    <f:image src="somefile.pdf" width="80">
      -> would render a PDF thumbnail at 80px width
         from "somefile.pdf" to "somefile.avif" due to "default => avif"

    <f:image src="somefile.heic" width="80">
      -> would render a 80px thumbnail
         from "somefile.heic" to "somefile.webp" due to "heic => webp"

Impact
======

TYPO3 is now more future-proof for using upcoming file formats, and allows to utilize
modern file formats by configuration.

Also, projects can now receive specific configuration on how to convert each file
format as a processed image as well as the default format.

..  index:: Frontend, ext:core, ext:frontend
