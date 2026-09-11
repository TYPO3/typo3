..  include:: /Includes.rst.txt

..  _important-110684-1789168000:

====================================================================================
Important: #110684 - File storage select fields no longer reference sys_file_storage
====================================================================================

See :issue:`110684`

Description
===========

The TCA columns :sql:`sys_file.storage` and
:sql:`tx_scheduler_task.file_storage` are :php:`type => 'select'` fields that
resolved their items through
:php:`foreign_table => 'sys_file_storage'`. Both now receive their items from
the new items processor function
:php:`\TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions->populateFileStorages()`,
which lists the storages the current backend user has access to via
:php:`BackendUserAuthentication->getFileStorages()`. As before, non-admin users
only see the storages they have a file mount in, and admin users see all
storages.

The database columns are declared explicitly in :file:`ext_tables.sql` as
unsigned integers and keep their previous definition. As both fields no longer
define a relation, the schema API reports them as
:php:`\TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType` instead of
:php:`\TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType`, and the Record API
returns the storage uid instead of a resolved storage record.

This has the following side effects:

*   The reference index no longer records a relation from :sql:`sys_file` and
    :sql:`tx_scheduler_task` records to their storage record. Existing
    relations are removed by updating the reference index, for example with
    :bash:`vendor/bin/typo3 referenceindex:update`. Afterwards, the reference
    count and the delete warning of a :sql:`sys_file_storage` record no longer
    include files and scheduler tasks.
*   Labels of both fields, for example in the record history, are resolved
    through the items processor function. For storages the current backend user
    has no access to, and without a backend user, the storage uid is shown
    instead of the storage name.
*   :bash:`cleanup:localprocessedfiles --all` now removes the processed file
    records of local storages and of the fallback storage only. Records of
    remote storages were previously included by accident, and installations
    with several local storages no longer see duplicate deletion attempts.

The read-only file mounts resolved from the user TSconfig option
:typoscript:`options.folderTree.altElementBrowserMountPoints` use the new
internal method :php:`StorageRepository->getDefaultStorageUid()` instead of a
direct database query. If no storage exists apart from deleted ones, the default
local storage is created, as for any other storage lookup, instead of an
exception being thrown.

Extensions with own select fields that use
:php:`foreign_table => 'sys_file_storage'` keep working, including the storage
based item restriction for non-admin users. Extensions that override the TCA of
the two core fields, for example by adding a :php:`foreign_table_where` clause,
have to adapt, as those options no longer have an effect on an items processor
function.

..  index:: TCA, FAL, ext:core, ext:scheduler, ext:lowlevel
