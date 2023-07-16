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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Builds a TypoLink to a database record
 */
class DatabaseRecordLinkBuilder extends AbstractTypolinkBuilder
{
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $request = $this->contentObjectRenderer->getRequest();
        $pageTsConfig = $this->getPageTsConfig($tsfe, $request);
        $configurationKey = $linkDetails['identifier'] . '.';
        $typoScriptArray = $request->getAttribute('frontend.typoscript')?->getSetupArray() ?? [];
        $configuration = $typoScriptArray['config.']['recordLinks.'] ?? [];
        $linkHandlerConfiguration = $pageTsConfig['TCEMAIN.']['linkHandler.'] ?? [];

        if (!isset($configuration[$configurationKey], $linkHandlerConfiguration[$configurationKey])) {
            throw new UnableToLinkException(
                'Configuration how to link "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989149,
                null,
                $linkText
            );
        }
        $typoScriptConfiguration = $configuration[$configurationKey]['typolink.'];
        $linkHandlerConfiguration = $linkHandlerConfiguration[$configurationKey]['configuration.'];
        $databaseTable = $linkHandlerConfiguration['table'];

        if ($configuration[$configurationKey]['forceLink'] ?? false) {
            $record = $tsfe->sys_page->getRawRecord($databaseTable, $linkDetails['uid']);
        } else {
            $record = $tsfe->sys_page->checkRecord($databaseTable, $linkDetails['uid']);
            $languageAspect = $tsfe->getContext()->getAspect('language');
            $languageField = (string)($GLOBALS['TCA'][$databaseTable]['ctrl']['languageField'] ?? '');

            if (is_array($record) && $languageField !== '') {
                $languageIdOfRecord = $record[$languageField];
                // If a record is already in a localized version OR if the record is set to "All Languages"
                // we allow the generation of the link
                if ($languageIdOfRecord === 0 && $languageAspect->doOverlays()) {
                    $overlay = $tsfe->sys_page->getLanguageOverlay(
                        $databaseTable,
                        $record,
                        $languageAspect
                    );
                    // If the record is not translated (overlays enabled), even though it should have been done
                    // We avoid linking to it
                    if (empty($overlay['_LOCALIZED_UID'])) {
                        $record = 0;
                    }
                }
            }
        }
        if ($record === 0) {
            throw new UnableToLinkException(
                'Record not found for "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.',
                1490989659,
                null,
                $linkText
            );
        }

        // Unset the parameter part of the given TypoScript configuration while keeping
        // config that has been set in addition.
        unset($conf['parameter.']);

        $typoLinkCodecService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $parameterFromDb = $typoLinkCodecService->decode($conf['parameter'] ?? '');
        unset($parameterFromDb['url']);
        $parameterFromTypoScript = $typoLinkCodecService->decode($typoScriptConfiguration['parameter'] ?? '');
        $parameter = array_replace_recursive($parameterFromTypoScript, array_filter($parameterFromDb));
        $typoScriptConfiguration['parameter'] = $typoLinkCodecService->encode($parameter);

        $typoScriptConfiguration = array_replace_recursive($conf, $typoScriptConfiguration);

        if (!empty($linkDetails['fragment'])) {
            $typoScriptConfiguration['section'] = $linkDetails['fragment'];
        }
        // Build the full link to the record by calling LinkFactory again ("inception")
        $request = $this->contentObjectRenderer->getRequest();
        $localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $localContentObjectRenderer->setRequest($request);
        $localContentObjectRenderer->start($record, $databaseTable);
        $localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        return $localContentObjectRenderer->createLink($linkText, $typoScriptConfiguration);
    }

    /**
     * Helper method to calculate pageTsConfig in frontend scope, we can't use BackendUtility::getPagesTSconfig() here.
     */
    protected function getPageTsConfig(TypoScriptFrontendController $tsfe, ServerRequestInterface $request): array
    {
        $id = $tsfe->id;
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $pageTsConfig = $runtimeCache->get('pageTsConfig-' . $id);
        if ($pageTsConfig instanceof PageTsConfig) {
            return $pageTsConfig->getPageTsConfigArray();
        }
        $fullRootLine = $tsfe->rootLine;
        ksort($fullRootLine);
        $site = $request->getAttribute('site') ?? new NullSite();
        $pageTsConfigFactory = GeneralUtility::makeInstance(PageTsConfigFactory::class);
        $pageTsConfig = $pageTsConfigFactory->create($fullRootLine, $site);
        $runtimeCache->set('pageTsConfig-' . $id, $pageTsConfig);
        return $pageTsConfig->getPageTsConfigArray();
    }
}
