.. include:: ../../Includes.txt

===============================================================
Deprecation: #94193 - Public url with relative paths in FAL API
===============================================================

See :issue:`94193`

Description
===========

The public FAL API for accessing the public url of a FAL object,
e.g. :php:`FileReference` or :php:`Folder`, previously allowed to
retrieve the relative path instead of the absolute path. This could
be achieved by setting :php:`$relativeToCurrentScript` to :php:`true`
while calling :php:`getPublicUrl()`.

However, only under some circumstances FAL is actually able to build such
relative links. If at all, this only worked for local drivers. Other drivers
would still return the absolute URL, which has often led to unexpected
side-effects.

Since both, frontend (site handling) and backend (url routing) are meanwhile
fully capable of supporting absolute URLs, :php:`$relativeToCurrentScript`
is now deprecated and will finally be removed in TYPO3 v12.0.

This also affects the :php:`isRelativeToCurrentScript()` method in the
:php:`GeneratePublicUrlForResourceEvent` event, as well as the
:php:`OnlineMediaHelperInterface`.

Impact
======

Calling :php:`getPublicUrl()` on a FAL object, e.g. :php:`FileReference`
or :php:`Folder`, with :php:`$relativeToCurrentScript` set to :php:`true`
will trigger a PHP :php:`E_USER_DEPRECATED` error. The extension scanner
will detect such calls.

Accessing :php:`isRelativeToCurrentScript()` on
:php:`GeneratePublicUrlForResourceEvent` will trigger a PHP
:php:`E_USER_DEPRECATED` error. The extension scanner will detect
such calls.

Manually calling :php:`getPublicUrl()` on an :php:`OnlineMediaHelper`,
e.g. :php:`YoutubeHelper`, will not trigger a PHP :php:`E_USER_DEPRECATED`
error, but the extension scanner will detect such calls.

Affected Installations
======================

All installations which set :php:`$relativeToCurrentScript` to :php:`true`
when calling :php:`getPublicUrl()` on a FAL object, e.g. :php:`FileReference`
or :php:`Folder`.

All installations which manually call :php:`getPublicUrl()` on an
:php:`OnlineMediaHelper`, e.g. :php:`YoutubeRenderer`.

All installation which access :php:`isRelativeToCurrentScript()` on the
:php:`GeneratePublicUrlForResourceEvent` event.

Migration
=========

Remove the :php:`$relativeToCurrentScript` parameter from all calls to
:php:`getPublicUrl()` on FAL objects, e.g. :php:`FileReference` or :php:`Folder`.

Remove the :php:`$relativeToCurrentScript` parameter from all manual calls
to :php:`getPublicUrl()` on an :php:`OnlineMediaHelper`, e.g. :php:`YoutubeHelper`.

Remove all calls to :php:`GeneratePublicUrlForResourceEvent->isRelativeToCurrentScript()`.

.. index:: FAL, PHP-API, FullyScanned, ext:core
