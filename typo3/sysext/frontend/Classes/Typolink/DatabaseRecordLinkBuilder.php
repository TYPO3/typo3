<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Typolink;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Builds a TypoLink to a database record
 */
class DatabaseRecordLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $pageTsConfig = $tsfe->getPagesTSconfig();
        $configurationKey = $linkDetails['identifier'] . '.';
        $configuration = $tsfe->tmpl->setup['config.']['recordLinks.'];
        $linkHandlerConfiguration = $pageTsConfig['TCEMAIN.']['linkHandler.'];

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

        if ($configuration[$configurationKey]['forceLink']) {
            $record = $tsfe->sys_page->getRawRecord($linkHandlerConfiguration['table'], $linkDetails['uid']);
        } else {
            $record = $tsfe->sys_page->checkRecord($linkHandlerConfiguration['table'], $linkDetails['uid']);
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
        $typoScriptConfiguration = array_replace_recursive($conf, $typoScriptConfiguration);

        // Build the full link to the record
        $localContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $localContentObjectRenderer->start($record, $linkHandlerConfiguration['table']);
        $localContentObjectRenderer->parameters = $this->contentObjectRenderer->parameters;
        $link = $localContentObjectRenderer->typoLink($linkText, $typoScriptConfiguration);

        $this->contentObjectRenderer->lastTypoLinkLD = $localContentObjectRenderer->lastTypoLinkLD;
        $this->contentObjectRenderer->lastTypoLinkUrl = $localContentObjectRenderer->lastTypoLinkUrl;
        $this->contentObjectRenderer->lastTypoLinkTarget = $localContentObjectRenderer->lastTypoLinkTarget;

        // nasty workaround so typolink stops putting a link together, there is a link already built
        throw new UnableToLinkException(
            '',
            1491130170,
            null,
            $link
        );
    }
}
