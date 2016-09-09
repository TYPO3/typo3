
.. include:: ../../Includes.txt

=================================================
Deprecation: #70138 - Flex form language handling
=================================================

See :issue:`70138`

Description
===========

`TCA` flex fields had an own language handling that allowed to store field localization within
the flex data itself and not within the usual database driven overlay records. This was mainly
introduced for `TemplaVoila` and usually only used in this context.

The whole flex form specific language handling has been moved from core extensions to extension
`compatibility6` and will not be delivered with `TYPO3 CMS 7 LTS` anymore.

The following flex XML data structure keys have been marked as deprecated and are supported by `compatiblity6` only:

* `<meta><langDisable>`
* `<meta><langChildren>`
* `<meta><currentLangId>`


The following `PageTSConfig` options have been dropped and are ignored if `compatibility6` is not loaded:

* `TCEFORM.[tableName].[field].[dataStructureKey].langDisable`
* `TCEFORM.[tableName].[field].[dataStructureKey].langChildren`


The following `UserTSConfig` options have been dropped and are ignored if `compatibility6` is not loaded:

* `options.checkPageLanguageOverlay`


The following `LocalConfiguration` value has been marked as deprecated, will be removed by the install tool and
is set by `compatibility6` to `TRUE`:

* `$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase']`


The following method has been marked as deprecated and is cloned by `compatibility6` in an `XCLASS`:

* `TYPO3\CMS\Core\Configaration\FlexForm\FlexFormTools->getAvailableLanguages()`


The following property has been marked as deprecated:

* `TYPO3\CMS\Core\DataHandling\DataHandler->clear_flexFormData_vDEFbase`


Impact
======

If this feature is needed, the `compatibility6` extension must be loaded to keep compatibility
with older versions after upgrading from `TYPO3 CMS 6.2`. If the extension is not loaded,
flex form fields can no longer be localized on flex data level.


Affected Installations
======================

All multi language installations that use flex form with meta field `langDisable` not set to 1
in their data structure definition and that make active use of the flex localization feature.
This is the case if records with flex form fields show flex forms multiple times with different
language flags.


Migration
=========

Load extension `compatibility6` for a compatibility layer in `TYPO3 CMS 7`, or migrate affected
features to use the record based localization feature. The flex field based language handling
will most likely vanish with `TYPO3 CMS 8` altogether. In case the feature is needed for a
longer time the code from `compatibility6` could be used as a kick start for an implementation
within an own extension.

In case `compatibility6` is loaded, some core content elements may start showing flex field
language overlays since the `langDisable` meta definition has been removed from their data
structure XML. This can be suppressed with this `PageTSConfig` snippet:

.. code-block:: typoscript

	TCEFORM.tt_content.pi_flexform.table.langDisable = 1
	TCEFORM.tt_content.pi_flexform.login.langDisable = 1
	TCEFORM.tt_content.pi_flexform.media.langDisable = 1
