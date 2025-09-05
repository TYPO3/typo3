..  include:: /Includes.rst.txt

..  _important-107399-1757066244:

==================================================================
Important: #107399 - Add more common file types to `mediafile_ext`
==================================================================

See :issue:`107399`

Description
===========

The default configuration for `$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']`
has been enhanced with a list of formats commonly uploaded into web-based
CMS systems like TYPO3:

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

This list is only enhanced for systems where the said configuration option
has not been customized already.

Enhancing this list allows to upload files with these extensions into
the file list (and use these files in TCA fields with "common-media-types"),
when the security flag `security.system.enforceAllowedFileExtensions` is
enabled.

This can be a relevant change, if these file types are now used in
records or content elements, that might not expect files of these types
for further operation (like frontend display).

Integrators need to ensure that all uploaded file types are handled
accordingly (e.g. embedding "mov" as a video, "ico" as an image, and
"psd" as a download), or have an appropriate fallback.

The `<f:media>` ViewHelper for example would iterate possible `Renderer`
implementations, that can deal with several of the mentioned files
(their MIME types), and try to render a file as an image as fallback.

..  index:: TCA, ext:core
