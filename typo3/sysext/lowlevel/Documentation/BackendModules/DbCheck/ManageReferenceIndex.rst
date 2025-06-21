.. include:: /Includes.rst.txt

.. _module-db-check-Manage-Reference-Index:

============================================================
Manage Reference Index
============================================================

The reference index in TYPO3 is the table `sys_refindex`.
(Related link: :ref:`Soft references <t3coreapi:soft-references>`).
The table contains all relations/cross correlations between datasets.
For example a content element has an image and a link.
These two references can be found in this table stored against this
unique data record (`tt_content` uid).

When you want to perform a TYPO3 update it is recommended to update these relations.
See `Update Reference Index <https://docs.typo3.org/permalink/t3coreapi:update-reference-index>`_.

To perform an update you can use the TYPO3 Console command shown in that section.

TYPO3 installations with a small number of records can use the module
:guilabel:`System > DB check` and use the :guilabel:`Manage Reference Index`
function.

On TYPO3 installations with a large number of records and many relations between
those the maximum run time of PHP will be reached and the scripts therefore
fail. It is recommended to run the commands from the command line. This
module outputs the commands with absolute paths to update or check the
reference from the command line.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Manage_Reference_Index.rst.txt
