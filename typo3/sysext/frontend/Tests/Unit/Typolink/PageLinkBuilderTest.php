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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageLinkBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getQueryArgumentsExcludesParameters(): void
    {
        $queryParameters = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key31' => 'value31',
                'key32' => [
                    'key321' => 'value321',
                    'key322' => 'value322',
                ],
            ],
        ];
        $request = new ServerRequest('https://example.com');
        $request = $request->withQueryParams($queryParameters);
        $request = $request->withAttribute('routing', new PageArguments(1, '', $queryParameters, [], []));
        $configuration = [];
        $configuration['exclude'] = [];
        $configuration['exclude'][] = 'key1';
        $configuration['exclude'][] = 'key3[key31]';
        $configuration['exclude'][] = 'key3[key32][key321]';
        $configuration['exclude'] = implode(',', $configuration['exclude']);
        $expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2&key3[key32][key322]=value322');
        $GLOBALS['TSFE'] = new \stdClass();
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);
        $subject = $this->getAccessibleMock(PageLinkBuilder::class, ['dummy'], [], '', false);
        $subject->_set('contentObjectRenderer', $cObj);
        $actualResult = $subject->_call('getQueryArguments', 'untrusted', $configuration);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Encodes square brackets in URL.
     *
     * @return string
     */
    private function rawUrlEncodeSquareBracketsInUrl(string $string): string
    {
        return str_replace(['[', ']'], ['%5B', '%5D'], $string);
    }
}
