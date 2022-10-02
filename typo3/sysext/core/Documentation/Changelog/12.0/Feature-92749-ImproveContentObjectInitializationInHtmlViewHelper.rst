.. include:: /Includes.rst.txt

.. _feature-92749:

=========================================================================
Feature: #92749 - Improve content object initialization in HtmlViewHelper
=========================================================================

See :issue:`92749`

Description
===========

New options are available for the :html:`f:format.html` ViewHelper,
related to the initialization of the underlying content object. The options
are similar to the ones, available for the :html:`f:cObject` ViewHelper.

With the `data` argument an integrator can pass an array or an object (e.g. a
domain model), which will be used as data record on initialization.

With the `currentValueKey` argument, one can specify the array key of the
provided data record, which should be used as the current value.

Alternatively, one can use the new `current` argument to set a static value
as current value for the content object.

Additionally, with the `table` argument, the :php:`ContentObjectRenderer`
receives the table name, the given data record is from.

Example
=======

Access a news record title with `CURRENT:1` and resolve a marker:

..  code-block:: html

    <f:format.html
            parseFuncTSPath="lib.news"
            data="{uid: 1, title: \'Great news\'}"
            currentValueKey="title">
        ###PROJECT### news:
    </f:format.html>

..  code-block:: typoscript

    constants.PROJECT = TYPO3
    lib.news {
        htmlSanitize = 1
        constants = 1
        plainTextStdWrap.noTrimWrap = || |
        plainTextStdWrap.dataWrap = |{CURRENT:1}
    }

This will result in:

..  code-block:: html

    TYPO3 news: Great news

Impact
======

The :html:`f:format.html` ViewHelper can now be utilized in more customized use cases.

.. index:: Frontend, TypoScript, ext:fluid
