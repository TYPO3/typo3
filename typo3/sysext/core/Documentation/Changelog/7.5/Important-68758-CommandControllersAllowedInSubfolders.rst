
.. include:: ../../Includes.txt

=============================================================
Important: #68758 - Command controllers allowed in subfolders
=============================================================

See :issue:`68758`

Description
===========

Extbase command controllers can now reside in arbitary subfolders within the
`Command` folder. This allows for better grouping and namespacing of commands.

Given a command controller resides in `my_ext/Classes/Command/Hello/WorldCommandController.php`,
it can now be invoked like this:

.. code-block:: shell

	typo3/cli_dispatch.sh extbase my_ext:hello:world <arguments>
