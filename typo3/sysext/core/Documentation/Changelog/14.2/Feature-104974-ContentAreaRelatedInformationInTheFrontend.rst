.. include:: /Includes.rst.txt

.. _feature-104974-1726401724:

===================================================================
Feature: #104974 - Content area related information in the frontend
===================================================================

See :issue:`104974`

Description
===========

:ref:`feature-103504-1712041725` introduced the :typoscript:`PAGEVIEW` cObject
for frontend rendering. It is a powerful alternative to
the :typoscript:`FLUIDTEMPLATE` cObject, allowing a full page to be rendered with
less configuration.

:typoscript:`PAGEVIEW` has now been extended and provides all
content elements related to a page, grouped by their columns
as defined in the page layout. The elements are provided as fully resolved
:php:`Record` objects (see :ref:`feature-103783-1715113274` and
:ref:`feature-103581-1723209131`).

The content elements are attached to the new
:php:`\TYPO3\CMS\Core\Page\ContentArea` object, which also contains all
column-related information and configuration.
This is useful for frontend rendering because an
element may need to know its rendering context. Knowing
this information, an element can, for example, decide not to render the
:html:`Header` partial if it is in a sidebar content area.

:php-short:`\TYPO3\CMS\Core\Page\ContentArea` objects are added to the
view either by variable name defined in :typoscript:`contentAs` or,
if not defined, `content`. Content elements can then be accessed via the
:html:`records` property.

:php-short:`\TYPO3\CMS\Core\Page\ContentArea` objects contain
backend layout-related configuration, such as
:ref:`content restrictions <feature-108623-1768315053>`. These allow
further validation such as whether a content type is
valid.

Therefore :html:`{content.main.records}` can be used to get content
elements from the `main` content area. `main` is the identifier as defined in
the page layout, and `content` is the default variable name.

.. important::

    :php-short:`\TYPO3\CMS\Core\Page\ContentArea` objects are attached in
    the :php-short:`\TYPO3\CMS\Core\Page\ContentAreaCollection`, which
    implements the PSR-11 :php:`\Psr\Container\ContainerInterface` to allow
    access to the content areas using :php:`get()`. To optimize performance
    :php-short:`\TYPO3\CMS\Core\Page\ContentArea` objects are
    instantiated only when accessed (lazy loading).

Accessing a :php-short:`\TYPO3\CMS\Core\Page\ContentArea` using
:html:`{content.main}` makes the following information available, as defined in
the page layout:

*   :html:`identifier` - The column identifier
*   :html:`colPos` - The defined `colPos`
*   :html:`name` - The descriptive `name`, which might be a locallang key
*   :html:`allowedContentTypes` - The defined `allowedContentTypes`
*   :html:`disallowedContentTypes` - The defined `disallowedContentTypes`
*   :html:`slideMode` - The defined :php:`ContentSlideMode`, which defaults to
    :php:`ContentSlideMode::None`
*   :html:`configuration` - The complete content area-related configuration
*   :html:`records` - The content elements as :php:`Record` objects

The following example renders the content elements of a page which has only
a single column:

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
        <f:render partial="ContentElement"
                  arguments="{record: record, area: content.main}" />
    </f:for>

The introduction of the new
:ref:`f:render.contentArea <feature-108726-1769071158>` and
:ref:`f:render.record <feature-108726-1769503907>` ViewHelpers means that manually
iterating over content elements is no longer necessary. All the content elements
in a content area can be rendered with a single ViewHelper call:

..  code-block:: html

    <!-- Tag syntax -->
    <f:render.contentArea contentArea="{content.main}" />

    <!-- Inline syntax -->
    {content.main -> f:render.contentArea()}

To render a single record, use the :html:`f:render.record` ViewHelper:

..  code-block:: html

    <!-- Tag syntax -->
    <f:render.record record="{content.main.records.0}" />

    <!-- Inline syntax -->
    {content.main.records.0 -> f:render.record()}

.. note::

    :php-short:`\TYPO3\CMS\Core\Page\ContentArea` helps the
    :php-short:`\TYPO3\CMS\Frontend\Event\AfterContentHasBeenFetchedEvent`
    to manipulate content elements in an area by
    providing context.

Impact
======

It is now possible to access all the content elements on a page, grouped by their
column, as well as having all the column-related information and
configuration available. In addition to reduced configuration effort,
different rendering is possible for an element depending on context.

Example
=======

A content element template using a `Default` layout that renders the
`Header` partial only if the content element is not in the `sidebar` column.

..  code-block:: html

    <f:layout name="Default" />

    <f:section name="Main">
        <f:if condition="{area.identifier} != 'sidebar'">
            <f:render partial="Header" arguments="{_all}" />
        </f:if>

        <p>{record.text}</p>
        <f:image image="{record.image}" width="{area.configuration.imageWidth}" />
    </f:section>

.. index:: Frontend, ext:frontend
