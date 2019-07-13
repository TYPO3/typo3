.. include:: ../../Includes.txt

==========================================================
Feature: #86973 - TypoScript getText property siteLanguage
==========================================================

See :issue:`86973`

Description
===========

Site language configuration can now be accessed via the :typoscript:`getText` property :typoscript:`siteLanguage`
in TypoScript.

Examples:

.. code-block:: typoscript

    page.10 = TEXT
    page.10.data = siteLanguage:navigationTitle
    page.10.wrap = This is the title of the current site language: |

.. code-block:: typoscript

    page.10 = TEXT
    page.10.dataWrap = The current site language direction is {siteLanguage:direction}

Impact
======

Accessing the current site language configuration is now possible in TypoScript.

.. index:: PHP-API, ext:frontend
