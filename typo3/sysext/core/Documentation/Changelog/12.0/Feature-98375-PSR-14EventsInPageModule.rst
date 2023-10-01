.. include:: /Includes.rst.txt

.. _feature-98375-1663598746:

==============================================
Feature: #98375 - PSR-14 events in Page Module
==============================================

See :issue:`98375`

Description
===========

Three new PSR-14 events have been added to TYPO3's page module to modify
the preparation and rendering of content elements:

* :php:`TYPO3\CMS\Backend\View\Event\IsContentUsedOnPageLayoutEvent`
* :php:`TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForContentEvent`
* :php:`TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent`

They are drop-in replacement to the removed hooks:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']`

Example for :php:`IsContentUsedOnPageLayoutEvent`
-------------------------------------------------

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

      MyVendor\MyExtension\Listener\ContentUsedOnPage:
        tags:
          - name: event.listener
            identifier: 'my-extension/view/content-used-on-page'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Listener/ContentUsedOnPage.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Listener;

    use TYPO3\CMS\Backend\View\Event\IsContentUsedOnPageLayoutEvent;

    final class ContentUsedOnPage
    {
        public function __invoke(IsContentUsedOnPageLayoutEvent $event): void
        {
            // Get the current record from the event.
            $record = $event->getRecord();

            // This code will be your domain logic to indicate if content
            // should be hidden in the page module.
            if ((int)($record['colPos'] ?? 0) === 999
                && !empty($record['tx_myext_content_parent'])
            ) {
                // Flag the current element as not used. Set it to true, if you
                // want to flag it as used and hide it from the page module.
                $event->setUsed(false);
            }
        }
    }

Example for :php:`ModifyDatabaseQueryForContentEvent`
-----------------------------------------------------

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

      MyVendor\MyExtension\Listener\ModifyDatabaseQueryForContent:
        tags:
          - name: event.listener
            identifier: 'my-extension/view/modify-database-query-for-content'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Listener/ModifyDatabaseQueryForContent.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Listener;

    use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForContentEvent;
    use TYPO3\CMS\Core\Database\Connection;

    final class ModifyDatabaseQueryForContent
    {
        public function __invoke(ModifyDatabaseQueryForContentEvent $event): void
        {
            // early return if we do not need to react
            if ($event->getTable() !== 'tt_content') {
                return;
            }

            // Retrieve QueryBuilder instance from event
            $queryBuilder = $event->getQueryBuilder();

            // Add an additional condition to the QueryBuilder for the table
            // Note: This is only an example, modify the QueryBuilder instance
            //       here to your needs.
            $queryBuilder = $queryBuilder->andWhere(
                $queryBuilder->expr()->neq(
                    'some_field',
                    $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                )
            );

            // set updated QueryBuilder to event
            $event->setQueryBuilder($queryBuilder);
        }
    }

Example :php:`PageContentPreviewRenderingEvent`
-----------------------------------------------

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

      MyVendor\MyExtension\Listener\PageContentPreviewRendering:
        tags:
          - name: event.listener
            identifier: 'my-extension/view/page-content-preview-rendering'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Listener/PageContentPreviewRendering.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Listener;

    use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;

    final class PageContentPreviewRendering
    {
        public function __invoke(PageContentPreviewRenderingEvent): void
        {
            $tableName = $event->getTable();
            $record = $event->getRecord();

            // early return if we do not need to react
            if ($event->getTable() !== 'tt_content'
                || ((string)($record['CType'] ?? '') !== 'my-content-element'
               ) {
                return;
            }

            // Create custom preview content
            $previewContent = sprintf(
                '<div class="alert alert-notice">No preview available for %s:%s</div>',
                $event->getTable(),
                ($event->getRecord()['uid'] ?? 0)
            );

            // Set (override) preview content with custom content.
            $event->setPreviewContent($previewContent);
        }
    }

Impact
======

Use :php:`IsContentUsedOnPageLayoutEvent` to identify if a content has been used
in a column that isn't on a Backend Layout.

Use :php:`ModifyDatabaseQueryForContentEvent` to filter out certain content elements
from being shown in the Page Module.

Use :php:`PageContentPreviewRenderingEvent` to ship an alternative rendering for
a specific content type or to manipulate the content elements' record data.

.. index:: Backend, PHP-API, ext:backend
