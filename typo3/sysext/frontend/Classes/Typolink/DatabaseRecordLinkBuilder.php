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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Builds a TypoLink to a database record
 */
class DatabaseRecordLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $pageTsConfig = $tsfe->getPagesTSconfig();
        $configurationKey = $linkDetails['identifier'] . '.';
        $configuration = $tsfe->tmpl->setup['config.']['recordLinks.'] ?? [];
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

            if ($languageAspect->doOverlays()) {
                $overlay = $tsfe->sys_page->getRecordOverlay(
                    $databaseTable,
                    $record,
                    $languageAspect
                );

                if (empty($overlay['_LOCALIZED_UID'])) {
                    $record = 0;
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
        // Build the full link to the record
        $request = $this->contentObjectRenderer->getRequest();
        $localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $localContentObjectRenderer->start($record, $databaseTable, $request);
        $localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        $link = $localContentObjectRenderer->typoLink($linkText, $typoScriptConfiguration);

        $this->contentObjectRenderer->lastTypoLinkLD = $localContentObjectRenderer->lastTypoLinkLD;
        $this->contentObjectRenderer->lastTypoLinkUrl = $localContentObjectRenderer->lastTypoLinkUrl;
        $this->contentObjectRenderer->lastTypoLinkTarget = $localContentObjectRenderer->lastTypoLinkTarget;
        $this->contentObjectRenderer->lastTypoLinkResult = $localContentObjectRenderer->lastTypoLinkResult;

        // nasty workaround so typolink stops putting a link together, there is a link already built
        throw new UnableToLinkException(
            '',
            1491130170,
            null,
            $link
        );
    }
}
