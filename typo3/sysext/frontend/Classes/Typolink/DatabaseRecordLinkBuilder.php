<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Frontend\Typolink;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\BeforeDatabaseRecordLinkResolvedEvent;

/**
 * Builds a TypoLink to a database record
 */
class DatabaseRecordLinkBuilder implements TypolinkBuilderInterface
{
    public function __construct(
        private readonly TcaSchemaFactory $schemaFactory,
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $runtimeCache,
        private readonly TypoLinkCodecService $typoLinkCodecService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function buildLink(
        array $linkDetails,
        array $configuration,
        ServerRequestInterface $request,
        string $linkText = '',
    ): LinkResultInterface {
        $pageTsConfig = $this->getPageTsConfig($request);
        $configurationKey = $linkDetails['identifier'] . '.';
        $typoScriptArray = $request->getAttribute('frontend.typoscript')?->getSetupArray() ?? [];
        $typoScriptLinkHandlerConfiguration = $typoScriptArray['config.']['recordLinks.'] ?? [];
        $linkHandlerConfiguration = $pageTsConfig['TCEMAIN.']['linkHandler.'] ?? [];

        if (!isset($typoScriptLinkHandlerConfiguration[$configurationKey], $linkHandlerConfiguration[$configurationKey])) {
            throw new UnableToLinkException(
                'Configuration how to link "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989149,
                null,
                $linkText
            );
        }
        $typoScriptConfiguration = $typoScriptLinkHandlerConfiguration[$configurationKey]['typolink.'];
        $linkHandlerConfiguration = $linkHandlerConfiguration[$configurationKey]['configuration.'];
        $databaseTable = (string)($linkHandlerConfiguration['table'] ?? '');

        $event = $this->eventDispatcher->dispatch(
            new BeforeDatabaseRecordLinkResolvedEvent(
                $linkDetails,
                $databaseTable,
                $typoScriptLinkHandlerConfiguration,
                $linkHandlerConfiguration,
                $request
            )
        );
        $record = $event->record;
        if ($record === null) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            if ($typoScriptLinkHandlerConfiguration[$configurationKey]['forceLink'] ?? false) {
                $record = $pageRepository->getRawRecord($databaseTable, (int)$linkDetails['uid']);
            } else {
                $record = $pageRepository->checkRecord($databaseTable, (int)$linkDetails['uid']);
                $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
                if (is_array($record) && $this->schemaFactory->has($databaseTable)) {
                    $schema = $this->schemaFactory->get($databaseTable);
                    if ($schema->isLanguageAware()) {
                        $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField(
                        )->getName();
                        $languageIdOfRecord = $record[$languageField];
                        // If a record is already in a localized version OR if the record is set to "All Languages"
                        // we allow the generation of the link
                        if ($languageIdOfRecord === 0 && $languageAspect->doOverlays()) {
                            $overlay = $pageRepository->getLanguageOverlay(
                                $databaseTable,
                                $record,
                                $languageAspect
                            );
                            // If the record is not translated (overlays enabled), even though it should have been done
                            // We avoid linking to it
                            if (!isset($overlay['_LOCALIZED_UID'])) {
                                $record = 0;
                            }
                        }
                    }
                }
            }
        }
        if ($record === null) {
            throw new UnableToLinkException(
                'Record not found for "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989659,
                null,
                $linkText
            );
        }

        // Unset the parameter part of the given TypoScript configuration while keeping
        // config that has been set in addition.
        unset($configuration['parameter.']);

        $parameterFromDb = $this->typoLinkCodecService->decode((string)($configuration['parameter'] ?? ''));
        unset($parameterFromDb['url']);
        $parameterFromTypoScript = $this->typoLinkCodecService->decode((string)($typoScriptConfiguration['parameter'] ?? ''));
        $parameter = array_replace_recursive($parameterFromTypoScript, array_filter($parameterFromDb));
        $typoScriptConfiguration['parameter'] = $this->typoLinkCodecService->encode($parameter);

        $typoScriptConfiguration = array_replace_recursive($configuration, $typoScriptConfiguration);

        if (!empty($linkDetails['fragment'])) {
            $typoScriptConfiguration['section'] = $linkDetails['fragment'];
        }
        // Build the full link to the record by calling LinkFactory again ("inception")
        $localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $localContentObjectRenderer->setRequest($request);
        $localContentObjectRenderer->start($record, $databaseTable);
        $localContentObjectRenderer->parameters = $request->getAttribute('currentContentObject')->parameters ?? [];
        return $localContentObjectRenderer->createLink($linkText, $typoScriptConfiguration);
    }

    /**
     * Helper method to calculate pageTsConfig in frontend scope, we can't use BackendUtility::getPagesTSconfig() here.
     */
    protected function getPageTsConfig(ServerRequestInterface $request): array
    {
        if (!ApplicationType::fromRequest($request)->isFrontend()) {
            return [];
        }
        $pageInformation = $request->getAttribute('frontend.page.information');
        $id = $pageInformation->getId();
        $fullRootLine = $pageInformation->getRootLine();
        $pageTsConfig = $this->runtimeCache->get('pageTsConfig-' . $id);
        if ($pageTsConfig instanceof PageTsConfig) {
            return $pageTsConfig->getPageTsConfigArray();
        }
        ksort($fullRootLine);
        $site = $request->getAttribute('site') ?? new NullSite();
        $pageTsConfig = GeneralUtility::makeInstance(PageTsConfigFactory::class)->create($fullRootLine, $site);
        $this->runtimeCache->set('pageTsConfig-' . $id, $pageTsConfig);
        return $pageTsConfig->getPageTsConfigArray();
    }
}
