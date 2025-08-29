..  include:: /Includes.rst.txt

..  _feature-107359-1740170000:

=======================================================
Feature: #107359 - Introduce Fluid page meta ViewHelper
=======================================================

See :issue:`107359`

Description
===========

A new Fluid ViewHelper :html:`<f:page.meta>` has been introduced to allow
setting meta tags directly from Fluid templates using TYPO3's MetaTagManager API.

This is especially useful for Extbase plugins that need to set meta tags
in their detail views without having to implement custom meta tag handling.

In addition, frontend developers do not need to write custom TypoScript
anymore but can use Fluid directly.

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Templates/Item/Show.html

    <f:page.meta property="description">{item.description}</f:page.meta>
    <f:page.meta property="og:title">{item.title}</f:page.meta>
    <f:page.meta property="og:type">article</f:page.meta>

    <h1>{item.title}</h1>
    <p>{item.description}</p>

The ViewHelper supports all features of the MetaTagManager API:

**OpenGraph and Twitter / X Card meta tags:**

..  code-block:: html

    <f:page.meta property="og:title">My Article Title</f:page.meta>
    <f:page.meta property="og:description">Article description</f:page.meta>
    <f:page.meta property="twitter:card">summary_large_image</f:page.meta>

**Sub-properties for complex meta tags:**

..  code-block:: html

    <f:page.meta property="og:image"
                 subProperties="{width: 1200, height: 630, alt: 'Article image'}">{item.image.url}</f:page.meta>

**Custom meta tag types:**

..  code-block:: html

    <f:page.meta property="author" type="name">John Doe</f:page.meta>
    <f:page.meta property="robots" type="name">index, follow</f:page.meta>

**Replacing existing meta tags:**

..  code-block:: html

    <f:page.meta property="description" replace="true">Override any existing description</f:page.meta>

ViewHelper Arguments
====================

- **property** (required): The meta property name (e.g. "description", "og:title")
- **type** (optional): The meta type attribute ("name", "property", "http-equiv"). If not set, the appropriate MetaTagManager will determine the type automatically
- **subProperties** (optional): Array of sub-properties for complex meta tags
- **replace** (optional): Boolean to replace existing meta tags with the same property (default: false)

Impact
======

Extension developers can now easily set meta tags from their Fluid templates
without needing to use the MetaTagManager API directly in PHP code. This
simplifies the implementation of SEO-optimized pages in Extbase plugins,
especially for detail views where meta tags should reflect the displayed record.

The ViewHelper integrates seamlessly with TYPO3's existing MetaTagManager system
and respects all configured meta tag managers and their priorities.

..  index:: Frontend, ext:fluid
