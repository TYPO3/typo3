..  include:: /Includes.rst.txt

..  _deprecation-108653-1741600000:

==========================================================
Deprecation: #108653 - Form file-based storage deprecated
==========================================================

See :issue:`108653`

Description
===========

File-based form storage (YAML files stored via file mounts) in
`EXT:form` has been deprecated in favor of database storage.

Since TYPO3 v14.2, the `EXT:form` module stores form definitions as
records in the :sql:`form_definition` database table. This approach provides
simpler setup, integrates better with the TYPO3 permission system, and
eliminates the need for file mounts and file system configuration.

The following components are deprecated and will be removed in TYPO3 v15.0:

*   :php:`\TYPO3\CMS\Form\Storage\FileMountStorageAdapter` – the storage
    adapter for FAL file mount-based form persistence
*   The YAML configuration option :yaml:`persistenceManager.allowedFileMounts`
    – configuring allowed file mounts for form storage

See :ref:`feature-108653-1767199420` for the new database storage approach.

An upgrade wizard as well as a CLI command are available to migrate existing file-based form definitions
to the database: :guilabel:`System > Upgrade > Upgrade Wizard > Migrate file-based forms to database storage`.

..  note::

    YAML form files provided within extension directories
    (:yaml:`persistenceManager.allowedExtensionPaths`) still work as before
    and are not affected by this deprecation until further concepts are evaluated.


Impact
======

File-based form storage will continue to work without any functional changes
during the deprecation period. However, it will be removed in TYPO3 v15.0.

An upgrade wizard is available to check whether file-based forms exist
and to migrate them to database storage. Run the wizard regularly to verify
your migration status.


Affected installations
======================

All installations that:

*   Store form definitions as YAML files in file mounts
    (for example, :file:`1:/form_definitions/`)
*   Use the :yaml:`persistenceManager.allowedFileMounts` configuration
    option in their form setup YAML with one or more mount points configured


Migration
=========

..  warning::

    If your installation uses file mount–based permission separation (i.e.,
    different backend user groups have access to different form storage
    folders to isolate which forms they can see and edit), an equivalent
    access control mechanism for database-stored forms is **not yet available**.

    In this case, it is recommended to **not migrate at this time**. The
    file-based storage will continue to work without functional changes during
    the entire deprecation period. A dedicated permission feature for
    database storage is planned for a future release.


1.  Run the upgrade wizard :guilabel:`Migrate file-based forms to database storage`
    in the :guilabel:`System > Upgrade` module. This wizard:

    *   Copies all file-based form definitions into the :sql:`form_definition`
        database table
    *   Updates all :sql:`tt_content` FlexForm references
        (`persistenceIdentifier`) to point to the new database records
    *   Deletes the original YAML files after successful migration

    If the :sql:`form_definition` table does not exist yet, run
    :guilabel:`System > Maintenance > Analyze Database` first.

    ..  important::

        The upgrade wizard only updates references in :sql:`tt_content`
        (CType `form_formframework`). If your installation stores form
        persistence identifiers in **custom database tables** or FlexForm
        fields outside :sql:`tt_content` (e.g., through third-party
        extensions), these references are **not updated automatically** and
        must be migrated manually.

2.  After verifying that all forms work correctly from the database, remove
    the :yaml:`allowedFileMounts` configuration from your YAML setup:

    **Before (deprecated):**

    ..  code-block:: yaml
        :caption: EXT:my_extension/Configuration/Yaml/FormSetup.yaml

        persistenceManager:
          allowedFileMounts:
            10: '1:/form_definitions/'

    **After:**

    Remove the :yaml:`allowedFileMounts` key entirely, or set it to an empty
    array to explicitly disable file mount storage:

    ..  code-block:: yaml
        :caption: EXT:my_extension/Configuration/Yaml/FormSetup.yaml

        persistenceManager:
          allowedFileMounts: []

3.  Optionally, if the upgrade wizard did not delete the YAML files (e.g.,
    due to file permission issues), delete them manually from the file system
    after confirming that the migration was successful.

Alternatively, the CLI command :bash:`form:definition:transfer` can be used
to transfer forms between storage types:

..  code-block:: bash

    # Transfer all file mount forms to database
    bin/typo3 form:definition:transfer --source=filemount --target=database

    # Move (transfer + delete source) in one step
    bin/typo3 form:definition:transfer --source=filemount --target=database --move

    # Preview without changes
    bin/typo3 form:definition:transfer --source=filemount --target=database --dry-run

..  index:: YAML, NotScanned, ext:form
