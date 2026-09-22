..  include:: /Includes.rst.txt

..  _breaking-110762-1789862500:

==================================================================================
Breaking: #110762 - File storages are configured in a file instead of the database
==================================================================================

See :issue:`110762`

Description
===========

A file storage answers the question where TYPO3 reads files from, with
which driver and with which credentials. That is infrastructure, decided when
an installation is set up and usually changed together with a deployment. It
is not content that editors produce. TYPO3 nevertheless stored it as a record
in the database table :sql:`sys_file_storage` since the File Abstraction Layer
was introduced with TYPO3 6.0, edited through FormEngine like a content
element. What was the pragmatic choice at that time turned into three problems
that the Core of today can solve:

*   **A storage definition is not for regular admins.** It decides which
    directories of the server and which remote buckets TYPO3 reads, writes
    and deletes in, and which credentials it uses to do so. Changing the base
    path of a local storage to a directory outside the web root, or pointing
    it at another site on the same server, exposes and destroys data that no
    backend permission was ever meant to cover. Still, any backend user with
    :php:`tables_modify` on the table could edit it, and the shipped editor
    permission preset granted exactly that. The definition now lives in
    :file:`config/system/`, next to :file:`settings.php`, and is only
    reachable for whoever maintains that directory: system maintainers,
    deployment tooling and upgrade code.
*   **A storage definition depends on the environment.** Credentials, bucket
    names and base paths legitimately differ between local, staging and
    production, and belong to the deployment, not to a database dump that is
    synchronized between them. A database row cannot reference an environment
    variable. The file can: :yaml:`%env()%` placeholders and :yaml:`imports`
    work as in the site configuration, so the definition is versioned,
    reviewed and deployed with the project, with the secrets kept outside.
*   **A storage definition starts at the project root.** The base path of a
    local storage was relative to the public web directory, so a storage
    outside the web root, the natural place for files that must never be
    served directly, could only be configured with an absolute path that ties
    the definition to one server. Relative base paths are now resolved
    against the project path, see :ref:`feature-110762-1789250000`:
    :yaml:`public/fileadmin/` for the default storage of a Composer based
    installation, :yaml:`storage/private/` for a directory next to
    :file:`config/` and :file:`var/`.

File storages are therefore configured in the file
:file:`config/system/file-storages.yaml`
(:file:`typo3conf/system/file-storages.yaml` in classic mode). The database
table :sql:`sys_file_storage`, its TCA and the record form are removed.
There is no backend form to edit the file.

The file is optional. Without it, TYPO3 assumes exactly the storage it
created automatically until now: one local storage with identifier :php:`1`
for the directory configured in
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`. Installations with
only this storage need no file at all.

..  code-block:: yaml
    :caption: config/system/file-storages.yaml

    storages:
      1:
        name: fileadmin
      2:
        name: 'Assets (S3)'
        driver: AmazonS3
        public: false
        configuration:
          bucket: '%env(S3_BUCKET)%'
          key: '%env(S3_KEY)%'
          secret: '%env(S3_SECRET)%'

The map key is the storage identifier, the former :sql:`uid`. It stays
numeric because every persisted reference, from :sql:`sys_file.storage` to
combined identifiers like :php:`1:/user_upload/`, uses it. Do not reuse the
identifier of a removed storage: file mounts, upload folders in TSconfig and
links may still point to it. Each storage takes the following options, all
optional:

..  list-table::
    :header-rows: 1

    *   -   Option
        -   Default
        -   Former column
    *   -   :yaml:`name`
        -   :yaml:`Storage <identifier>`
        -   :sql:`name`
    *   -   :yaml:`driver`
        -   :yaml:`Local`
        -   :sql:`driver`
    *   -   :yaml:`default`
        -   :yaml:`true` for a single storage, else :yaml:`false`
        -   :sql:`is_default`. Exactly one storage must be the default
            when several are configured.
    *   -   :yaml:`browsable`
        -   :yaml:`true`
        -   :sql:`is_browsable`
    *   -   :yaml:`public`
        -   :yaml:`true`
        -   :sql:`is_public`
    *   -   :yaml:`writable`
        -   :yaml:`true`
        -   :sql:`is_writable`
    *   -   :yaml:`online`
        -   :yaml:`true`
        -   :sql:`is_online`. A maintainer decision only, the runtime fuse of
            failing drivers lives in the :yaml:`hash` cache.
    *   -   :yaml:`autoExtractMetadata`
        -   :yaml:`true`
        -   :sql:`auto_extract_metadata`
    *   -   :yaml:`processingFolder`
        -   empty, meaning :file:`_processed_/` inside the storage
        -   :sql:`processingfolder`
    *   -   :yaml:`configuration`
        -   empty map
        -   :sql:`configuration`, the former FlexForm, as a plain map of
            driver options

The file is loaded through the YAML loader of TYPO3, so :yaml:`%env()%`
placeholders and :yaml:`imports` work. Placeholders are kept when TYPO3
writes the file itself. Storages defined in an imported file are changed in
that file; TYPO3 refuses to write them into the main file. Comments and YAML
anchors of the main file are not preserved when TYPO3 writes it.

The base path of a local storage is either absolute or relative to the
project path. The former option :yaml:`pathType: relative` is still
understood and resolves against the public path, new entries should not use
it.
When the option :yaml:`caseSensitive` is not set, the local driver detects the
behaviour of the file system below the base path.

Reading the file is tolerant, so that a single mistake does not stop every
file access of the site. Each problem is logged:

*   Unknown options of a storage are ignored, so a file written for a newer
    TYPO3 version still works.
*   A storage with an invalid value, or with an :yaml:`%env()%` placeholder
    whose environment variable is not set, is available as an offline
    storage.
*   If several storages are marked as default, the first of them is the
    default storage; if none is, the first storage is.

A YAML syntax error, a non-numeric identifier or a storage that is not a map
of options are errors that stop file access. TYPO3 itself validates
strictly when it writes the file.

The evaluated file is kept in the :yaml:`core` cache, keyed by the content of
the file. A changed file takes effect right away; after changing an imported
file or an environment variable used by a placeholder, flush the caches.

Migration of existing records
-----------------------------

The upgrade wizard "Move file storage records to
config/system/file-storages.yaml" writes the rows of :sql:`sys_file_storage`
into the file:

*   The FlexForm of each record becomes the :yaml:`configuration` map. Local
    base paths that were relative to the public directory are rewritten into
    paths relative to the project path. The :sql:`uid` stays the identifier.
*   Deleted records are skipped. If no record is marked as default, the first
    one becomes the default; if several are, the first of them stays the
    default.
*   An installation whose only record is the auto-created :file:`fileadmin`
    storage with identifier :php:`1` gets no file.
*   Records of a table that the database analyzer already renamed to
    :sql:`zzz_deleted_sys_file_storage` are migrated as well.
*   If the file defines storages already, for example because it was
    committed from another environment, the wizard does not change it. It
    lists the storages of the database that are missing in the file and stays
    open until they are added or removed from the database.
*   Driver configuration of other drivers than :yaml:`Local` is written as
    found, credentials included. The wizard points this out.

The file is part of the deployment, so the wizard should run where the
result can be committed, usually in the local development environment:

#.  Run the upgrade wizard.
#.  Replace credentials in the file with :yaml:`%env()%` placeholders and
    provide the variables in every environment.
#.  Commit the file and deploy it.
#.  Run the wizard on every further environment. It reports storages of that
    database that are missing in the deployed file.
#.  Remove the table with the database analyzer.

Production systems usually have no version control and are often
deployed read-only, while their database is the one that holds the storage
records that matter. Do not run the wizard there first. Instead, import a
recent copy of the production database into the local or staging
environment, run the wizard there, and commit and deploy the resulting file
before upgrading production. The wizard on production then only confirms
that the deployed file covers all storages. If the wizard has already
written the file on a production system, copy it from there into the
project before the next deployment replaces :file:`config/system/`.

The wizard is only offered when a file is needed. It is not shown when the
table does not exist (anymore), when it holds no active records, or when its
only record is the auto-created :file:`fileadmin` storage with identifier
:php:`1` and unchanged settings. Such installations, the majority, need no
file, no commit and no extra step in their deployment.

Impact
======

*   The database table :sql:`sys_file_storage`, its TCA, the record form and
    the labels of its columns are removed. Records of the table can no longer
    be created, edited, exported or referenced by the reference index. The
    table is dropped by the database analyzer after the upgrade wizard ran.
    Select fields of other tables that used
    :php:`foreign_table => 'sys_file_storage'` have to use the items processor
    function :php:`TcaItemsProcessorFunctions->populateFileStorages()` instead,
    which lists the storages the current backend user may access.
*   The permission presets no longer grant :php:`tables_select` and
    :php:`tables_modify` for the table. Backend users cannot change storages
    anymore; which storages a user sees is defined by file mounts alone.
*   :php:`\TYPO3\CMS\Core\Resource\StorageRepository` reads the file instead
    of the database. Its constructor takes
    :php:`\TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration` and
    :php:`\TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter`
    in addition to :php:`ConnectionPool` and :php:`FlexFormTools`.
    The new method :php:`addLocalStorage()` appends a local storage to the
    file. :php:`createLocalStorage()` keeps its signature, writes the file as
    well and is deprecated, see :ref:`deprecation-110762-1791443860`.
*   :php:`\TYPO3\CMS\Core\Resource\ResourceStorage` receives the normalized
    storage definition instead of a database row: the keys are
    :php:`identifier`, :php:`name`, :php:`driver`,
    :php:`default`, :php:`browsable`, :php:`public`, :php:`writable`,
    :php:`online`, :php:`autoExtractMetadata`, :php:`processingFolder` and
    :php:`configuration` (always an array). The same array is passed to
    :php:`\TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent`
    and returned by :php:`getRecord()`. Listeners that set the keys of the
    former row are mapped for one major version and trigger a deprecation,
    see :ref:`deprecation-110762-1791443860`. Listeners that read the former
    keys from :php:`getRecord()` get nothing and have to be adapted.
    :php:`getStorageRecord()` is deprecated, see
    :ref:`deprecation-110762-1789862501`.
*   :php:`ResourceStorage->markAsPermanentlyOffline()` no longer writes any
    record. It marks the storage as offline in the :yaml:`hash` cache until
    the caches are flushed. A storage that has to stay offline is configured
    with :yaml:`online: false`.
*   :php:`\TYPO3\CMS\Core\Resource\Driver\DriverRegistry->addDriversToTCA()`
    is removed and the :php:`flexFormDS` key of
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']` is
    no longer evaluated. A driver that still registers one triggers a
    deprecation log entry pointing to this document. Driver registration
    itself is unchanged: drivers keep registering a class, a short name and
    a label. The driver of a storage receives the :yaml:`configuration` map
    of the file as its configuration array, exactly as it received the
    parsed FlexForm before.
*   The FormEngine node :php:`userSysFileStorageIsPublic` and the class
    :php:`\TYPO3\CMS\Backend\Form\Element\UserSysFileStorageIsPublicElement`
    are removed, as is the method
    :php:`AbstractItemProvider->removeItemsByUserStorageRestriction()`.
*   Storage root nodes of the file tree now carry :php:`recordType`
    :php:`sys_file` and :php:`resourceType` :php:`storage`; their context
    menu offers the folder actions instead of the record actions of the
    former storage record.
*   The fallback storage with identifier :php:`0` is not part of the file and
    stays as it is; it is scheduled for removal separately.

Affected installations
======================

Every installation. Installations with only the auto-created
:file:`fileadmin` storage work without any change. Installations with
further storages have to run the upgrade wizard, after which the table can
be dropped. Extensions that read or write :sql:`sys_file_storage` records
directly, construct :php:`ResourceStorage` objects from database rows, read
the former keys in a listener of
:php:`BeforeResourceStorageInitializationEvent` or provide a
:php:`flexFormDS` for their driver are affected. The extension scanner
reports the removed and changed methods.

Migration
=========

Run the upgrade wizard as described above, then let the database analyzer
remove the table. Edit :file:`config/system/file-storages.yaml` by hand for
any further change, or use :php:`FileStorageConfigurationWriter` from
command line tools and set up code. Writing the file during regular web
requests is not supported: it fails on read-only file systems and diverges
between several web servers. Replace database access to
:sql:`sys_file_storage` with :php:`StorageRepository`, which offers
:php:`findAll()`, :php:`findByUid()`, :php:`getDefaultStorage()` and
:php:`findByStorageType()`. Driver extensions
delete the :php:`flexFormDS` entry and document their options for the
:yaml:`configuration` map instead.

..  index:: FAL, Database, TCA, PHP-API, PartiallyScanned, ext:core
