..  include:: /Includes.rst.txt

..  _feature-110702-1789927725:

=================================================
Feature: #110702 - Process system resource images
=================================================

See :issue:`110702`

Description
===========

The :html:`<f:image>` and :html:`<f:uri.image>` ViewHelpers can now
resize, crop and convert an image referenced via an :php:`EXT:`/:php:`PKG:`
system resource identifier, the same way they already do for a File
Abstraction Layer (FAL) file:

..  code-block:: html

    <f:image src="EXT:my_extension/Resources/Public/Images/logo.png" width="200c" />
    <f:uri.image src="EXT:my_extension/Resources/Public/Images/logo.png" maxWidth="400" />

The :html:`image` argument also accepts a public system resource object,
for example the one returned by :html:`<f:resource>`:

..  code-block:: html

    <f:image image="{f:resource(identifier: 'EXT:my_extension/Resources/Public/Images/logo.png')}" width="200c" />

The same applies to the TypoScript :typoscript:`imgResource` data type, used
for example by :typoscript:`IMAGE` and :typoscript:`IMG_RESOURCE`, including
masking (:typoscript:`m.mask`, :typoscript:`m.bgImg`, ...) with system
resources and FAL images in any combination, and :typoscript:`imageLinkWrap`:

..  code-block:: typoscript

    lib.logo = IMAGE
    lib.logo {
        file = EXT:my_extension/Resources/Public/Images/logo.png
        file.width = 200
        file.m.mask = EXT:my_extension/Resources/Public/Images/mask.png
        file.m.bgImg = EXT:my_extension/Resources/Public/Images/background.png
        imageLinkWrap = 1
        imageLinkWrap.enable = 1
    }

The same applies to an image referenced by a plain path to a public file
that is not part of any configured file storage, for example below
:file:`typo3temp/assets/` or :file:`uploads/`. Only public paths of the
project are considered, by default :file:`_assets/`,
:file:`typo3temp/assets/` and :file:`uploads/`. Further public paths can be
declared in :file:`config/system/resources.php`. Hidden files and files in
hidden directories are never processed this way. Paths within a configured
file storage, for example :file:`fileadmin/image.png`, keep resolving to
that storage.

This works without creating a :sql:`sys_file`/:sql:`sys_file_processedfile`
database entry: a system resource is not indexed and never was, and
processing it does not change that. A rendered variant is cached on disk
under :file:`typo3temp/assets/images/system/`, keyed by the resource's own
identifier and a hash of its current content, so a variant is reused
across requests and does not go stale across a release-based deployment
(where the absolute filesystem path changes on every release regardless
of whether the image itself changed). A result that uses the original
image as-is is recorded there as well, so it is not processed again.

A system resource has none of FAL's stored metadata (crop, alt, title): the :html:`crop`/:html:`alt`/:html:`title`
ViewHelper arguments are the only source for those, there is nothing
stored to fall back to.

Impact
======

Extension authors can reference an asset shipped with their extension
directly by its :php:`EXT:`/:php:`PKG:` path in a Fluid template or in
TypoScript and have
it processed like any other image, without first importing it into a FAL
storage or building a custom processing routine.

An :php:`EXT:`/:php:`PKG:` source previously still went through a FAL
compatibility storage to be processed at all, which created a
:sql:`sys_file_processedfile` row (and a matching :sql:`sys_file`-backed
original) for every distinct resource/configuration combination actually
rendered on a site - accumulating indefinitely for any site with a
non-trivial number of templates or crop variants using such images. None
of that happens anymore: processing a system resource now never touches
either database table, however many templates or configurations use it.

The same applied to plain paths to public files outside all file storages,
which were indexed in the FAL fallback storage (uid ``0``). Their processed
variants are now placed in :file:`typo3temp/assets/images/system/` instead
of :file:`typo3temp/assets/_processed_/`, and :typoscript:`imageLinkWrap`
links them to the :php:`tx_cms_showpic` eID by their resource identifier
instead of a :sql:`sys_file` uid.

This relies on the interface widening described in
:ref:`breaking-110702-1789915156`.

..  index:: Fluid, FAL, TypoScript, ext:core
