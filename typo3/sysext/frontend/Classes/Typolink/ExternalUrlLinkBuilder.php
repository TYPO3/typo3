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
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;

/**
 * Builds a TypoLink to an external URL
 */
class ExternalUrlLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        return [
            $this->processUrl(UrlProcessorInterface::CONTEXT_EXTERNAL, htmlspecialchars_decode($linkDetails['url']), $conf),
            $this->parseFallbackLinkTextIfLinkTextIsEmpty($linkText, $linkDetails['url']),
            $target ?: $this->resolveTargetAttribute($conf, 'extTarget', true, $this->getTypoScriptFrontendController()->extTarget)
        ];
    }
}
