..  include:: /Includes.rst.txt

..  _feature-105638-1732034075:

==============================================
Feature: #105638 - Modify fetched page content
==============================================

See :issue:`105638`

Description
===========

With :issue:`103894` the new data processor :ref:`PageContentFetchingProcessor <feature-103894-1716544976>`
has been introduced, to allow fetching page content based on the current page
layout, taking the configured :php:`SlideMode` into account.

Fetching content has previously mostly been done via the `Content` content
object. A common example looked like this:

..  code-block:: typoscript

    page.20 = CONTENT
    page.20 {
        table = tt_content
        select {
            orderBy = sorting
            where = colPos=0
        }
    }

As mentioned in the linked changelog, using the `page-content` data processor,
this can be simplified to:

..  code-block:: typoscript

    page.20 = page-content

This however reduces the possibility to modify the select configuration
(SQL statement), used to define which content should be fetched, as this
is automatically handled by the data processor. However, there might be some
use cases in which the result needs to be adjusted, e.g. to hide specific
page content, like it's done by :ref:`EXT:content_blocks <friendsoftypo3/content-blocks:cb-nesting-prevent-output-fe>`
for child elements. For such use cases, the new PSR-14 :php:`AfterContentHasBeenFetchedEvent`
has been introduced, which allows to manipulate the list of fetched page
content.

The following member properties of the event object are provided:

- :php:`$groupedContent`: The fetched page content, grouped by their column - as defined in the page layout
- :php:`$request`: The current request, which can be used to e.g. access the page layout in question

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, removes some of the fetched page content elements based on
specific field values.

..  code-block:: php
    :caption: my_extension/Classes/EventListener/MyEventListener.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\Event\AfterContentHasBeenFetchedEvent;

    final class MyEventListener
    {
        #[AsEventListener]
        public function removeFetchedPageContent(AfterContentHasBeenFetchedEvent $event): void
        {
            foreach ($event->groupedContent as $columnIdentifier => $column) {
                foreach ($column['records'] ?? [] as $key => $record) {
                    if ($record->has('parent_field_name') && (int)($record->get('parent_field_name') ?? 0) > 0) {
                        unset($event->groupedContent[$columnIdentifier]['records'][$key]);
                    }
                }
            }
        }
    }

Impact
======

Using the new PSR-14 :php:`AfterContentHasBeenFetchedEvent`, it's possible
to manipulate the page content, which has been fetched by the
:php:`PageContentFetchingProcessor`, based on the page layout and
corresponding columns configuration.

..  index:: Frontend, PHP-API, TypoScript, ext:frontend
