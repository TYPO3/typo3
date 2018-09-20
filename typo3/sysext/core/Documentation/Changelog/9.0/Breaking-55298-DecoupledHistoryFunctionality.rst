.. include:: ../../Includes.txt

======================================================
Breaking: #55298 - Decoupled sys_history functionality
======================================================

See :issue:`55298`

Description
===========

Tracking of record changes within the TYPO3 Backend is now handled via the database table `sys_history` only,
the connection towards `sys_log` has been removed - at the same time, the backend view for showing the history
of a database record has been updated.


Database-related changes
------------------------
Changes of database records within DataHandler are now always tracked regardless of enabled logging within DataHandler.
It is not possible to disable this functionality by design (e.g. for bulk-inserts), otherwise the history of a database
record would not be complete.

DataHandler now tracks inserts/deletes/undelete entries into `sys_history` as well. Previously this was only
stored within `sys_log` (where it is still logged, if logging is enabled).

Instead of having sys_history database entries that are referenced into sys_log contain all necessary data, all data
is now stored within sys_history. All additional payload data is stored as JSON and not as serialized array.

A PHP new class :php:`RecordHistory` store has been introduced to act as API layer for storing any activity (including
moving records).

BE-log module
-------------
Referencing history entries within the BE-Log module is now done reverse (sys_log has a reference to an existing
sys_history record, and not vice-versa), speeding up the module rendering. The following related PHP classes
have been removed which were previously needed for rendering within the BE-Log backend module:

* :php:`\TYPO3\CMS\Belog\Domain\Model\HistoryEntry`
* :php:`\TYPO3\CMS\Belog\Domain\Repository\HistoryEntryRepository`
* :php:`\TYPO3\CMS\Belog\ViewHelpers\HistoryEntryViewHelper`

History view
------------
The "highlight" functionality for selecting a specific change within the history module of the TYPO3 Backend
has been removed.

A clear separation of concerns has been introduced between :php:`ElementHistoryController`, which is the entry-point
for viewing changes of a record, and :php:`RecordHistory`. The latter is now the place for fetching the history
data and doing rollbacks, where the Controller class is responsible for evaluating display-related settings inside the
module, and for preparing and rendering the Fluid-based output.

The following public PHP methods have now been removed or made protected.

* :php:`TYPO3\CMS\Backend\History\RecordHistory->maxSteps` (see the added setMaxSteps() method)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->showDiff`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->showSubElements` (see the added setShowSubElements() method)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->showInsertDelete` (moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->element`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->lastSyslogId`
* [not scanned] :php:`TYPO3\CMS\Backend\History\RecordHistory->returnUrl`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->showMarked`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->main()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->toggleHighlight()`
* Method parameter of :php:`TYPO3\CMS\Backend\History\RecordHistory->performRollback()`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->displaySettings()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->displayHistory()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->displayMultipleDiff()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->renderDiff()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->generateTitle()` (logic moved into controller)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->linkPage()` (logic moved into view)
* :php:`TYPO3\CMS\Backend\History\RecordHistory->removeFilefields()`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->resolveElement()`
* :php:`TYPO3\CMS\Backend\History\RecordHistory->resolveShUid()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController->content`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController->doc`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController->main()`

Impact
======

Calling any of the PHP methods will result in a fatal PHP error. Getting or setting any of the PHP properties
will trigger a PHP warning.

Using the affected database tables directly will produce unexpected results than before.


Affected Installations
======================

Any installation using the record history, or extensions extending sys_history.

Migration
=========

An upgrade wizard to separate existing history data from `sys_log` can be found within the Install Tool.

The install tool also checks for existing extensions making use of the dropped and changed PHP code.

.. index:: Database, PHP-API, Backend, PartiallyScanned
