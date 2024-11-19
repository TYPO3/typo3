.. include:: /Includes.rst.txt

.. _feature-103894-1716544976:

====================================================================
Feature: #103894 - Additional properties for columns in Page Layouts
====================================================================

See :issue:`103894`

Description
===========

Backend Layouts were introduced in TYPO3 v6 in order to customize the view of
the :guilabel:`Page` module in TYPO3 backend for pages, but has since grown, also in
frontend rendering, to select e.g. Fluid template files via TypoScript for a page,
commonly used via :typoscript:`data:pagelayout`.

In order to use a single source for backend and frontend representation, the
definition of a "Backend Layout" or "Page Layout" is expanded to also include
more information for a specific content area. The Content Area was previously
defined via "name" (for the label in the :guilabel:`Page` module) and "colPos",
the numeric database field in which content is grouped in.

A definition can now optionally also contain a "slideMode" property and an
"identifier" property next to each colPos, in order to simplify frontend
rendering.

Whereas "identifier" is a speaking representation for the colPos, such as
"main", "sidebar" or "footerArea", the "slideMode" can be set to one of the
three options:

*   :typoscript:`slideMode = slide` - if no content is found, check the parent
    pages for more content
*   :typoscript:`slideMode = collect` - use all content from this page, and the
    parent pages as one collection
*   :typoscript:`slideMode = collectReverse`- same as "collect" but in the
    opposite order

With this information added, a new DataProcessor :typoscript:`page-content`
(:php:`\TYPO3\CMS\Frontend\DataProcessing\PageContentFetchingProcessor`)
is introduced for the frontend rendering,
which fetches all content for a page and respecting the settings from the
page layout.

The new data processor allows to manipulate the fetched page content via
the PSR-14 :ref:`AfterContentHasBeenFetchedEvent <feature-105638-1732034075>`.


Impact
======

Enriching the backend layout information for each colPos enables a TYPO3
integrator to write less TypoScript in order to render content on a page.

The DataProcessor fetches all content elements from all defined columns with an
included "identifier" in the selected backend layout and makes the resolved
record objects available in the Fluid template via
:html:`{content."myIdentifier".records}`.

Example of an enriched backend layout definition:

..  code-block:: typoscript

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
                    slideMode = slide
                  }
                }
              }
            }
          }
        }
      }
    }

Example of the frontend output:

..  code-block:: typoscript

    page = PAGE
    page.10 = PAGEVIEW
    page.10.paths.10 = EXT:my_site_package/Tests/Resources/Private/Templates/
    page.10.dataProcessing.10 = page-content
    page.10.dataProcessing.10.as = myContent

..  code-block:: html

    <main>
        <f:for each="{myContent.main.records}" as="record">
            <f:cObject typoscriptObjectPath="{record.mainType}" table="{record.mainType}" data="{record}"/>
        </f:for>
    </main>

The :html:`f:cObject` ViewHelper above uses the rendering definition of the
tt_content table :html:`{record.mainType}` to render the Content Element from
the list. The attribute :html:`data` expects the raw database record, which is
retrieved from :html:`{record}`.

.. index:: Backend, Frontend, ext:frontend
