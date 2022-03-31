.. include:: /Includes.rst.txt

===============================================================
Feature: #88921 - New PSR-14 events in the PageLayoutView class
===============================================================

See :issue:`88921`

Description
===========

Two new PSR-14 events have been added to the :php:`PageLayoutView` class.
Those events can be used to add content into any column of a BackendLayout.
You can use this for example to show some content in a column without a ``colPos`` assigned.

The event :php:`BeforeSectionMarkupGeneratedEvent` can be used to add content above
the content elements of the column. The event :php:`AfterSectionMarkupGeneratedEvent`
can be used to add content below the content elements of the column.

You can use business logic to show content in specific columns.
E.g. for displaying content only in columns without any ``colPos``
in the BackendLayout configuration.

Example how to register the event listener in your own extension:

:file:`EXT:my_extension/Configuration/Services.yaml`

.. code-block:: yaml

  services:
    Vendor\MyExtension\Backend\View\PageLayoutViewDrawEmptyColposContent:
      tags:
        - name: event.listener
          identifier: 'myColposListener'
          before: 'backend-empty-colpos'
          event:  TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent

With :yaml:`before` and :yaml:`after`, you can make sure your own listener is
executed before or after the given identifiers.

:file:`EXT:my_extension/Classes/Backend/View/PageLayoutViewDrawEmptyColposContent.php`

.. code-block:: php

   <?php
   namespace Vendor\MyExtension\Backend\View;

   class PageLayoutViewDrawEmptyColposContent
   {
      public function __invoke(AfterSectionMarkupGeneratedEvent $event): void
      {
         if (
             !isset($event->getColumnConfig()['colPos'])
             || trim($event->getColumnConfig()['colPos']) === ''
         ) {
            $content = $event->getContent();
            $content .= <<<EOD
               <div class="t3-page-ce-wrapper">
                  <div class="t3-page-ce">
                     <div class="t3-page-ce-header">Empty colpos</div>
                     <div class="t3-page-ce-body">
                        <div class="t3-page-ce-body-inner">
                           <div class="row">
                              <div class="col-xs-12">
                                 This column has no "colPos". This is only for display Purposes.
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            EOD;

            $event->setStopRendering(true);
            $event->setContent($content);
         }
      }
   }

With the :php:`$event->setStopRendering()` method,
you can make sure that no other listeners are triggered after the current listener.

.. index:: ext:backend, PHP-API
