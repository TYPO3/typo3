.. include:: ../../Includes.txt

=============================================================================
Deprecation: #86439 - Mark several methods within TemplateService as internal
=============================================================================

See :issue:`86439`

Description
===========

Some minor changes have been made with :php:`\TYPO3\CMS\Core\TypoScript\TemplateService` in order
to continue cleaning up the code.

The following methods have been marked as protected:

- :php:`prependStaticExtra()`
- :php:`versionOL()`
- :php:`processIncludes()`
- :php:`mergeConstantsFromPageTSconfig()`
- :php:`flattenSetup()`
- :php:`substituteConstants()`


Impact
======

Calling the methods in a public context will now trigger a PHP deprecation message.


Affected Installations
======================

TYPO3 installations with custom extensions working with the :php:`TemplateService` class.

Migration
=========

Avoid using the methods, and re-implement the functionality on your own, if necessary.

.. index:: Backend, NotScanned, ext:core
