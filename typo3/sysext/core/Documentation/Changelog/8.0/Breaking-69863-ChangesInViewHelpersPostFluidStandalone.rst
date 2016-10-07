
.. include:: ../../Includes.txt

===============================================================
Breaking: #69863 - Changes in ViewHelpers post Standalone-Fluid
===============================================================

See :issue:`69863`

Description
===========

The following ViewHelpers have changed behaviours in Fluid:

* The `f:case` ViewHelper argument `default` has been marked as deprecated. To indicate which case is the default, use `f:defaultCase`.
* Tag content of `f:render` is no longer ignored and will be output if called with `optional="1"`.
* Arguments `iconOnly` and `styleAttributes` have been removed from `f:be.buttons.csh`.
* Argument `alternateBackgroundColors` has been removed from `f:be.tableList`.
* ViewHelpers no longer use the `escapingInterceptorEnabled` property but instead use `escapeChildren` and `escapeOutput` to control each behavior.
* All ViewHelpers no longer initialize standard arguments ("additionalArguments" and "data") in `__construct()`, but instead do this in `initializeArguments()`. If you override this method, you need to make sure you include a call to `parent::initializeArguments()` in your subclass.

The following ViewHelper classes are now only found in namespace `TYPO3Fluid\Fluid\ViewHelpers` and no longer exist in `TYPO3\CMS\Fluid\ViewHelpers`:

* `TYPO3\CMS\Fluid\ViewHelpers\AliasViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper` (present as deprecated alias until final removal)
* `TYPO3\CMS\Fluid\ViewHelpers\CommentViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\CycleViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\IfViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\ThenViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\ElseViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\LayoutViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\SectionViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\SpacelessViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\Format\CdataViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\Format\PrintfViewHelper`
* `TYPO3\CMS\Fluid\ViewHelpers\Format\RawViewHelper`

Impact
======

* A warning about use of an unregistered argument `default` will be displayed if templates contain `f:case` with `default` argument.
* Unexpected template output will be output if templates are rendered which contain `<f:render partial/section optional="1">will be output now</f:render>`.
* A warning about use of an unregistered argument `iconOnly` and/or `styleAttributes` will be displayed if templates contain `f:be.buttons.csh` with either argument.
* A warning about use of an unregistered argument `alternateBackgroundColors` will be displayed if templates contain `f:be.tableList` with that argument.
* Any third-party ViewHelpers subclassing any of the classes listed above must change parent class to new namespace.
* Any third-party ViewHelpers using `escapingInterceptorEnabled` property to disable escaping.


Affected Installations
======================

Any TYPO3 instance that uses a template which contains:

* An `f:case` with `default` argument.
* An `f:render` with `optional="1"` and having content in the `<f:render>` tag.
* An `f:be.buttons.csh` with either `iconOnly` or `styleAttributes` (value irrelevant).
* An `f:be.tableList` with `alternateBackgroundColors` (value irrelevant).
* Any third-party ViewHelper which subclasses any of the classes listed above.
* Any third-party ViewHelper which uses `escapingInterceptorEnabled` property to disable escaping.


Migration
=========

* Remove the `default` option and change `f:case` to `f:defaultCase` for that case.
* Remove the tag contents of `f:render`.
* Remove arguments `iconOnly` and `styleAttributes` from `f:be.buttons.csh` where found.
* Remove argument `alternateBackgroundColors` from `f:be.tableList` where found.
* Update namespace of parent class in ViewHelpers subclassing any of the classes listed above.
* Update ViewHelper class to use `escapeChildren` and/or `escapeOutput` depending on desired behavior.

.. index:: Fluid
