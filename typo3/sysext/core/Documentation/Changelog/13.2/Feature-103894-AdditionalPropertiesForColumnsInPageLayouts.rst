.. include:: /Includes.rst.txt

.. _feature-103894-1716544976:

====================================================================
Feature: #103894 - Additional properties for columns in Page Layouts
====================================================================

See :issue:`103894`

Description
===========

Backend Layouts were introduced in TYPO3 v6 in order to customize the view of
the Page module in TYPO3 Backend for a page, but has then since grown also in
Frontend rendering to select e.g. Fluid template files via TypoScript for a page,
commonly used via :typoscript:`data:pagelayout`.

In order to use a single source for Backend and Frontend representation, the
definition of a "Backend Layout" or "Page Layout" is expanded to also include
more information for a specific content area. The Content Area is previously
defined via "name" (for the label in the Page Module) and "colPos",
the numeric database field in which content is grouped in.

A definition can now optionally also contain a "slideMode" property and an
"identifier" property next to each colPos, in order to simplify the Frontend
rendering.

Whereas "identifier" is a speaking representation for the colPos, such as
"main", "sidebar" or "footerArea", the "slideMode" can be set to one of the
three options:

* :typoscript:`slideMode = slide` - if no content is found, check the parent pages for more content
* :typoscript:`slideMode = collect` - use all content from this page, and the parent pages as one collection
* :typoscript:`slideMode = collectReverse`- same as "collect" but in the opposite order

With this information added, a new DataProcessor :typoscript:"page-content"
(:php:`PageContentFetchingProcessor`) is introduced for the Frontend Rendering,
which fetches all content for a page respecting the settings from the
Page Layout.


Impact
======

Enriching the Backend Layout information for each colPos enables a TYPO3
integrator to write less TypoScript in order to render content on a page.

The DataProcessor fetches all content elements from all defined columns with an
included "identifier" in the selected Backend Layout and makes the resolved
record objects available in the Fluid Template via
:html:`{content."myIdentifier".records}`.

Example for an enriched Backend Layout definition:

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
                    slideMode = slide
                  }
                }
              }
            }
          }
        }
      }
    }

Example for the Frontend output:

.. code-block:: typoscript

    page = PAGE
    page.10 = PAGEVIEW
    page.10.paths.10 = EXT:my_site_package/Tests/Resources/Private/Templates/
    page.10.dataProcessing.10 = page-content
    page.10.dataProcessing.10.as = myContent

.. code-block:: html

    <main>
        <f:for each="{myContent.main.records}" as="record">
            <h4>{record.header}</h4>
        </f:for>
    </main>


.. index:: Backend, Frontend, ext:frontend
