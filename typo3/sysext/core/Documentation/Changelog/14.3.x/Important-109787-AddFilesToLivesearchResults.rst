..  include:: /Includes.rst.txt

..  _important-109787-1730981200:

====================================================
Important: #109787 - Add files to livesearch results
====================================================

See :issue:`109787`

Description
===========

The backend live search now surfaces files from the file abstraction
layer alongside pages and database records. A new live search provider
queries the :sql:`sys_file` and :sql:`sys_file_metadata` tables through
the existing :php:`\TYPO3\CMS\Core\Resource\Search\FileSearchQuery`, so
matches against file names as well as indexed metadata fields like
title, description or alternative text appear in the global search.

Results respect the user's file mounts via the existing
:php:`FolderMountsRestriction`, so non-admin users only see files within
their permitted scope.

The result detail panel has been extended to show a thumbnail for
previewable files (images, videos, PDFs and online media such as
YouTube or Vimeo) together with a list of properties such as the
location, file size and last modification date.


New API on ResultItem
=====================

To support richer detail rendering, two optional fields have been added
to :php:`\TYPO3\CMS\Backend\Search\LiveSearch\ResultItem`:

*   :php:`setThumbnailUrl(?string $thumbnailUrl)` — a URL to a preview
    image rendered in the detail panel; falls back to the icon when
    `null`.
*   :php:`addProperty(string $label, string $value)` — a key/value pair
    rendered as a description list in the detail panel. Useful for
    surfacing metadata like timestamps, sizes or relations.

Both fields default to empty, so existing providers continue to work
unchanged.


Impact
======

Backend users can now find files directly through the global live
search without switching to the media module. Custom providers can opt
into the new thumbnail and property fields to enrich their detail
views.

..  index:: Backend, ext:backend, ext:filelist
