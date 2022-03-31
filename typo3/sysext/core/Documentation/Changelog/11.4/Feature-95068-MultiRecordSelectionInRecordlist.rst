.. include:: /Includes.rst.txt

======================================================
Feature: #95068 - Multi record selection in recordlist
======================================================

See :issue:`95068`

Description
===========

With :issue:`94906` the multi record selection component has been
introduced to TYPO3. Next to proper keyboard support, it enables
editors to easily select multiple records with a couple of convenience
methods, such as "select all", "toggle selection" or "select range".

This component has now also been added to the :guilabel:`Web > List`
module. Therefore the "clipboard" column has been removed. All clipboard
actions, e.g. "paste content", have been moved to the multi record selection
action bar in the table header. Because the component is not bound to the
clipboard functionality, editors are now able to perform actions, such
as editing or deleting multiple records, without moving them to the
clipboard first.

As already known from other modules, the available actions (edit, copy,
delete, etc.) are shown in the table header, as soon as one record is
selected. An exception is the "Edit this field" button, displayed next
to each column header, which represents a real database field (only in
the "single table view"). It can be used to edit a single field for all
displayed records. This button now also respects the current selection,
making it possible to edit a single field for only a specific selection
of records.

Manipulating the displayed actions in the table header is still possible
using the `\TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent`
PSR-14 event.

In case you are still using the TSconfig option
:typoscript:`showClipControlPanelsDespiteOfCMlayers`, which is rather
unlikely as it wasn't properly respected in latest versions at all,
you should remove it now, since it is no longer evaluated due to
the removal of the clipboard column.


Impact
======

Editing multiple records in the :guilabel:`Web > List` module has been
improved and is now no longer bound to the clipboard functionality.

Setting the TSconfig option :typoscript:`showClipControlPanelsDespiteOfCMlayers`
has no effect anymore.

.. index:: Backend, ext:backend
