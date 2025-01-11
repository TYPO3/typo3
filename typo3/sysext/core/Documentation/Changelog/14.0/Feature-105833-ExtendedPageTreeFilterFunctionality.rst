..  include:: /Includes.rst.txt

..  _feature-105833-1734420558:

==========================================================
Feature: #105833 - Extended page tree filter functionality
==========================================================

See :issue:`105833`

Description
===========

The page tree is one of the central components in the TYPO3 backend,
particularly for editors. However, in large installations, the page tree can
quickly become overwhelming and difficult to navigate. To maintain a clear
overview, the page tree can be filtered using basic terms, such as the page
title or ID.

To enhance the filtering capabilities, the new PSR-14
:php:`\TYPO3\CMS\Backend\Tree\Repository\BeforePageTreeIsFilteredEvent` 
has been introduced. This event allows developers to extend the filter's 
functionality and process the given search phrase in more advanced ways.

Using the Event, it is for example possible to evaluate a given URL or to
add additional field matchings, like filter pages by their :php:`doktype`
or their configured backend layout.

The event provides the following member properties:

`$searchParts`: 
    The search parts to be used for filtering
`$searchUids`: 
    The uids to be used for filtering by a special search part, which 
    is added by Core always after listener evaluation
`$searchPhrase`
    The complete search phrase, as entered by the user
`$queryBuilder`: 
    The current :php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` 
    instance to provide context and to be used to create search parts

..  important::

    The :php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` instance is provided solely 
    for context and to simplify the creation of
    search parts by using the :php-short:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder` 
    via :php:`QueryBuilder->expr()`. The instance itself **must not** be modified by listeners and 
    is not considered part of the public API. TYPO3 reserves the right to change the instance at 
    any time without prior notice.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, adds additional conditions to the filter.

..  code-block:: php
    :caption: my_extension/Classes/EventListener/MyEventListener.php

    use TYPO3\CMS\Backend\Tree\Repository\BeforePageTreeIsFilteredEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Database\Connection;

    final class MyEventListener
    {
        #[AsEventListener]
        public function removeFetchedPageContent(BeforePageTreeIsFilteredEvent $event): void
        {
            // Adds an additional UID to the filter
            $event->searchUids[] = 123;

            // Adds evaluation of doktypes to the filter
            if (preg_match('/doktype:([0-9]+)/i', $event->searchPhrase, $match)) {
                $doktype = $match[1];
                $event->searchParts = $event->searchParts->with(
                    $event->queryBuilder->expr()->eq('doktype', $event->queryBuilder->createNamedParameter($doktype, Connection::PARAM_INT))
                );
            }
        }
    }

Impact
======

With the new PSR-14 event :php-short:`\TYPO3\CMS\Backend\Tree\Repository\BeforePageTreeIsFilteredEvent`, custom
functionality and advanced evaluations can now be added to enhance the page
tree filter.

..  index:: Backend, PHP-API, ext:backend
