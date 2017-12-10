.. include:: ../../Includes.txt

================================================================
Important: #82230 - Updates to the Fluid template engine library
================================================================

See :issue:`82230`

Description
===========

This article describes the changes that have been added to the Fluid template engine which is used in TYPO3.

* Bug fix to evaluate negative numbers in conditions the same way PHP does
* Bug fix for :php:`getLayoutPathAndFilename` on :php:`TemplatePaths` when property was manually set using setter
* Bug fix to avoid issues with malformed cache identifiers
* Bug fix to avoid attempting :php:`htmlspecialchars()` on any values that are not string-compatible
* Bug fix for adding namespaces to list of ignored namespaces
* Bug fix to make casting of ints/floats consistent in attribute values and in array values
* Bug fix to make internal cache of resolved ViewHelpers non-static to make sure it flushes between contexts
* Bug fix for recursive file resolving
* Performance bug fix to avoid loading compiled template classes that are already loaded
* Performance bug fix to make :html:`f:render` static callable from compiled templates
* Performance bug fix to improve performance of uncompilable templates
* New feature: support for :php:`hasMyProperty()` as alternative to :php:`isMyProperty()` when using :html:`{object.myProperty}`
* New feature: :php:`ParserRuntimeOnly` ViewHelper trait to use when ViewHelper only has functionality during parsing
* New feature: :php:`ignoreEmptyAttributes()` added to :php:`TagBuilder`, can be called in tag based ViewHelpers to skip
  rendering of any attributes that evaluate to an empty string
* New feature: support for `AND` and `OR` as alternatives to `&&` and `||` in boolean attributes like
  `condition` on :html:`f:if`
* New feature: support for custom error handling and a new implementation of a fault-tolerant error handler
* New feature: methods :php:`getAll` and :php:`addAll` added to :php:`ViewHelperVariableContainer` to allow getting and setting
  all variables in a scope
* New feature: concept of :php:`Renderable` introduced. A :php:`Renderable` is any class which implements
  :php:`RenderableInterface` - instances of such classes can be assigned as template variables and passed to :html:`f:render`

Full list can be found on https://github.com/TYPO3/Fluid/compare/2.3.4...2.4.0

.. index:: Fluid