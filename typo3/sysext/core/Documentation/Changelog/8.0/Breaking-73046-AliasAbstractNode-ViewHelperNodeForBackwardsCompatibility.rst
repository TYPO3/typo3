
.. include:: /Includes.rst.txt

===================================================================================
Breaking: #73046 - Alias AbstractNode -> ViewHelperNode for backwards compatibility
===================================================================================

See :issue:`73046`

Description
===========

`ViewHelperInterface::compile()` in standalone Fluid now requires an actual `ViewHelperNode` instead of `AbstractNode` as it was before.
The reason for changing this should be fairly obvious. In order to preserve signature compatibility an alias is put in place.

The alias is created so that `TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode` becomes an alias of `TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode`.
This is obviously less than ideal but solves the problem immediately and prioritises not breaking the public API by breaking the non-public API.


Impact
======

This signature change means that ViewHelpers which implement a custom `compile()` method must update their signature (does not apply to ViewHelpers implementing
only renderStatic). Two ViewHelpers in TYPO3 CMS Fluid were migrated as part of the standalone Fluid
merge - but third-party ViewHelpers would require either migration or an alias.

* Overridden TemplateParsers (before standalone Fluid merge) might not work, depending on the nature of overrides (conditions
  checking class names may fail)
* Custom Nodes (implemented via an overridden TemplateParser, before standalone Fluid) would break either in function or form
  (incompatible constructor signatures on PHP7 at least)


Affected Installations
======================

Any TYPO3 site using an extension which replaces or directly interacts with TemplateParser or TemplateCompiler from Fluid. Only
known affected community extension is at this point EXT:builder, specifically the template validation/analysis feature.


Migration
=========

Change any reference to classes in `TYPO3\CMS\Fluid\Core\TemplateParser` to `TYPO3Fluid\Fluid\Core\TemplateParser`.

.. index:: PHP-API, Fluid
