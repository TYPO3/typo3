..  include:: /Includes.rst.txt

..  _feature-107358-1740166029:

========================================================
Feature: #107358 - Introduce Fluid page title ViewHelper
========================================================

See :issue:`107358`

Description
===========

A new Fluid ViewHelper :html:`<f:page.title>` has been introduced to allow
setting the page title directly from Fluid templates.

This is especially useful for Extbase plugins that need to set a page title
in their detail views without having to implement their own custom Page Title
Provider.

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Templates/Item/Show.html

    <f:page.title>{item.title}</f:page.title>

    <h1>{item.title}</h1>
    <p>{item.description}</p>

The ViewHelper can also be used with static content:

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Templates/Static/About.html

    <f:page.title>About Us - Company Information</f:page.title>

    <h1>About Us</h1>
    <p>Welcome to our company...</p>


Impact
======

Extension developers can now easily set page titles from their Fluid templates
without needing to create custom Page Title Providers for each extension. This
simplifies the implementation of dynamic page titles in Fluid templates or
especially Extbase plugins for detail views where the title should
reflect the displayed record.

The ViewHelper integrates seamlessly with TYPO3's existing Page Title Provider
system and respects the configured provider priorities.

..  index:: Frontend, ext:fluid
