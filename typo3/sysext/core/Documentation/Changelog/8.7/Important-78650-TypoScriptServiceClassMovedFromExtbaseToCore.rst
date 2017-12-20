.. include:: ../../Includes.txt

======================================================================
Important: #78650 - TypoScriptService class moved from Extbase to Core
======================================================================

See :issue:`78650`

Description
===========

The PHP class :php:`TypoScriptService` has been moved to the core extension, as it has no direct link
to Extbase, and a lot of other system extensions are using the class.

The old class name is still registered as a class alias, so extensions can call the class via
the Extbase PHP namespace in TYPO3 v8 without any downsides.

.. index:: PHP-API, ext:extbase, TypoScript
