..  include:: /Includes.rst.txt

..  _important-110333-1785414572:

===============================================================
Important: #110333 - File metadata is resolved in every context
===============================================================

See :issue:`110333`

Description
===========

The metadata of a file, stored in :sql:`sys_file_metadata`, is now resolved for
the language and the workspace of the current context in every context -
frontend, backend, command line and eID requests alike. Before, that resolution
was done by the event listener
:php:`\TYPO3\CMS\Frontend\Aspect\FileMetadataOverlayAspect`, which returned early
unless the current request was a frontend request, so translated metadata was
only ever used in the frontend.

:php:`\TYPO3\CMS\Core\Resource\Index\MetaDataRepository` now resolves the record
itself, before dispatching the PSR-14 event
:php:`\TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent`. Listeners of that
event therefore receive the already resolved record.

The obsolete class :php:`\TYPO3\CMS\Frontend\Aspect\FileMetadataOverlayAspect`
has been removed. It was marked :php:`@internal`, so no public API is affected.
Event listeners that were ordered before its listener identifier
:php:`typo3-frontend/overlay` to receive the default language record now receive
the resolved record as well. An ordering against that identifier is ignored and
can be dropped.

..  index:: PHP-API, FAL, ext:core
