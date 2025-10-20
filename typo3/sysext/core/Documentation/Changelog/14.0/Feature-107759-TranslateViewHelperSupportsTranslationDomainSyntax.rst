..  include:: /Includes.rst.txt

..  _feature-107759-1729433323:

=========================================================================
Feature: #107759 - TranslateViewHelper supports Translation Domain syntax
=========================================================================

See :issue:`107759`

Description
===========

The Fluid :html:`<f:translate>` ViewHelper now supports the new
:ref:`Translation Domain syntax <feature-93334-translation-domain-format>`,
providing a more concise and readable way to reference translation labels.

A new :html:`domain` attribute has been added that accepts both traditional
extension names and the new translation domain names.

The ViewHelper now supports multiple ways to specify translations:

1. **New domain attribute** - Recommended for new code:

   ..  code-block:: html

       <f:translate key="form.legend" domain="my_extension" />
       <f:translate key="form.legend" domain="my_extension.messages" />

2. **Inline domain syntax in key** - Shortest form:

   ..  code-block:: html

       <f:translate key="my_extension.messages:form.legend" />
       <f:translate key="LLL:my_extension.messages:form.legend" />

3. **Traditional extensionName attribute** - Still supported:

   ..  code-block:: html

       <f:translate key="form.legend" extensionName="MyExtension" />
       <f:translate key="form.legend" extensionName="my_extension" />

4. **Full LLL reference** - Classic syntax, still supported:

   ..  code-block:: html

       <f:translate key="LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:form.legend" />


Domain Attribute Priority
==========================

When both :html:`domain` and :html:`extensionName` are provided, the
:html:`domain` attribute takes precedence:

..  code-block:: html

    <!-- Uses "other_extension.messages" domain, not "MyExtension" -->
    <f:translate key="label" domain="other_extension.messages" extensionName="MyExtension" />


Automatic Domain Detection
===========================

If neither :html:`domain` nor :html:`extensionName` are specified, the
ViewHelper attempts to automatically detect the translation domain from the
context:

1. **Extbase context**: Uses the controller extension name
2. **Key with domain prefix**: Extracts domain from :html:`key="domain:id"` syntax
3. **LLL reference**: Parses the extension key from the file path

Examples
========

Using domain attribute with full domain name
---------------------------------------------

You can specify the exact translation domain including resource names:

..  code-block:: html

    <f:translate key="menu.item" domain="backend.toolbar" />
    <!-- Resolves to: EXT:backend/Resources/Private/Language/locallang_toolbar.xlf -->

Inline domain syntax in key
----------------------------

The shortest form combines domain and key directly:

..  code-block:: html

    <f:translate key="core.form.tabs:general" />
    <!-- Resolves to: EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf -->

With arguments and default values
----------------------------------

All existing ViewHelper features work with the new syntax:

..  code-block:: html

    <f:translate
        key="users"
        domain="backend.messages"
        arguments="{0: count}"
        default="No users found"
    />

Using variables for domain
---------------------------

Dynamic domain selection is supported:

..  code-block:: html

    <f:translate key="label.title" domain="{myDomain}" />


Migration from extensionName
=============================

Existing code using :html:`extensionName` continues to work without changes.
However, new code should prefer the :html:`domain` attribute combined with
translation domain syntax for better readability:

Before:

..  code-block:: html

    <f:translate key="LLL:EXT:my_extension/Resources/Private/Language/locallang_form.xlf:legend" />
    <f:translate key="legend" extensionName="MyExtension" />

After:

..  code-block:: html

    <f:translate key="my_extension.form:legend" />
    <f:translate key="legend" domain="my_extension.form" />


Impact
======

The :html:`<f:translate>` ViewHelper now provides a more convenient and
readable way to reference translations using the new translation domain syntax.
This reduces verbosity in Fluid templates and aligns with modern translation
system conventions used in Symfony and other frameworks.

All existing syntax forms remain fully supported, ensuring backward
compatibility. The new syntax can be adopted incrementally within a project,
and both old and new forms can coexist in the same template.

..  index:: Fluid, Localization, ext:fluid
