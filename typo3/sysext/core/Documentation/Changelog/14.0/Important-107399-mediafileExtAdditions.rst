..  include:: /Includes.rst.txt

..  _important-107399-1757066244:

==================================================================
Important: #107399 - Add more common file types to `mediafile_ext`
==================================================================

See :issue:`107399`

Description
===========

The default configuration for `$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']`
has been extended with additional formats commonly uploaded to web-based
CMS systems such as TYPO3:

*  3gp
*  aac
*  aif
*  avif
*  heic
*  ico
*  m4a
*  m4v
*  mov
*  psd

This list is extended only for systems where the configuration option
has not already been customized.

Adding these formats allows files with these extensions to be uploaded
into the file list (and used in TCA fields with "common-media-types")
when the security flag `security.system.enforceAllowedFileExtensions`
is enabled.

This may be a relevant change if these file types are now used in
records or content elements that do not expect such files for further
operations (for example, in frontend display).

Integrators must ensure that all uploaded file types are handled
appropriately (for example, embedding "mov" as a video, "ico" as an
image, or "psd" as a download) or have a suitable fallback.

The `<f:media>` ViewHelper, for example, iterates over possible
`Renderer` implementations that can handle several of these file
types (and their MIME types) and attempts to render a file as an image
as a fallback.

..  index:: TCA, ext:core
