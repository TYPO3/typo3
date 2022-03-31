.. include:: /Includes.rst.txt

================================================================
Deprecation: #94193 - Public URLs with relative paths in FAL API
================================================================

See :issue:`94193`

Description
===========

The public FAL API for accessing the public url of a FAL object,
for example :php:`\TYPO3\CMS\Core\Resource\FileReference` or
:php:`\TYPO3\CMS\Core\Resource\Folder`, previously allowed to
retrieve the relative path instead of the absolute path. This could
be achieved by setting :php:`$relativeToCurrentScript` to :php:`true`
while calling :php:`getPublicUrl()`.

FAL is only able to build relative links for local drivers. Other drivers
would still return the absolute URL, which has often led to unexpected
side effects.

Since both, frontend (site handling) and backend (url routing) are meanwhile
fully capable of supporting absolute URLs, :php:`$relativeToCurrentScript`
is now deprecated and will be removed in TYPO3 v12.

This also affects the :php:`isRelativeToCurrentScript()` method in the
:php:`GeneratePublicUrlForResourceEvent` event, as well as the
:php:`OnlineMediaHelperInterface`.

Impact
======

Calling :php:`getPublicUrl()` on a FAL object, for example
:php:`\TYPO3\CMS\Core\Resource\FileReference` or
:php:`\TYPO3\CMS\Core\Resource\Folder`, with :php:`$relativeToCurrentScript`
set to :php:`true`
will trigger a PHP :php:`E_USER_DEPRECATED` error. The extension scanner
will detect such calls.

Accessing :php:`isRelativeToCurrentScript()` on
:php:`GeneratePublicUrlForResourceEvent` will trigger a PHP
:php:`E_USER_DEPRECATED` error. The extension scanner will detect
such calls.

Manually calling :php:`getPublicUrl()` on an :php:`OnlineMediaHelper`,
for example :php:`YoutubeHelper`, will not trigger a PHP :php:`E_USER_DEPRECATED`
error, but the extension scanner will detect such calls.

Affected Installations
======================

All installations which set :php:`$relativeToCurrentScript` to :php:`true`
when calling :php:`getPublicUrl()` on a FAL object, for example
:php:`\TYPO3\CMS\Core\Resource\FileReference` or
:php:`\TYPO3\CMS\Core\Resource\Folder`.

All installations which manually call :php:`getPublicUrl()` on an
:php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelper`,
for example :php:`\TYPO3\CMS\Core\Resource\Rendering\YoutubeRenderer`.

All installation which access :php:`isRelativeToCurrentScript()` on the
:php:`\TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent` event.

Migration
=========

Remove the :php:`$relativeToCurrentScript` parameter from all calls to
:php:`getPublicUrl()` on FAL objects, for example
:php:`\TYPO3\CMS\Core\Resource\FileReference` or
:php:`\TYPO3\CMS\Core\Resource\Folder`.

Remove the :php:`$relativeToCurrentScript` parameter from all manual calls
to :php:`getPublicUrl()` on a
:php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelper`,
for example :php:`\TYPO3\CMS\Core\Resource\Rendering\YoutubeRenderer`.

Remove all calls to
:php:`\TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent->isRelativeToCurrentScript()`.

.. index:: FAL, PHP-API, FullyScanned, ext:core
