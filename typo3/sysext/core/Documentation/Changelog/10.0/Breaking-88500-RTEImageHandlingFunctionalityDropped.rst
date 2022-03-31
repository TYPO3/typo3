.. include:: /Includes.rst.txt

===========================================================
Breaking: #88500 - RTE image handling functionality dropped
===========================================================

See :issue:`88500`

Description
===========

With the replacement of CKEditor as RTE instead of RTEHtmlArea in TYPO3 v8, the native
and very incomplete functionality of having images within the RTE was unused.

It is still possible to use HTMLArea in further versions (if adopted), however the
handling of images is removed.

This includes:

* RTE processing mode ("ts_images")
* SoftReference Index for handling inline images
* Removed public method :php:`ImportExport->getRTEoriginalFilename()`
* Removed public method :php:`RteHtmlParser->TS_images_rte()`
* Removed CLI command "cleanup:rteimages" and relevant command class
* The configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']`


Impact
======

Images within an RTE field are not processed at all anymore, not part of the CLI.

Calling the CLI script, using the PHP methods or the PHP CLI command class directly
within PHP, will result in a PHP :php:`E_ERROR` error.

Accessing the configuration option will trigger a PHP :php:`E_NOTICE` error, as it is
silently removed, if customary set in :file:`LocalConfiguration.php`.


Affected Installations
======================

Any TYPO3 installation using images within CKEditor (with plugins) or still
using RTEHtmlArea.

Any TYPO3 installation triggering the CLI command, handling RTE images via EXT:impexp
or directly handling functionality from the CLI command PHP class.


Migration
=========

If necessary, it is recommended to add this functionality to a custom extension
where this functionality can live on. It is important however, that most of the
added functionality of TYPO3 in the last years was not supported (image cropping
inside RTE was not possible via the Image Cropper of FAL).

It is recommended to move all images within an RTE to proper relations, or to
use extensions like `rte_ckeditor_image` from https://extensions.typo3.org.

If any fork of RTEHtmlArea is still used in TYPO3 v10.0, the image functionality for
SoftRefParser, CLI command and the processing mode should be added there.

.. index:: CLI, PHP-API, RTE, PartiallyScanned
