
.. include:: ../../Includes.txt

==========================================================================
Breaking: #69057 - Deprecate IconUtility and move methods into IconFactory
==========================================================================

See :issue:`69057`

Description
===========

While refactoring the `IconUtility` to the new `IconFactory` class, several methods have been marked as deprecated.
In some cases parameters of the old `IconUtility` methods are not used anymore.
The following list describes the possible breaking changes.

The second parameter `$options` of method `IconUtility::getSpriteIconForFile()` is not used anymore.
The third parameter `$options` of method `IconUtility::getSpriteIconForRecord()` is not used anymore.

The `IconUtility` signals `buildSpriteIconClasses` and `buildSpriteHtmlIconTag` have been dropped and will not be emitted anymore.
The `IconUtility` hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay']` has been dropped and will not be called anymore.


Impact
======

Extensions could break if the methods, signals or hooks above are used.


Affected Installations
======================

Extensions that call the methods with the `$options` parameter or make use of the signals and hook.


Migration
=========

Make use of the new `IconFactory` class.
