.. include:: /Includes.rst.txt

======================================================
Feature: #88276 - TypoScript Condition for page layout
======================================================

See :issue:`88276`

Description
===========

A new condition enables integrators to check for the defined backend layout of a page including the
inheritance of the field *Backend Layout (subpages of this page)*

.. code-block:: typoscript

    # Using backend_layout records
    [tree.pagelayout == 2]
        page.1 = TEXT
        page.1.value = Layout 2
    [END]

    # Using TsConfig provider of Backend Layouts
    [tree.pagelayout == "pagets__Home"]
        page.1 = TEXT
        page.1.value = Layout Home
    [END]

This condition is available for both frontend and backend.

Impact
======

Change TypoScript or TsConfig based on the backend layout of a page.

.. index:: Frontend, Backend, TypoScript, ext:frontend
