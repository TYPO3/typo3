.. include:: /Includes.rst.txt

.. _feature-94499-1675615570:

======================================================================================
Feature: #94499 - Provide additional `PageTypeSource` auto-create redirect source type
======================================================================================

See :issue:`94499`

Description
===========

A new source type implementation based on :php:`\TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface`
is added, providing the page type number as an additional value. The main use case
for this source type is to provide additional source types where the source host
and path are taken from a fully built URI before the page slug change occurred for
a specific page type. That avoids the need for extension authors to implement a
custom source type for the same task, and instead provides a custom event
listener to build sources for non-zero page types. Sources can be added by
implementing an event listener for
:ref:`\\TYPO3\\CMS\\Redirects\\Event\\SlugRedirectChangeItemCreatedEvent <feature-99746-1675059434>`.

..  note::

    TYPO3 Core implements a listener to add a :php:`PageTypeSource` for page
    type `0` with :ref:`AddPageTypeZeroSource Event Listener <feature-94499-1675615684>`.
    This source class can be re-used, if page type related sources should be added
    for non-zero page types.

This class features the following methods:

-   :php:`getHost()`: Returns the source host for the redirect
-   :php:`getPath()`: Returns the source path for the redirect
-   :php:`getPageType()`: Returns the page type used to provide the host/path
-   :php:`getTargetLinkParameters()`: Returns the link parameters which should
    be used to create the target based on `t3://` syntax

Values can be set only by the constructor.

Example:
--------

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/custom-page-type-redirect'
          after: 'redirects-add-page-type-zero-source'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Core\Context\Context;
    use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
    use TYPO3\CMS\Core\Routing\RouterInterface;
    use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
    use TYPO3\CMS\Core\Site\Entity\Site;
    use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
    use TYPO3\CMS\Redirects\RedirectUpdate\PageTypeSource;
    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;

    final class MyEventListener
    {
        protected array $customPageTypes = [ 1234, 169999 ];

        public function __invoke(
            SlugRedirectChangeItemCreatedEvent $event
        ): void {
            $changeItem = $event->getSlugRedirectChangeItem();
            $sources = $changeItem->getSourcesCollection()->all();

            foreach ($this->customPageTypes as $pageType) {
                try {
                    $pageTypeSource = $this->createPageTypeSource(
                        $changeItem->getPageId(),
                        $pageType,
                        $changeItem->getSite(),
                        $changeItem->getSiteLanguage(),
                    );
                    if ($pageTypeSource === null) {
                        continue;
                    }
                } catch (UnableToLinkToPageException) {
                    // Could not properly link to page. Continue to next page type
                    continue;
                }

                if ($this->isDuplicate($pageTypeSource, ...$sources)) {
                    // not adding duplicate,
                    continue;
                }

                $sources[] = $pageTypeSource;
            }

            // update sources
            $changeItem = $changeItem->withSourcesCollection(
                new RedirectSourceCollection(
                    ...array_values($sources)
                )
            );

            // update change item with updated sources
            $event->setSlugRedirectChangeItem($changeItem);
        }

        private function isDuplicate(
            PageTypeSource $pageTypeSource,
            RedirectSourceInterface ...$sources
        ): bool {
            foreach ($sources as $existingSource) {
                if ($existingSource instanceof PageTypeSource
                    && $existingSource->getHost() === $pageTypeSource->getHost()
                    && $existingSource->getPath() === $pageTypeSource->getPath()
                ) {
                    // we do not check for the type, as that is irrelevant. Same
                    // host+path tuple would lead to duplicated redirects if
                    // type differs.
                    return true;
                }
            }
            return false;
        }

        private function createPageTypeSource(
            int $pageUid,
            int $pageType,
            Site $site,
            SiteLanguage $siteLanguage
        ): ?PageTypeSource {
            if ($pageType === 0) {
                // pageType 0 is handled by \TYPO3\CMS\Redirects\EventListener\AddPageTypeZeroSource
                return null;
            }

            try {
                $context = GeneralUtility::makeInstance(Context::class);
                $uri = $site->getRouter($context)->generateUri(
                    $pageUid,
                    [
                        '_language' => $siteLanguage,
                        'type' => $pageType,
                    ],
                    '',
                    RouterInterface::ABSOLUTE_URL
                );
                return new PageTypeSource(
                    $uri->getHost() ?: '*',
                    $uri->getPath(),
                    $pageType,
                    [
                        'type' => $pageType,
                    ],
                );
            } catch (\InvalidArgumentException | InvalidRouteArgumentsException $e) {
                throw new UnableToLinkToPageException(
                    sprintf(
                        'The link to the page with ID "%d" and type "%d" could not be generated: %s',
                        $pageUid,
                        $pageType,
                        $e->getMessage()
                    ),
                    1675618235,
                    $e
                );
            }
        }
    }



Impact
======

The new :php:`PageTypeSource` can be used to provide additional sources, for example,
based on custom page types using full URI building, which would take
configured PageTypeSuffix decorators into account. For page type `0` (default), the Core
implements an event listener which adds the source based on this source class for
page type `0` with :ref:`AddPageTypeZeroSource event listener <feature-94499-1675615684>`.

.. index:: PHP-API, ext:redirects
