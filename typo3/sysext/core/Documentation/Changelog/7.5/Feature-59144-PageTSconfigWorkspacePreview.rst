
.. include:: ../../Includes.txt

==================================================================
Feature: #59144 - Previewing workspace records using Page TSconfig
==================================================================

See :issue:`59144`

Description
===========

Per default TYPO3 only creates preview links for the tables tt_content, pages
and pages_language_overlay. To avoid utilizing a hook for each table, creating
preview links can be triggered using Page TSconfig.

.. code-block:: TypoScript

	# Using page 123 for previewing workspaces records (in general)
	options.workspaces.previewPageId = 123

	# Using the pid field of each record for previewing (in general)
	options.workspaces.previewPageId = field:pid

	# Using page 123 for previewing workspaces records (for table tx_myext_table)
	options.workspaces.previewPageId.tx_myext_table = 123

	# Using the pid field of each record for previewing (or table tx_myext_table)
	options.workspaces.previewPageId.tx_myext_table = field:pid
