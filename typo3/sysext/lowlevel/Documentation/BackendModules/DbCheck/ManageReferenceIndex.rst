.. include:: /Includes.rst.txt

.. _module-db-check-Manage-Reference-Index:

============================================================
Manage Reference Index
============================================================

The reference index in TYPO3 is the table `sys_refindex`.
(Related link: :ref:`Soft references <t3coreapi:soft-references>`).
The table contains all relations/cross correlations between datasets. 
For example a content element has an image and a link. 
Then these two references can be found 
in this table stored against this unique data record (`tt_content` uid). 
When you want to perform a TYPO3 update it is recommended to backup these relations.
See :ref:`Update Reference Index <t3upgrade:update_reference_index>`.

To perform an update you can use the TYPO3 Console command shown here
:doc:`With command line (recommended) <t3upgrade:Major/PreupgradeTasks/Index#with-command-line-recommended>`.
Or, users with administrator rights can find this module at
:guilabel:`System > DB Check > Manage Reference Index` and perform an update of the 
reference index there.

In TYPO3 installations with a small number of records this module can be used
to :ref:`check or update the reference index <t3upgrade:update_reference_index>`.

On TYPO3 installations with a large number of records and many relations between
those the maximum run time of PHP will be reached and the scripts therefore
fail. It is recommended to run the commands from the command line then. This
module outputs the commands with absolute paths to update or check the
reference from the command line.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Manage_Reference_Index.rst.txt
