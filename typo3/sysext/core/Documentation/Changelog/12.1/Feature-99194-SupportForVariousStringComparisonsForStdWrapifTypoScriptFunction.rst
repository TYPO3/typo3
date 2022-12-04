.. include:: /Includes.rst.txt

.. _feature-99194-1669413174:

===========================================================================================
Feature: #99194 - Support for various string comparisons for stdWrap.if TypoScript function
===========================================================================================

See :issue:`99194`

Description
===========

The TypoScript function :typoscript:`if.` now supports several new sub-properties
for comparing a value (provided via :typoscript:`if.value = ...`),
if it contains a certain part of a string, or starts with a certain
part, or ends with a certain part. All of these properties also work with
the :typoscript:`if.negate` flag.

The new TypoScript properties for `if.` are called:

* :typoscript:`if.contains`
* :typoscript:`if.startsWith`
* :typoscript:`if.endsWith`

All of the mentioned properties can be assigned a static value, and support
:typoscript:`stdWrap` as their sub-properties.


Impact
======

As :typoscript:`if.` is available in most content objects, :typoscript:`stdWrap` or
data processors, it can now be used more exhaustive.

Example for :typoscript:`ìf.contains`:

..  code-block:: typoscript

    # Add a span tag before the page title if the page title
    # contains the string "media"
    page.10 = TEXT
    page.10.data = page:title
    page.10.htmlSpecialChars = 1
    page.10.prepend = TEXT
    page.10.prepend.value = <span class="icon-video"></span>
    page.10.prepend.if.value.data = page:title
    page.10.prepend.if.contains = Media
    page.10.outerWrap = <h1>|</h1>

Example for :typoscript:`ìf.endsWith`:

..  code-block:: typoscript

    # Add a footer note, if the page author ends with "Kott"
    page.100 = TEXT
    page.100.value = This is an article from Benji
    page.100.htmlSpecialChars = 1
    page.100.if.value.data = page:author
    page.100.if.endsWith = Kott
    page.100.wrap = <footer>|</footer>

Example for :typoscript:`ìf.startsWith`:

..  code-block:: typoscript

    page.10 = TEXT
    page.10.value = Your editor added the magic word in the header field
    page.10.htmlSpecialChars = 1
    page.10.if.value.data = DB:tt_content:1234:header
    page.10.if.startsWith = Bazinga

.. index:: TypoScript, ext:frontend
