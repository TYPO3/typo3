..  include:: /Includes.rst.txt

..  _feature-103594-1791966674:

=====================================================================
Feature: #103594 - Allowed file extensions per storage and file mount
=====================================================================

See :issue:`103594`

Description
===========

A file storage in :file:`config/system/file-storages.yaml` has the new
option :yaml:`allowedFileExtensions`, see :ref:`breaking-110762-1789862500`
for the file. It takes a list of file extensions, for example for a storage
meant for images:

..  code-block:: yaml
    :caption: config/system/file-storages.yaml

    storages:
      2:
        name: Images
        allowedFileExtensions: [jpg, jpeg, png, webp]
        configuration:
          basePath: images/

A comma-separated string such as :yaml:`'pdf,docx,xlsx'` is accepted as
well. The case of letters is ignored.

When the list is filled, a file can only enter the storage with one of these
extensions. This is checked by
:php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` for every operation that
puts a file into the storage or gives it a new extension:

*   uploading, adding and creating a file, including replacing a file by an
    upload
*   copying a file or a folder, which checks every visible file the folder
    contains
*   moving a file from another storage
*   renaming or moving a file to a different extension

A rejected operation throws an
:php-short:`\TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException`,
which the backend reports as a message.

Where files are added in the backend, the allowed file extensions of the
folder are shown, and an upload of another file is rejected right away.
Online media providers and new text files are only offered if their file
extension is allowed in the folder.

Files that already are in the storage are not affected as long as they keep
their extension. They can still be renamed, moved within the storage, deleted
and have their content replaced without an upload. Copying such a file
creates a new file and is rejected. Processed files, such as thumbnails, are
written to the processing folder directly and are not checked either.

The same applies to files that reach the storage without TYPO3, for example
by FTP, a deployment or another application writing to the same S3 bucket.
The restriction guards the operations of
:php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` only, it is not a
filter. Such files are indexed by the file list and the scheduler task
:guilabel:`File Abstraction Layer: Update storage index` as before, are
listed, can be referenced and are managed like any other file already in
place. Whoever writes to the storage directly has to enforce the
restriction there.

The list narrows what is allowed, it never widens it.
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']` is evaluated first
and keeps its meaning, and so does the system-wide list of allowed file
extensions enabled by the feature toggle
:php:`security.system.enforceAllowedFileExtensions`.

Only the file extension is compared. Whether the content of a file matches
its extension is checked by the existing consistency check of the File
Abstraction Layer, see :ref:`important-106240-1747316969`. It is enabled by
default through the feature toggle
:php:`security.system.enforceFileExtensionMimeTypeConsistency` and applies
when a file is added, uploaded, renamed or replaced, for file extensions with
a known MIME type.

File mounts
-----------

File mount records have the new field :guilabel:`Allowed file extensions`,
a comma-separated list. It restricts what backend users may put into the
folder of the mount, which is useful where one storage is split into folders
for different purposes, for example :file:`/images/` and :file:`/documents/`
in :file:`fileadmin/`.

The same operations are checked as for storages. Moving a folder checks the
files it contains as well, ignoring hidden files. A file that does not match
the file mounts it is located in may still be renamed and moved to another
folder that does not allow it either, as long as it keeps its extension.

As permissions of file mounts add up, a file extension is allowed in a folder
if any writable file mount of the user that contains the folder allows it. A
file mount without restriction therefore lifts the restriction of a file mount
for one of its subfolders. If a user gets the same folder mounted several
times through different groups, the lists are combined.

File mounts only apply to backend users without admin rights. Administrators,
frontend uploads and command line or API usage are restricted by the option of
the storage only.

Impact
======

Integrators can restrict which kinds of files end up in which storage or file
mount without writing a custom event listener. The option and the field are
empty for existing storages and file mounts, so nothing changes until they are
filled.

..  index:: Backend, Database, FAL, TCA, YAML, ext:core
