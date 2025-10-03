..  include:: /Includes.rst.txt

..  _deleting-a-task:

===============
Deleting a task
===============

When choosing to delete a task, a pop-up window will appear requesting
confirmation.

Deleted tasks can be recovered in the module :guilabel:`Web > Recycler` if
installed or by unsetting the deleted flag in the database.

..  _deleting-a-task-restoration:

Restoring a deleted scheduler task
==================================

..  versionchanged:: 14.0
    Previously removed tasks can be restored via recycler.

If the system extension :composer:`typo3/cms-recycler` is installed, go to
module :guilabel:`Web > Recycler`.

Choose the page root (page 0) in the page tree, all scheduler tasks are stored
here.

You can use the field "Type" to filter for "Scheduler task" only.

..  figure:: /Images/RestoreTask.png
    :alt: Screenshot of the TYPO3 Backend Recycler module on the root page 0 with type "Scheduler task" selected

    A task can be restored or permanently deleted in the recycler module
