.. include:: /Includes.rst.txt

.. _feature-97816-1656350667:

================================================
Feature: #97816 - TypoScript syntax improvements
================================================

See :issue:`97816`

Description
===========

TYPO3 v12 comes with a new TypoScript syntax parser that is more performant,
more robust and allows better tooling in the Backend.

The new parser is more forgiving in many places, this documentation lists
the new capabilities.

Also see :ref:`breaking-97816-1656350406`
for an overview of breaking syntax changes.

Impact
======

Improved comment parsing
------------------------

TypoScript comment detection had various nasty quirks with the old parser. The confusing
behavior did lead to many headaches in the past and not sticking to the weird parser
restrictions in comment parsing could easily lead to unexpected results, often ignoring
bigger sections of the subsequent TypoScript lines.

This has been relaxed heavily: Comment detection should almost always act as developers
and integrators would expect from a language. Especially the former obligation to place
a closing multiline comment (:typoscript:`*/`) on a single line to close the comment
section has been removed.

A couple of examples to clarify:

..  code-block:: typoscript

    foo # This is a comment to an invalid line

    foo < bar // This is a comment
    foo < bar /* This is a valid comment, too */

    foo > # Another valid comment

    foo := addToList(1) # Yes, a comment

    [foo = bar] # Much comment. Much wow.

    <INCLUDE_TYPOSCRIPT: source="..."> /* A comment */

    foo (
      # This is NOT a comment but part of the value assignment!
      bar = barValue
    ) # This is a comment

    foo = bar // This is NOT a comment but part of the value assignment!

@import in conditions
---------------------

Placing an :typoscript:`@import` keyword within a condition is now supported,
the example below works. Note that this obsoletes the clumsy :typoscript:`<INCLUDE_TYPOSCRIPT:`
syntax, and integrators are encouraged to fully switch to :typoscript:`@import`.

..  code-block:: typoscript

    [frontend.user.isLoggedIn]
      @import 'EXT:my_extension/Configuration/TypoScript/LoggedInUser.typoscript'
    [ELSE]
      @import 'EXT:my_extension/Configuration/TypoScript/NotLoggedInUser.typoscript'
    [END]

Scope restriction to file / snipped level
-----------------------------------------

The old TypoScript parser merged the entire TypoScript for a page into one big
chunk of text. The new parser does not do that anymore, but parses each included
snippet one-by-one. This automatically means state no longer leaks to subsequent
snippets. Missing closing brackets :typoscript:`}` in one file do not destroy block
integrity of a following include anymore. Same for conditions: A missing closing
condition block (:typoscript:`[END]` or :typoscript:`[GLOBALS]`) no longer leaks
to another file - a conditions ends at the end of a file or snippet.

Nesting conditions is partially supported
-----------------------------------------

Nesting conditions is partially possible with the new TypoScript parser, **if** the
conditions are in different files. As example, let's first sort what happens when
two conditions follow directly in one snippet:

..  code-block:: typoscript

    [frontend.user.isLoggedIn]
      @import 'EXT:my_extension/Configuration/TypoScript/LoggedInUser.typoscript'
    [applicationContext == "Development"]
      @import 'EXT:my_extension/Configuration/TypoScript/Development.typoscript'
    [END]

This always worked and did not change with the new parser: Opening a new condition
automatically closes the preceding one. In the example above, both conditions are
standalone: :file:`Development.typoscript` is included no matter if a user is
logged in or not.

But, and this in new, nesting conditions within different files is possible now.
In the example below, file :file:`LoggedInUserDevelopment.typoscript` is only
included if a user is logged in *and* the application is in development context.

..  code-block:: typoscript

    [frontend.user.isLoggedIn]
      @import 'EXT:my_extension/Configuration/TypoScript/LoggedInUser.typoscript'
    [END]

    # File LoggedInUser.typoscript:
    [applicationContext == "Development"]
      @import 'EXT:my_extension/Configuration/TypoScript/LoggedInUserDevelopment.typoscript'
    [END]

Irrelevant order of <INCLUDE_TYPOSCRIPT: tokens
-----------------------------------------------

The :typoscript:`<INCLUDE_TYPOSCRIPT:` keywords has the three properties
:typoscript:`source`, :typoscript:`condition` and :typoscript:`extensions`. They had to
be in a specific order with the old parser, but can be placed in arbitrary order now.

Further clarifications
----------------------

* The "reference" :typoscript:`=<` operator is not a direct language construct. The parser
  understands the syntax, but does not resolve it. Allowed :typoscript:`=<` operator are very
  limited: In general, it can *only* be used for Frontend Content Objects, typically like this:
  :typoscript:`tt_content.bullets =< lib.contentElement`. Another usage is referencing :typoscript:`lib.parseFunc`.
  Using the :typoscript:`=<` operator in these cases can have a performance advantage since it
  avoids an expensive copy operation that is done lazily if really needed. There are two methods
  that resolve this operator, namely :php:`ContentObjectRenderer->cObjGetSingle()` and
  :php:`ContentObjectRenderer->mergeTSRef()`.
  This also means: The :typoscript:`=<` operator is *not supported* in TypoScript constants,
  is only supported for specific elements in TypoScript setup, and is *not supported* in TSconfig.
  Also note the reference operator does not support "relative" copies like the "copy" operator supports
  with :typoscript:`20 < .10` and similar.

* The new parser has a minor change in behavior with the "copy" :typoscript:`<` operator on top-level.
  This shouldn't have huge impact in real life usage and is documented for completeness. Consider this
  example:

  ..  code-block:: typoscript

      lib.viewConfig {
        baz = bazValue
      }

      first = FLUIDTEMPLATE
      first < lib.viewConfig

  The situation is there that :typoscript:`lib.viewConfig` has no assigned value (just children). The
  target :typoscript:`first` however has value :typoscript:`FLUIDTEMPLATE`. The old TypoScript parser
  usually keeps the "target" value in such cases, but only if the TypoScript object is not on top level
  (:typoscript:`first` in contrast to :typoscript:`first.10` or similar). In the example above, value
  :typoscript:`FLUIDTEMPLATE` would vanish with the old parser, but is now kept with the new parser.

.. index:: Backend, Frontend, TSConfig, TypoScript, ext:core
