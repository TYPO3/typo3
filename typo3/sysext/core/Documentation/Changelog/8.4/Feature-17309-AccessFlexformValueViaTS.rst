.. include:: /Includes.rst.txt

==============================================
Feature: #17309 - Access flexform value via TS
==============================================

See :issue:`17309`

Description
===========

It is now possible to access properties of a flexform field by using TypoScript.

.. code-block:: typoscript

    lib.flexformContent = CONTENT
    lib.flexformContent {
        table = tt_content
        select {
            pidInList = this
        }

        renderObj = COA
        renderObj {
            10 = TEXT
            10 {
                data = flexform: pi_flexform:settings.categories
            }
        }
    }

The key `flexform` is followed by the field which holds the flexform data and the name of the property whose content should be retrieved.

.. index:: TypoScript, FlexForm
