..  include:: /Includes.rst.txt

..  _breaking-108148-1763288349:

=====================================================
Breaking: #108148 - Strict Types in Fluid ViewHelpers
=====================================================

See :issue:`108148`

Description
===========

With Fluid 5, various changes have been made to use stricter types in the
context of ViewHelpers. This has consequences in three areas:

* Validation of arguments passed to ViewHelpers
  (see `#1194 on GitHub <https://github.com/TYPO3/Fluid/pull/1194>`_)
* Passing `null` values to ViewHelpers that generate a HTML tag, also
  known as tag-based ViewHelpers
  (see `#1233 on GitHub <https://github.com/TYPO3/Fluid/pull/1233>`_)
* Required type declarations for custom ViewHelper implementations
  (see `#1219 on GitHub <https://github.com/TYPO3/Fluid/pull/1219>`_)


Impact
======

ViewHelper argument validation
------------------------------

Fluid ViewHelpers now use stricter validation for their arguments by default.
The previous argument validation had numerous blind spots, which meant that
ViewHelper implementations couldn't really rely on the type requirements
specified in the ViewHelper's API. The new implementation performs a stricter
validation, which means that Fluid might reject arguments passed to ViewHelpers
that were previously considered *valid* (but which the ViewHelper in question
usually didn't know how to handle). The new implementation does however deal
with simple type conversions automatically, so that a ViewHelper that requires
a :php:`string` still can receive an :php:`int` as input.

For integrators, this change might reject certain ViewHelper arguments that
were previously *valid*, but not covered by the ViewHelper's specified API.

For developers of custom ViewHelpers, this change allows to get rid of custom
validation logic that was previously necessary due to Fluid's spotty
validation.

Note that the
`Argument ViewHelper <f:argument> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-argument>`_,
which can be used to define an API for a template, is **not** affected by this
change, as it already used the improved validation from the beginning.

Passing :php:`null` to tag-based ViewHelpers
--------------------------------------------

Previously, Fluid's :php:`TagBuilder` class, which is used to create HTML tags
in tag-based ViewHelpers, treated :php:`null` values as empty strings, leading
to an HTML tag with an empty HTML attribute. With Fluid 5, :php:`null` values lead
to the HTML attribute being omitted from the resulting HTML tag.

Example:

..  code-block:: html

    <f:form.textfield name="myTextBox" value="{variableThatMightBeNull}" />

If the variable is :php:`null` (the PHP value), Fluid 4 and below generated
the following output:

..  code-block:: html

    <input type="text" name="myTextBox" value="" />

Fluid 5 omits the :html:`value=""`:

..  code-block:: html

    <input type="text" name="myTextBox" />

In most cases, the impact of this change is non-existent. However, there are
some edge cases where this change is relevant. In TYPO3 Core, the :html:`<f:image>`
ViewHelper needed to be adjusted to always render the :html:`alt` attribute,
even if its internal value is :php:`null`, to match the previous output and
to produce valid HTML code.

TYPO3 Core ships with the following tag-based ViewHelpers:

* :html:`<f:media>` and :html:`<f:image>`
* :html:`<f:asset.css>` and :html:`<f:asset.script>`
* :html:`<f:form>` and :html:`<f:form.*>`
* :html:`<f:link.*>`, except for `<f:link.typolink>`, which uses TypoScript internally
* :html:`<f:be.link>`
* :html:`<be:link.*>`
* :html:`<be:thumbnail>`

Type declarations in ViewHelper classes
---------------------------------------

Fluid's `ViewHelperInterface` now requires proper return types for all ViewHelper
methods. Thus, custom ViewHelper implementations need to be adjusted accordingly.
This is backwards-compatible to previous TYPO3 versions.


Affected installations
======================

All installations need to verify that

* ViewHelpers aren't called with invalid argument types
* :php:`null` values passed to tag-based ViewHelpers don't lead to unexpected
  HTML output
* Custom ViewHelper implementations specify proper return types


Migration
=========

Custom ViewHelper implementations need to make sure that they declare
proper return types in the ViewHelper class to conform to Fluid 5's
interface changes, for example:

* `render()` must specify a return type other than `void`; Though a
  specific type is recommended, `mixed` can be used as well.
* `initializeArguments()` must specify `void` as return type

Note that properties in ViewHelper classes are **not** affected.
The following example doesn't need to be adjusted, no types can/should
be specified for these properties:

..  code-block:: php
    class MyViewHelper extends AbstractViewHelper
    {
        protected $escapeOutput = false;
        protected $escapeChildren = false;
    }

Unfortunately, the other changes concern runtime characteristics of
Fluid templates, as they depend on the concrete values of variables that
are passed to a template. Thus, it is not possible to scan for affected
templates automatically.

However, the majority of issues these changes in Fluid might uncover in
existing projects would have already been classified as a bug
(in the template or extension code) before this Fluid change, such as
passing an array to a ViewHelper that expects a string.

..  index:: Fluid, NotScanned, ext:fluid
