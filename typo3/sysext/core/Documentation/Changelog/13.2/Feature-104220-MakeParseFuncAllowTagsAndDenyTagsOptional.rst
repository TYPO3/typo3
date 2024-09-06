.. include:: /Includes.rst.txt

.. _feature-104220-1719409311:

=================================================================
Feature: #104220 - Make parseFunc allowTags and denyTags optional
=================================================================

See :issue:`104220`

Description
===========

Defining the TypoScript properties :typoscript:`allowTags` or
:typoscript:`denyTags` for the HTML processing via
:typoscript:`stdWrap.parseFunc` is now optional.

Besides that, it is now possible to use :typoscript:`allowTags = *`.


Impact
======

By omitting :typoscript:`allowTags` or :typoscript:`denyTags`, the
corresponding rendering instructions can be simplified.
Security aspects are considered automatically by the HTML sanitizer,
unless :typoscript:`htmlSanitize` is disabled explicitly.

Examples
--------

.. code-block:: typoscript

    10 = TEXT
    10.value = <p><em>Example</em> <u>underlined</u> text</p>
    10.parseFunc = 1
    10.parseFunc {
      allowTags = *
      denyTags = u
    }

The example above allows any tag, except :html:`<u>` which will be encoded.

.. code-block:: typoscript

    10 = TEXT
    10.value = <p><em>Example</em> <u>underlined</u> text</p>
    10.parseFunc = 1
    10.parseFunc {
      allowTags = u
    }

The example above only allows :html:`<u>` and encodes any other tag.

.. code-block:: typoscript

    10 = TEXT
    10.value = <p><em>Example</em> <u>underlined</u> text</p>
    10.parseFunc = 1
    10.parseFunc {
      allowTags = *
      denyTags = *
    }

The example above allows all tags, the new :typoscript:`allowTags = *`
takes precedence over :typoscript:`denyTags = *`.

.. index:: Frontend, TypoScript, ext:core
