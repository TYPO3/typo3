
.. include:: ../../Includes.txt

========================================================
Feature: #52131 - Hook for end of PageRepository->init()
========================================================

See :issue:`52131`

Description
===========

A new hook at the very end of the `PageRepository->init()` has been introduced.
This function allows manipulation of where clause in order to modify select queries that involve visibility of pages.

Register the hook as follows:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Page\PageRepository::class]['init']

The hook class must implement the interface :code:`\TYPO3\CMS\Frontend\Page\PageRepositoryInitHookInterface`.
