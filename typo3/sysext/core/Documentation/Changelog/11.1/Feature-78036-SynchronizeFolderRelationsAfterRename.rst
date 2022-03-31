.. include:: /Includes.rst.txt

===========================================================
Feature: #78036 - Synchronize folder relations after rename
===========================================================

See :issue:`78036`

Description
===========

TYPO3 features the File module where editors and integrators can manage
all their media assets in a structured way. Certainly, one essential
task is to rename folders from time to time. Since folders are sometimes
referenced in other records, e.g. file collections or file mounts, these
relations did previously break after a folder was renamed, because the
reference index does not contain these relations.

Therefore, TYPO3 does now automatically synchronize all references of
a folder when it is renamed. This is done by registering event listeners
for the :php:`AfterFolderRenamedEvent` event. This event is dispatched as
soon as a folder was successfully renamed.

To be able to automatically replace the old folder name with the new one,
the mentioned event is extended for another property :php:`$sourceFolder`.
This property can be retrieved using the public :php:`getSourceFolder()`
method.

Note that the synchronization is always performed, as soon as a folder
was renamed. This does not only apply to the File module, but for every
:php:`ResourceFactory->renameFolder()` call, since the event is being
dispatched in this method.


Impact
======

All :sql:`sys_filemounts` and :sql:`sys_file_collection` records which
reference a renamed folder are now automatically synchronized.

The :php:`AfterFolderRenamedEvent` event now features a new property
:php:`$sourceFolder`. Extension authors can use this event to add
further synchronization for their custom records.

.. index:: Backend, FAL, ext:core
