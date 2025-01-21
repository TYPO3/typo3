..  include:: /Includes.rst.txt

..  _feature-106415-1742660981:

==================================================================
Feature: #106415 - Add stdWrap to config.htmlTag.attributes.[attr]
==================================================================

See :issue:`106415`

Description
===========

Each attribute within the TypoScript option
:typoscript:`config.htmlTag.attributes.[attr]` now has all
:typoscript:`stdWrap` possibilities available.

This option is used to control the attributes of the single :html:`<html>`
tag of a rendered page.


Impact
======

It is now possible to e.g. use a custom :typoscript:`userFunc`,
:typoscript:`override` or :typoscript:`getData` via TypoScript:

..  code-block:: typoscript

    config.htmlTag.attributes.my-attribute = 123
    config.htmlTag.attributes.my-attribute.override = 456

..  code-block:: typoscript

    config.htmlTag.attributes.my-attribute = 123
    config.htmlTag.attributes.my-attribute.userFunc = MyVendor\\MyExtension\\HtmlTagEnhancer->overrideMyAttribute

..  index:: TypoScript, ext:frontend
