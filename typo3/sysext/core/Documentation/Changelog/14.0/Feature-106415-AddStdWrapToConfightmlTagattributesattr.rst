..  include:: /Includes.rst.txt

..  _feature-106415-1742660981:

==================================================================
Feature: #106415 - Add stdWrap to config.htmlTag.attributes.[attr]
==================================================================

See :issue:`106415`

Description
===========

Each attribute within the TypoScript option
:typoscript:`config.htmlTag.attributes.[attr]` now supports all
:typoscript:`stdWrap` properties.

This option controls the attributes of the single :html:`<html>`
element of a rendered page.

Impact
======

It is now possible to use :typoscript:`userFunc`,
:typoscript:`override`, or :typoscript:`getData` within TypoScript:

..  code-block:: typoscript
    :caption: Using override in TypoScript

    config.htmlTag.attributes{
        my-attribute = 123
        my-attribute.override = 456
    }

..  code-block:: typoscript
    :caption: Using userFunc in TypoScript

    config.htmlTag.attributes {
        my-attribute = 123
        my-attribute.userFunc = MyVendor\\MyExtension\\HtmlTagEnhancer->overrideMyAttribute
    }

..  index:: TypoScript, ext:frontend
