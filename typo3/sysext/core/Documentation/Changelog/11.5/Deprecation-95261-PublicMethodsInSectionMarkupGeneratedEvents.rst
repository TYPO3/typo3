.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #95261 - Public methods in SectionMarkupGenerated events
=====================================================================

See :issue:`95261`

Description
===========

In TYPO3 v10, a new page module has been introduced. In this version,
administrators could choose between those two approaches by using a feature
toggle. This toggle has been removed in TYPO3 v11, making the
:php:`TYPO3\CMS\Backend\View\PageLayoutView`
unused. Two events, introduced in :issue:`88921`, however exposed this class.

Therefore the public methods :php:`getPageLayoutView()` and
:php:`getLanguageId()` of the :php:`BeforeSectionMarkupGeneratedEvent`
and :php:`AfterSectionMarkupGeneratedEvent` have been marked as deprecated.

Impact
======

Calling those methods in event listeners will trigger a PHP :php:`E_USER_DEPRECATED` error.
The extension scanner also detects those calls as weak match.

Affected installations
======================

All installations using one of the mentioned methods are affected.

Migration
=========

Access necessary information using the new methods :php:`getPageLayoutContext()`
and :php:`getRecords()`.

Examples for retrieving information with the new methods:

.. code-block:: php

    // Get the language id of the column
    $event->getPageLayoutContext()->getSiteLanguage()->getLanguageId();

    // Get records of the column
    $event->getRecords();

    // Get the page record of the column
    $event->getPageLayoutContext()->getPageRecord();


.. index:: Backend, PHP-API, FullyScanned, ext:backend
