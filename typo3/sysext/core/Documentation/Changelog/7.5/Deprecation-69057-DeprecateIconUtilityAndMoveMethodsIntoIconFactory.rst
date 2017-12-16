
.. include:: ../../Includes.txt

=============================================================================
Deprecation: #69057 - Deprecate IconUtility and move methods into IconFactory
=============================================================================

See :issue:`69057`

Description
===========

The `IconUtility` class will be removed with TYPO3 CMS 8. All public methods of this class have been marked as deprecated:

* `IconUtility::skinImg()`
* `IconUtility::getIcon()`
* `IconUtility::getSpriteIcon()`
* `IconUtility::getSpriteIconForFile()`
* `IconUtility::getSpriteIconForRecord()`
* `IconUtility::getSpriteIconForResource()`
* `IconUtility::getSpriteIconClasses()`

The PageTSConfig setting `mod.wizards.newContentElement.wizardItems.*.elements.*.icon` also has been marked as deprecated.

The `IconUtilityOverrideResourceIconHookInterface` interface will be removed with TYPO3 CMS 8.


Impact
======

Any usage of these methods will trigger a deprecation log entry.


Affected Installations
======================

Extensions that call these PHP methods directly.
Extensions that register own content elements with an icon for the new content element wizard.


Migration
=========

Use the new `IconFactory` class instead of `IconUtility`.

For content element wizard register your icon in `IconRegistry::registerIcon()` and use the new setting:
`mod.wizards.newContentElement.wizardItems.*.elements.*.iconIdentifier`
