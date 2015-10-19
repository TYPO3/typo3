================================================================================
Important: #70849 - Make search levels in live search and list search consistent
================================================================================

Description
===========

In order to make the searchlevel handling consistent between live and list search a new PageTS option has been added.

.. code-block:: typoscript

	mod.web_list.searchLevel.items {
		-1 = EXT:lang/locallang_core.xlf:labels.searchLevel.infinite
		0 = EXT:lang/locallang_core.xlf:labels.searchLevel.0
		1 = EXT:lang/locallang_core.xlf:labels.searchLevel.1
		2 = EXT:lang/locallang_core.xlf:labels.searchLevel.2
		3 = EXT:lang/locallang_core.xlf:labels.searchLevel.3
		4 = EXT:lang/locallang_core.xlf:labels.searchLevel.4
	}

This makes it possible to add custom search level entries.

