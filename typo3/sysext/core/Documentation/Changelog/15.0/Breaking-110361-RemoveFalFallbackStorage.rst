.. include:: /Includes.rst.txt

.. _breaking-110361-1785715200:

=======================================================
Breaking: #110361 - Remove FAL fallback storage (uid 0)
=======================================================

See :issue:`110361`

Description
===========

The virtual "fallback storage" with uid ``0`` of the File Abstraction Layer
(FAL) has been removed. It was introduced together with FAL in TYPO3 6.0 as a
compatibility bridge for pre-FAL file references and mounted the whole public
web directory as an implicitly public, browsable and writable storage.

Since backend permission evaluation was never applied to this virtual storage,
it has been the root cause of a series of security issues and required more
than a dozen scattered guard checks throughout the TYPO3 Core. Every file
handled by FAL now resides in a configured file storage
(:file:`config/system/file-storages.yaml`).

Files shipped with extensions (``EXT:`` paths) are no longer resolved through
FAL at all. They are processed as system resources without FAL persistence,
see :ref:`feature-110702-1789927725`. The same applies to public files outside
all configured file storages, for example below :file:`typo3temp/assets/`.


Impact
======

The following behavior changes apply:

- :php:`StorageRepository->findByUid(0)` returns :php:`null`, and
  :php:`StorageRepository->getStorageObject()` throws an
  :php:`\InvalidArgumentException` for storage uid ``0``.

- The method :php:`ResourceStorage->isFallbackStorage()` and the protected
  method :php:`FilePermissionAspect->usesLegacyStorage()` have been removed.

- :php:`ResourceFactory->createFileObject()`, and therefore
  :php:`ResourceFactory->getFileObject()`, throw a
  :php:`FileDoesNotExistException` instead of a :php:`\RuntimeException` for
  file records whose storage is not configured. Code catching
  :php:`ResourceDoesNotExistException`, such as
  :php:`FileRepository->findByRelation()` and the file link handler, skips such
  files instead of failing.

- :php:`ResourceFactory->retrieveFileOrFolderObject()` throws a
  :php:`ResourceDoesNotExistException` for ``EXT:`` paths and returns
  :php:`null` for paths outside all configured file storages. Plain paths
  within a configured local storage (e.g. ``fileadmin/some/file.png``) keep
  resolving to that storage.

- Combined identifiers without a storage prefix passed to
  :php:`ResourceFactory->getFileObjectFromCombinedIdentifier()` or
  :php:`ResourceFactory->getFolderObjectFromCombinedIdentifier()` are resolved
  to the best-matching storage, or a
  :php:`FileDoesNotExistException` / :php:`FolderDoesNotExistException` is
  thrown if the path is outside all storages. The same exceptions are thrown
  for combined identifiers of storage ``0``, e.g. ``0:/uploads/file.png``.

- Images referenced by a plain path outside all configured file storages
  and outside the public paths of the project (by default
  :file:`typo3temp/assets/`, :file:`uploads/` and :file:`_assets/`) are not
  rendered anymore by :html:`<f:image>`, :html:`<f:uri.image>`,
  :typoscript:`imgResource` and :typoscript:`imageLinkWrap`. The Fluid
  ViewHelpers throw an exception, TypoScript renders nothing and logs a
  warning.

- :php:`ImageService->getImage()` throws an :php:`\UnexpectedValueException`
  for sources outside all file storages.

- ``t3://file?identifier=...`` links without a storage prefix only resolve for
  files within a configured storage, otherwise the link yields no file.
  Legacy ``file:`` links to ``EXT:`` or ``PKG:`` resources resolve to the
  published URL of the resource. Soft references, and therefore exports of
  EXT:impexp, keep the resource identifier, so such links stay valid on other
  installations.

- Records still pointing to storage ``0``, for example in
  :sql:`sys_file` before the upgrade wizard ran, can not be resolved anymore.
  References to such files are omitted when rendering, links to them yield no
  file, and their metadata can not be edited.

- The Install Tool action "Remove temporary assets" handles
  :file:`typo3temp/assets/_processed_/` as a plain directory without FAL
  involvement. The folder structure check and the installation status report
  do not create or check this directory anymore, it can be removed.


Affected installations
======================

All installations are affected by the removed uid ``0`` API behavior.
Installations referencing files outside all configured file storages (for
example legacy ``uploads/`` files), installations with :sql:`sys_file` records
carrying ``storage = 0``, and extensions calling the removed or changed
methods are affected in particular.

The extension scanner reports usages of
:php:`ResourceStorage->isFallbackStorage()` and
:php:`FilePermissionAspect->usesLegacyStorage()`.


Migration
=========

Run the upgrade wizard "Migrate files of the removed fallback storage
(uid 0)": It moves :sql:`sys_file` records with ``storage = 0`` into the
file storage their file resides in, merges records duplicating already
indexed files, flags records outside all storages as missing, and removes
processed file records of the fallback storage (they are regenerated on
demand).

When a record is merged into an already indexed record of the same file, file
references are moved to the indexed record, while the metadata of the merged
record is discarded. Links to the merged record in rich text or link fields
(``t3://file?uid=...``) are not updated and must be adjusted manually; the
upgrade wizard is only affected if a file storage has been added for a
directory whose files were already indexed in storage ``0``.

Records flagged as missing are attached to the default storage, keeping their
former identifier, e.g. ``/uploads/pics/image.jpg``. To restore such files,
run the upgrade wizard first, then move the directory into the default
storage at the same path (e.g. :file:`fileadmin/uploads/pics/`) and update
the file index of that storage, for example via the scheduler task "File
Abstraction Layer: Update storage index". The records are found again and
keep their references.

Move files that are referenced from outside all storages and outside the
public paths of the project into a configured file storage such as
:file:`fileadmin/`, or reference them via ``EXT:`` identifiers. Further
public paths can be declared in :file:`config/system/resources.php`.

Custom code dealing with stringy image sources that may point to files
outside all file storages should resolve them with the System Resource API
(:php:`SystemResourceFactory->createPublicResource()`) and process them as
described in :ref:`feature-110702-1789927725`.

.. index:: FAL, Fluid, Frontend, PHP-API, TypoScript, PartiallyScanned, ext:core
