.. include:: /Includes.rst.txt

.. _module-db-check-Manage-Reference-Index:

============================================================
Manage Reference Index
============================================================

Users with administrator rights can find this module at
:guilabel:`System > DB Check > Manage Reference Index`.

In TYPO3 installations with a small number of records this module can be used
to :ref:`check or update the reference index <t3upgrade:update_reference_index>`.

On TYPO3 installations with a large number of records and many relations between
those the maximum run time of PHP will be reached and the scripts therefore
fail. It is recommended to run the commands from the command line then. This
module outputs the commands with absolute paths to update or check the the
reference from the command line.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Manage_Reference_Index.rst.txt
