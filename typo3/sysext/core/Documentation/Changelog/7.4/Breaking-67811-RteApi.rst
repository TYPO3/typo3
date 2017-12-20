
.. include:: ../../Includes.txt

==========================
Breaking: #67811 - Rte API
==========================

See :issue:`67811`

Description
===========

The RTE implementation was based on the main classes `\TYPO3\CMS\Backend\Rte\AbstractRte`,
`\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase` and `\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi`. These
three main API were removed or changed method signatures and internal method calls.

The functionality to render RTE standalone and out of a context of `FormEngine` was dropped.


Impact
======

Main API changes
----------------

* Method `TYPO3\CMS\Backend\Utility\BackendUtility::RTEgetObj()` is deprecated and no longer used.
  `FormEngine` now creates a `RichTextElement` with `NodeFactory` and `makeInstance()`, the
  created object is not a singleton but a prototype.

* With the deprecation of `RTEgetObj` method `transformContent` from `AbstractRte` has been inlined to
  `DataHandler`.

* Method `isAvailable` from `AbstractRte` has been dropped. Every valid browser and browser version
  for TYPO3 CMS 7 can render the default richtext editor. Custom checks may be implement via
  `NodeResolverInterface` in `FormEngine`.

* Property `RTE_errors` in `TYPO3\CMS\Core\Authentication\BackendUserAuthentication` has been dropped along
  with the `RTEgetObj()` deprecation.


RTE registration
----------------

* Different richtext implementations can no longer register in `$GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_reg']`.
  Instead, registration must be done in `FormEngine` via `NodeFactory` API, the method `drawRTE` has been dropped.

* Transformations are not available via `AbstractRte` anymore, hooks within `RteHtmlParser` can
  be used for custom transformations.


PHP classes
-----------

* `\TYPO3\CMS\Backend\Rte\AbstractRte` has been dropped.

* `\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase` has been dropped and its functionality was moved to
  `\TYPO3\CMS\Rtehtmlarea\Form\Element\RichtextElement`. All methods and properties except
  the main entry method `render()` used by `FormEngine` are protected.

* `\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi` has been refactored. Method `main()` receives
  a configuration array instead of an instance of the parent object. Some methods were dropped
  and are no longer called.


RTE Plugin Configuration
------------------------

* Parameter `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['anExtensionKey']['plugins']['aPluginName']['addIconsToSkin']`
  was dropped, plugin property `relativePathToSkin` is no longer evaluated.

* A couple of helper methods were added to `RteHtmlAreaApi`

* This API may get further changes in the future.


Affected Installations
======================

Extensions that extend one of the above mentioned extensions or API.


Migration
=========

Adapt the code using these methods.


.. index:: PHP-API, RTE, Backend
