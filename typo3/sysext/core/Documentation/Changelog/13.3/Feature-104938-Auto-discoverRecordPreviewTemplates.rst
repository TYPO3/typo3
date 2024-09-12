.. include:: /Includes.rst.txt

.. _feature-104938-1726146212:

=========================================================
Feature: #104938 - Auto-discover record preview templates
=========================================================

See :issue:`104938`

Description
===========

Rendering record previews in the backend has been improved by adding
a new TSConfig option to define the template paths for auto-discovery of the
corresponding preview templates, partials and layouts. The preview templates
receive the completely transformed (see: :ref:`<feature-103581-1723209131>`)
:php:`RecordInterface` object to work with.

Currently the previews are only used in the page module for content elements
(:php:`tt_content`) but will be used in other areas of the backend in the
future.

The syntax is :typoscript:`record.preview.<table_name>.paths`.

Example
=======

.. code-block:: typoscript

    record.preview.tt_content.paths {
        10 = EXT:my_site/Resources/Private/Templates/Preview/Content/
    }

To render a preview for a `header` content element, the file :file:`Header.html`
has to be added to the defined directory. For plugin previews (using CType
`list`), the template has to be placed into the subdirectory :file:`List`.
Therefore, to render the plugin `blog_pi1`, the file
:file:`EXT:my_site/Resources/Private/Templates/Preview/Content/List/BlogPi1.html`
has to be used.

As mentioned, the partial and layout paths are also automatically resolved.

.. code-block:: html

    <f:layout name="Default" />

    <f:section name="Main">
        {record.uid}
        <f:render partial="Header" arguments="{_all}" />
    </f:section>


For above example, following files have to exist:

* :file:`EXT:my_site/Resources/Private/Templates/Preview/Content/Header.html`
* :file:`EXT:my_site/Resources/Private/Templates/Preview/Content/Layouts/Default.html`
* :file:`EXT:my_site/Resources/Private/Templates/Preview/Content/Partials/Header.html`

In case paths are configured but no template is found for a content type, the
existing functionality kicks in and checks whether a template is defined via
:typoscript:`mod.web_layout.tt_content.preview.<content_type>`.

Impact
======

It's now possible to define template paths for record previews, which
will be used to auto-discover the preview templates as well as corresponding
layouts and partials.

.. index:: Backend, Fluid, TSConfig, ext:backend
