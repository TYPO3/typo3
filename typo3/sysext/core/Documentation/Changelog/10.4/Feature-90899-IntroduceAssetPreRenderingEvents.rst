.. include:: /Includes.rst.txt

.. _changelog-Feature-90899-IntroduceAssetPreRenderingEvents:

==============================================================
Feature: #90899 - Introduce AssetRenderer pre-rendering events
==============================================================

See :issue:`90899`

Description
===========

AssetRenderer is amended by two events which allow post-processing of
AssetCollector assets.

These new PSR-14 events are introduced:

* :php:`\TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent`
* :php:`\TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent`

Both stem fom the abstract base class
:php:`\TYPO3\CMS\Core\Page\Event\AbstractBeforeAssetRenderingEvent` and provide
these public methods:

* :php:`getAssetCollector(): AssetCollector`
* :php:`isInline(): bool`
* :php:`isPriority(): bool`

:php:`inline` and :php:`priority` refer to how the asset was registered with
:ref:`AssetCollector <changelog-Feature-90522-IntroduceAssetCollector>`.

The events are fired exactly once for every combination of
:php:`inline`/:php:`priority` before the corresponding section of JS/CSS assets
is rendered by the AssetRenderer.

To make the events easier to use, the :php:`AssetCollector::get*()` methods
have gotten an optional parameter :html:`?bool $priority = null` which when given a
boolean only returns assets of the given priority.


.. note::

   post-processing functionality for assets registered via
   TypoScript :typoscript:`page.include...` or the :php:`PageRenderer::add*()`
   functions are still provided by these hooks:

   * :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler']`
   * :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler']`
   * :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler']`
   * :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler']`
   * :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']`

   Assets registered with the AssetCollector (and output through the
   AssetRenderer) are not included in those.


Example
=======

As an example let's make sure jQuery is included in a specific version and
from a CDN.

.. rst-class:: bignums

   1. Register our listeners

      :file:`Configuration/Services.yaml`

      .. code-block:: yaml

         services:
            MyVendor\MyExt\EventListener\AssetRenderer\LibraryVersion:
             tags:
               - name: event.listener
                 identifier: 'myExt/LibraryVersion'
                 event: TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent


   2. Implement Listener to enforce a library version or CDN URI

      .. code-block:: php

         namespace MyVendor\MyExt\EventListener\AssetRenderer;

         use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;

         /**
          * If a library has been registered, it is made sure that it is loaded
          * from the given URI
          */
         class LibraryVersion
         {
             protected $libraries = [
                 'jquery' => 'https://code.jquery.com/jquery-3.4.1.min.js',
             ];

             public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
             {
                 if ($event->isInline()) {
                     return;
                 }

                 foreach ($this->libraries as $library => $source) {
                     $asset = $event->getAssetCollector()->getJavaScripts($event->isPriority())
                     // if it was already registered
                     if ($asset[$library] ?? false) {
                         // we set our authoritative version
                         $event->getAssetCollector()->addJavaScript($library, $source);
                     }
                 }
             }
         }


Impact
======

Existing installations are not affected.

If using the AssetCollector API, these new events should be used for asset
postprocessing.

Related
=======

- :ref:`changelog-Feature-90522-IntroduceAssetCollector`

.. index:: PHP-API, ext:core
