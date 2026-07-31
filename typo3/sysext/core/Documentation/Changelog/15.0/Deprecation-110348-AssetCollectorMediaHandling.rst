..  include:: /Includes.rst.txt

..  _deprecation-110348-1785062400:

====================================================
Deprecation: #110348 - AssetCollector media handling
====================================================

See :issue:`110348`

Description
===========

The media related methods of :php:`\TYPO3\CMS\Core\Page\AssetCollector` have
been marked as deprecated and will be removed in TYPO3 v16.0:

-   :php:`AssetCollector->addMedia()`
-   :php:`AssetCollector->getMedia()`
-   :php:`AssetCollector->hasMedia()`
-   :php:`AssetCollector->removeMedia()`

Unlike JavaScript and stylesheet assets, collected media never contributed
anything to the rendered output. The registry is a leftover of the
:php:`$TSFE->imagesOnPage` property, which was moved to the
:php:`AssetCollector` in TYPO3 v10.3 (see :ref:`deprecation-90522`) and only
ever served the "Images on this page" section of the admin panel.

Collecting this information by having every image renderer report into a
central registry is fragile: it only ever covered the code paths that
remembered to call :php:`addMedia()`. TYPO3 dispatches
:php:`\TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent` for every
processed file, which is a complete and more precise source for the same
information.

Impact
======

Calling one of the deprecated methods triggers a PHP :php:`E_USER_DEPRECATED`
error.

TYPO3 Core does not populate the registry anymore. The two places that used
to do so no longer call :php:`addMedia()`:

-   :php:`\TYPO3\CMS\Extbase\Service\ImageService->applyProcessingInstructions()`
-   :php:`\TYPO3\CMS\Frontend\ContentObject\ImageContentObject` (the
    :typoscript:`IMAGE` content object)

Consequently :php:`AssetCollector->getMedia()` only returns entries that were
added by third-party code.

The admin panel keeps its "Images on this page" section. It now collects the
data through a PSR-14 listener on :php:`AfterFileProcessingEvent`, which also
reports correct file sizes for images stored in remote storages and adds the
image dimensions.

Affected installations
======================

Installations with extensions calling any of the deprecated methods, and
installations relying on Core populating the registry. Custom image
ViewHelpers or content objects that mirrored the Core behaviour by calling
:php:`addMedia()` are the most likely candidates.

The methods are not covered by the extension scanner: their names are too
generic to be matched reliably.

Migration
=========

Remove calls to :php:`addMedia()` and :php:`removeMedia()` — they serve no
rendering purpose.

To collect the images processed during a request, register a PSR-14 event
listener instead. This works regardless of whether an image is rendered
through Fluid, TypoScript or custom code:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
    use TYPO3\CMS\Core\SingletonInterface;

    final class MyImageCollector implements SingletonInterface
    {
        private array $images = [];

        #[AsEventListener('my-extension/collect-images')]
        public function collect(AfterFileProcessingEvent $event): void
        {
            $processedFile = $event->getProcessedFile();
            $publicUrl = $processedFile->getPublicUrl();
            if ($publicUrl !== null) {
                $this->images[$publicUrl] = $processedFile;
            }
        }

        public function getImages(): array
        {
            return $this->images;
        }
    }

Note that the event fires for every processed file of a request, including
files that are processed but never emitted into the output.

..  index:: FAL, Frontend, PHP-API, NotScanned, ext:core
