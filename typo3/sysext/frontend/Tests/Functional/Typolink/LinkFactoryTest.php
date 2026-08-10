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

namespace TYPO3\CMS\Frontend\Tests\Functional\Typolink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function externalLinkWithDefaultRelAttribute(): void
    {
        $subject = $this->get(LinkFactory::class);
        $cObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObject->setRequest($this->buildRequestWithTypoScriptConfigArray([]));

        $result = $subject->create('link text', ['parameter' => 'https://example.com _blank'], $cObject);

        self::assertEquals('noreferrer', $result->getAttribute('rel'));
    }

    #[Test]
    #[TestWith(['noopener', 'noopener'], 'set allowed configuration "noopener"')]
    #[TestWith(['invalid', 'noreferrer'], 'fallback to default "noreferrer" for invalid configuration')]
    public function externalLinkWithGlobalRelAttribute(string $linkSecurityRelValue, string $expectedAttribute): void
    {
        $subject = $this->get(LinkFactory::class);
        $cObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObject->setRequest($this->buildRequestWithTypoScriptConfigArray(['linkSecurityRelValue' => $linkSecurityRelValue]));

        $result = $subject->create('link text', ['parameter' => 'https://example.com _blank'], $cObject);

        self::assertEquals($expectedAttribute, $result->getAttribute('rel'));
    }

    #[Test]
    #[TestWith(['noreferrer'])]
    #[TestWith(['noopener'])]
    public function externalLinkWithValidRelAttribute(string $typoLinkAdditionalRelAttribute): void
    {
        $subject = $this->get(LinkFactory::class);
        $cObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObject->setRequest($this->buildRequestWithTypoScriptConfigArray([]));

        $result = $subject->create('link text', [
            'parameter' => 'https://example.com _blank',
            'ATagParams' => 'rel="' . $typoLinkAdditionalRelAttribute . '"',
        ], $cObject);

        self::assertEquals($typoLinkAdditionalRelAttribute, $result->getAttribute('rel'));
    }

    #[Test]
    public function externalLinkWithAnyOtherRelAttribute(): void
    {
        $subject = $this->get(LinkFactory::class);
        $cObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObject->setRequest($this->buildRequestWithTypoScriptConfigArray([]));

        $result = $subject->create('link text', [
            'parameter' => 'https://example.com _blank',
            'ATagParams' => 'rel="something"',
        ], $cObject);

        // expect the attribute from typolink configuration + the default security attribute
        self::assertEquals('something noreferrer', $result->getAttribute('rel'));
    }

    /**
     * Build and return a ServerRequest object where attribute "frontend.typoscript" exists
     * and has the given array set as $configArray
     */
    protected function buildRequestWithTypoScriptConfigArray(array $configArray): ServerRequest
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray($configArray);
        return new ServerRequest()->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
