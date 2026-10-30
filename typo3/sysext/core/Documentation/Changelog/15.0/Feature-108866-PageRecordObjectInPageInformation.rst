..  include:: /Includes.rst.txt

..  _feature-108866-1793383685:

==================================================================
Feature: #108866 - Page record object in frontend page information
==================================================================

See :issue:`108866`

Description
===========

The frontend request attribute :php:`frontend.page.information`, an instance
of :php:`\TYPO3\CMS\Frontend\Page\PageInformation`, provides the new method
:php:`getRecord()`. It returns the current page as record object implementing
:php:`\TYPO3\CMS\Core\Domain\RecordInterface`, created by the Record API from
the same page row :php:`getPageRecord()` returns. The row is the final one of
the request: it reflects resolved shortcuts and mount points, and on a
translated page it is the language overlay. As with :php:`getPageRecord()`,
:php:`getUid()` returns the uid of the page in the default language, the uid
of the translation is available via
:php:`getComputedProperties()->getLocalizedUid()`.

Like other resolved records, its fields are transformed according to their TCA
configuration, for example relations such as :sql:`media` are resolved to file
references. Only the fields of the page type are available, system fields such
as :sql:`crdate` or :sql:`starttime` are not. Use :php:`getPageRecord()` or
:html:`{page.pageRecord.crdate}` for those. The record is created once the
page and language are resolved, after the event
:php:`\TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent`.
Relations are resolved lazily on first access.

..  code-block:: php

    $page = $request->getAttribute('frontend.page.information')->getRecord();
    $title = $page->get('title');

In Fluid templates rendered by :typoscript:`PAGEVIEW`, the record is
available via the :html:`page` variable:

..  code-block:: html

    <h1>{page.record.title}</h1>
    <f:for each="{page.record.media}" as="image">
        <f:image image="{image}" />
    </f:for>

:php:`getPageRecord()` continues to return the page row as array.

Impact
======

Extensions and templates can access the current frontend page as record
object with resolved relations, without creating it from the page row
themselves.

..  index:: Frontend, Fluid, PHP-API, ext:frontend
