.. include:: /Includes.rst.txt

.. _feature-104974-1726401724:

===================================================================
Feature: #104974 - Content area related information in the frontend
===================================================================

See :issue:`104974`

Description
===========

With :ref:`feature-103504-1712041725` the new :typoscript:`PAGEVIEW` cObject
has been introduced for the Frontend Rendering. It's a powerful alternative
to the :typoscript:`FLUIDTEMPLATE` cObjct and allows to render a full page
with less configuration.

The :typoscript:`PAGEVIEW` has now been further extended and does also
provide all content elements, related to the page, grouped by their
corresponding columns as defined in the page layout. The elements
are provided as fully resolved :php:`Record` objects (see :ref:`feature-103783-1715113274`
and :ref:`feature-103581-1723209131`).

The elements are attached to the new :php:`ContentArea` object, which beside
the elements itself also contains all the column related information and
configuration. This is quite useful for the frontend rendering, because it
might be important for an element to know in which context it should be
rendered. With this information, an element can e.g. decide to not render
the :html:`Header` partial if it is included in the sidebar content area.

The :php:`ContentArea` objects are added to the view using either the variable
name defined via :typoscript:`contentAs` or falls back to :html:`content`. The
content elements can then be accessed via the :html:`records` property.

The :php:`ContentArea`'s' contain all the backend layout related configuration,
such as the :ref:`content restrictions <feature-108623-1768315053>`, which
allows further validation, e.g. if the given content types are actually valid.

:html:`{content.main.records}` can therefore be used to get all content elements
from the :html:`main` content area. :html:`main` is the identifier, as
defined in the page layout and :html:`content` is the default variable name.

.. important::

    The :php:`ContentArea` objects are attached in the :php:`ContentAreaCollection`
    which implements the PSR-11 :php:`ContainerInterface` to allow access to
    the areas via :php:`get()`. To optimize performance, the :php:`ContentArea`
    objects itself are instantiated only when accessed (lazy loading).

By accessing the :php:`ContentArea` with :html:`{content.main}` the following
information is available (as defined in the page layout):

* :html:`identifier` - The column identifier
* :html:`colPos` - The defined `colPos`
* :html:`name` - The (speaking) `name`, which might be a locallang key
* :html:`allowedContentTypes` - The defined `allowedContentTypes`
* :html:`disallowedContentTypes` - The defined `disallowedContentTypes`
* :html:`slideMode` - The defined :php:`ContentSlideMode`, defaults to :php:`ContentSlideMode::None`
* :html:`configuration` - The whole content area related configuration
* :html:`records` - The content elements as :php:`Record` objects

The following example is enough to render content elements of a page with a
single column:

.. code-block:: typoscript

    mod.web_layout.BackendLayouts {
      default {
        title = Default
        config {
          backend_layout {
            colCount = 1
            rowCount = 1
            rows {
              1 {
                columns {
                  1 {
                    name = Main Content Area
                    colPos = 0
                    identifier = main
                  }
                }
              }
            }
          }
        }
      }
    }

.. code-block:: typoscript

    page = PAGE
    page.10 = PAGEVIEW
    page.10.paths.10 = EXT:my_site_package/Resources/Private/Templates/

.. code-block:: html

    <f:for each="{content.main.records}" as="record">
        <f:render partial="ContentElement" arguments="{record: record, area: content.main}">
    </f:for>

Introducing :php:`ContentArea` also improves the
:php:`AfterContentHasBeenFetchedEvent` - used to manipulate the resolved
content elements of each area - by having additional context at hand.

Impact
======

It's now possible to access all content elements of a page, grouped by their
corresponding column, while having the column related information and
configuration at hand. Next to less configuration effort, different renderings
for the same element, depending on the context, are easily possible.

Example
=======

A content element template using the :html:`Default` layout and rendering
the :html:`Header` partial only in case it has not been added to the
`sidebar` column.

..  code-block:: html

    <f:layout name="Default" />

    <f:section name="Main">
        <f:if condition="{area.identifier} != 'sidebar'">
            <f:render partial="Header" arguments="{_all} />
        </f:if>

        <p>{record.text}</p>
        <f:image image="{record.image}" width="{area.configuration.imageWidth}" />
    </f:section>

.. index:: Frontend, ext:frontend
