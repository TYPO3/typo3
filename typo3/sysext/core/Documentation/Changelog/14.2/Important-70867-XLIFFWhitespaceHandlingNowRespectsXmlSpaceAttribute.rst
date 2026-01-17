..  include:: /Includes.rst.txt

..  _important-70867-1737100000:

========================================================================
Important: #70867 - XLIFF whitespace handling now respects xml:space
========================================================================

See :issue:`70867`

Description
===========

TYPO3's XLIFF parser now properly respects the :xml:`xml:space` attribute
according to the XML specification (https://www.w3.org/TR/xml/#sec-white-space).

This affects how whitespace (spaces, tabs, newlines) in translation strings
are handled.

Without :xml:`xml:space="preserve"` (default behavior):

Multiple consecutive whitespace characters (spaces, tabs, newlines) are
collapsed into a single space, and leading/trailing whitespace is trimmed.

Example XLIFF source:

..  code-block:: xml

    <trans-unit id="my.label">
      <source>This is a
        multi-line
        string.</source>
    </trans-unit>

Before: The string contained literal newlines and indentation.
After: The string becomes :php:`"This is a multi-line string."`

With :xml:`xml:space="preserve"`:

Whitespace is kept exactly as written in the XLIFF file.

..  code-block:: xml

    <trans-unit id="my.label" xml:space="preserve">
      <source>This is a
        multi-line
        string.</source>
    </trans-unit>

The string remains :php:`"This is a\n        multi-line\n        string."`

Impact
======

Translation strings that previously contained unintended whitespace (from
formatting in the XLIFF file) will now display correctly without extra
spaces or line breaks.

If you intentionally need preserved whitespace in a translation string,
add the :xml:`xml:space="preserve"` attribute to the :xml:`<trans-unit>`
element (XLIFF 1.2) or :xml:`<segment>` element (XLIFF 2.0).

This change affects both XLIFF 1.2 and XLIFF 2.0/2.1 formats.

.. index:: Backend, Localization, XLF
