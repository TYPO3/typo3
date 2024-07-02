.. include:: /Includes.rst.txt

.. _feature-104221-1715591178:

========================================================================
Feature: #104221 - PSR-14 events for RTE <-> Persistence transformations
========================================================================

See :issue:`104221`

Description
===========

When using a RTE HTML content element, two transformations
take place within the TYPO3 backend:

*  From database: Fetching the current content from the database (`persistence`) and
   preparing it to be displayed inside the RTE HTML component.
*  To database: Retrieving the data returned by the RTE and preparing it to
   be persisted into the database.

This takes place in the :php:`TYPO3\CMS\Core\Html\RteHtmlParser` class, by utilizing the
methods :php:`transformTextForRichTextEditor` and :php:`transformTextForPersistence`.

With :issue:`96107` and :issue:`92992`, the former hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation']`
was removed, which took care of applying custom user-transformations. The suggested replacement
for this was to use the actual RTE YAML configuration and API like :php:`allowAttributes`.

Now, four PSR-14 Events are introduced to allow more granular control over data for
`persistence -> RTE` and `RTE -> persistence`. This allows developers to apply
more customized transformations, apart from the internal and API ones:

Modify data when saving RTE content to the database (persistence):

*  :php:`TYPO3\CMS\Core\Html\Event\BeforeTransformTextForPersistenceEvent`
*  :php:`TYPO3\CMS\Core\Html\Event\AfterTransformTextForPersistenceEvent`

Modify data when retrieving content from the database and pass to the RTE:

*  :php:`TYPO3\CMS\Core\Html\Event\BeforeTransformTextForRichTextEditorEvent`
*  :php:`TYPO3\CMS\Core\Html\Event\AfterTransformTextForRichTextEditorEvent`

All four events have the same structure (for now):

-  :php:`getHtmlContent()` - retrieve the current HTML content
-  :php:`setHtmlContent()` - used to set modifications of the HTML content
-  :php:`getInitialHtmlContent()` - retrieve the untampered initial HTML content
-  :php:`getProcessingConfiguration()` - retrieve processing configuration array

The event is meant to be used so that developers can change the HTML content
either `before` the internal TYPO3 modifications, or `after` those.

The `before` events are executed *before* TYPO3 applied any kind of internal transformations,
like for links. Event Listeners that want to modify output so that
TYPO3 additionally operates on that, should listen to those `before`-Events.

When Event Listeners want to perform on the final result, the corresponding `after`-Events
should be utilized.

Event listeners can use :php:`$value = $event->getHtmlContent()` to get the current contents,
apply changes to `$value` and then store the manipulated data via `$event->setHtmlContent($value)`,
see example:

Example
=======

An event listener class is constructed which will take an RTE input *TYPO3* and internally
store it in the database as *[tag:typo3]*. This could allow a content element data processor
in the frontend to handle this part of the content with for example internal glossary operations.

The workflow would be:

*  Editor enters "TYPO3" in the RTE instance.
*  When saving, this gets stored as "[tag:typo3]".
*  When the editor sees the RTE instance again, "[tag:typo3]" gets replaced to "TYPO3" again.
*  So: The editor will always only see "TYPO3" and not know how it is internally handled.
*  The frontend output receives "[tag:typo3]" and could do its own content element magic,
   other services accessing the database could also use the parseable representation.

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:MyExtension/Classes/EventListener/TransformListener.php

    <?php
    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;

    class TransformListener
    {
        /**
         * Transforms the current value the RTE delivered into a value that is stored (persisted) in the database.
         */
        #[AsEventListener('rtehtmlparser/modify-data-for-persistence')]
        public function modifyPersistence(AfterTransformTextForPersistenceEvent $event): void
        {
            $value = $event->getHtmlContent();
            $value = str_replace('TYPO3', '[tag:typo3]', $value);
            $event->setHtmlContent($value);
        }

        /**
         * Transforms the current persisted value into something the RTE can display
         */
        #[AsEventListener('rtehtmlparser/modify-data-for-richtexteditor')]
        public function modifyRichTextEditor(AfterTransformTextForRichTextEditorEvent $event): void
        {
            $value = $event->getHtmlContent();
            $value = str_replace('[tag:typo3]', 'TYPO3', $value);
            $event->setHtmlContent($value);
        }
    }


Impact
======

Using the new PSR-14 events

*  :php:`TYPO3\CMS\Core\Html\Event\BeforeTransformTextForPersistenceEvent`
*  :php:`TYPO3\CMS\Core\Html\Event\AfterTransformTextForPersistenceEvent`
*  :php:`TYPO3\CMS\Core\Html\Event\BeforeTransformTextForRichTextEditorEvent`
*  :php:`TYPO3\CMS\Core\Html\Event\AfterTransformTextForRichTextEditorEvent`

allows to apply custom transformations for `database <-> RTE content`
transformations.


.. index:: Backend, PHP-API, ext:core
