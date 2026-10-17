..  include:: /Includes.rst.txt

..  _feature-109862-1792231200:

==============================================================
Feature: #109862 - stdWrap for TypoScript config.headerComment
==============================================================

See :issue:`109862`

Description
===========

The TypoScript property :typoscript:`config.headerComment` now supports
:typoscript:`stdWrap`. The value is evaluated in the context of the current
page record before it is added as comment to the generated HTML source.

Only the comment endings :html:`-->` and :html:`--!>` are escaped in the
evaluated value; other characters remain unchanged. This prevents dynamic
values from breaking out of the HTML comment and injecting markup.

Example:

..  code-block:: typoscript

    config.headerComment = (c) 2011-{date:Y} Example Inc.
    config.headerComment.insertData = 1

Output:

..  code-block:: html

    <!--
        (c) 2011-2026 Example Inc.
    -->

Impact
======

Integrators can build the header comment dynamically, for example with
:typoscript:`insertData`, :typoscript:`data`, :typoscript:`wrap` or
:typoscript:`if`. Existing static values are rendered unchanged, except
that the comment endings :html:`-->` and :html:`--!>` are now escaped.

As the comment is part of the cacheable page content, dynamic values are
evaluated once per page cache entry.

..  index:: Frontend, TypoScript, ext:frontend
