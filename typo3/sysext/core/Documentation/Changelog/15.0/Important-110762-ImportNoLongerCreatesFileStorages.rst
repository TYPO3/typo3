..  include:: /Includes.rst.txt

..  _important-110762-1789862503:

==================================================================
Important: #110762 - Import no longer creates file storage records
==================================================================

See :issue:`110762`

Description
===========

Export files created with EXT:impexp can contain :sql:`sys_file_storage`
records from the exporting installation. Until now, the import created a
storage record for every local, writable and online storage of the export
file that had no equivalent in the importing installation, and imported the
records of remote storages as duplicates.

A file storage is configuration of the installation, not content. The import
therefore no longer creates or imports storage records. Each storage of the
export file is mapped to a storage of the importing installation instead:

*   A local storage whose base path resolves to the same directory as a local
    storage of the export file is used for its files. A storage with the same
    identifier is preferred when several match.
*   The files of every other storage of the export file, local storages
    without a match and remote storages included, are imported into the
    default storage.
*   The files of a storage the export file has no storage record for are
    imported into the default storage as well. The identifier of a storage
    alone is never used, as it may denote an unrelated storage in the
    importing installation.

Every storage whose files are imported into the default storage this way is
reported as a warning in the import module and by the
:bash:`impexp:import` command. Such a warning does not let the import fail.

The export still writes a :sql:`sys_file_storage` record for the storage of
every exported file, which now only contains the identifier, the name and the
driver of the storage. For a local storage, it also contains the base path:
relative to the public path if the directory is located below it, absolute
otherwise. The driver configuration of other drivers is never exported, as
it may contain credentials.

The import preview lists storage records of the export file with the note
that they are not imported. The prerequisite check that refused an import
when the directory of a storage to be created did not exist is gone, as no
storage is created anymore.

..  index:: ext:impexp, FAL, NotScanned
