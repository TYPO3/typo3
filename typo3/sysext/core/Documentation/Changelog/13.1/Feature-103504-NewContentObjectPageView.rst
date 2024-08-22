.. include:: /Includes.rst.txt

.. _feature-103504-1712041725:

=============================================
Feature: #103504 - New ContentObject PAGEVIEW
=============================================

See :issue:`103504`

Description
===========

A new content object for TypoScript :typoscript:`PAGEVIEW` has been added.

This cObject is mainly intended for rendering a full page in the TYPO3 frontend
with fewer configuration options over the generic :typoscript:`FLUIDTEMPLATE`
cObject.

A basic usage of the :typoscript:`PAGEVIEW` cObject is as follows:

..  code-block:: typoscript

    page = PAGE
    page.10 = PAGEVIEW
    page.10.paths.100 = EXT:mysite/Resources/Private/Templates/

:typoscript:`PAGEVIEW` wires certain parts automatically:

1.  The name of the used page layout (backend layout) is resolved automatically.

    If a page has a layout named "with_sidebar", the template file is then resolved
    to :file:`EXT:mysite/Resources/Private/Templates/Pages/With_sidebar.html`.

2.  Fluid features for layouts and partials are wired automatically. They
    can be placed into :file:`EXT:mysite/Resources/Private/Templates/Layouts/`
    and :file:`EXT:mysite/Resources/Private/Templates/Partials/` with above example.

3.  Default variables are available in the Fluid template:

    -   :typoscript:`settings` - contains all TypoScript settings (= constants)
    -   :typoscript:`site` - the current :php:`Site` object
    -   :typoscript:`language` - the current :php:`SiteLanguage` object
    -   :typoscript:`page` - the current page record as object

..  note::
    The :php:`PageInformation` object contains all relevant information about
    the current page. Those are, for example, the corresponding page record, the
    root line, and many more. Worth mentioning is also the :php:`PageLayout`
    object, which provides all the information about the selected backend layout.
    This includes the identifier, the title, the available content areas
    with their corresponding name and `colPos`. Additionally, the full (raw)
    backend layout configuration is available.

There is no special Extbase resolving done for the templates.

Migration
---------

Before
~~~~~~

..  code-block:: typoscript

    page = PAGE
    page {
        10 = FLUIDTEMPLATE
        10 {
            templateName = TEXT
            templateName {
                stdWrap {
                    cObject = TEXT
                    cObject {
                        data = levelfield:-2, backend_layout_next_level, slide
                        override {
                            field = backend_layout
                        }
                        split {
                            token = pagets__
                            1 {
                                current = 1
                                wrap = |
                            }
                        }
                    }
                    ifEmpty = Standard
                }
            }

            templateRootPaths {
                100 = {$plugin.tx_mysite.templateRootPaths}
            }

            partialRootPaths {
                100 = {$plugin.tx_mysite.partialRootPaths}
            }

            layoutRootPaths {
                100 = {$plugin.tx_mysite.layoutRootPaths}
            }

            variables {
                pageUid = TEXT
                pageUid.data = page:uid

                pageTitle = TEXT
                pageTitle.data = page:title

                pageSubtitle = TEXT
                pageSubtitle.data = page:subtitle

                parentPageTitle = TEXT
                parentPageTitle.data = levelfield:-1:title
            }

            dataProcessing {
                10 = menu
                10.as = mainMenu
            }
        }
    }

After
~~~~~

..  code-block:: typoscript

    page = PAGE
    page {
        10 = PAGEVIEW
        10 {
            paths {
                100 = {$plugin.tx_mysite.templatePaths}
            }
            variables {
                parentPageTitle = TEXT
                parentPageTitle.data = levelfield:-1:title
            }
            dataProcessing {
                10 = menu
                10.as = mainMenu
            }
        }
    }

In Fluid, the pageUid is available as :html:`{page.uid}` and pageTitle
as :html:`{page.title}`. The page layout identifier can be accessed
using :html:`{page.pageLayout.identifier}`.

Impact
======

Creating new page templates based on Fluid follows conventions in order to
reduce the amount of TypoScript needed to render a page in the TYPO3 frontend.

Sane defaults are applied, variables and settings are available at any time.

..  note::

    This cObject is marked as experimental until TYPO3 v13 LTS as some
    functionality will be added.

..  note::

    Default variable names cannot be set or overridden and trying to do
    will throw an exception.

.. index:: TypoScript, ext:frontend
