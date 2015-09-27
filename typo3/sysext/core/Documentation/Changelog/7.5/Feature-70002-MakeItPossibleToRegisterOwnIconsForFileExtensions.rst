============================================================================
Feature: #70002 - Make it possible to register own icons for file extensions
============================================================================

Description
===========

The IconRegistry has been extended with a mapping of file extensions.


Impact
======

It is now possible to register or overwrite the iconIdentifier for a file extension.

.. code-block:: php

	$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
	$iconRegistry->registerFileExtension('log', 'icon-identiifer-for-log-files');
