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

namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\Controller;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->subject->_set('context', new Context());
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $this->subject->sys_page = $pageRepository;

        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->getMock();
        $this->subject->_set('pageRenderer', $pageRenderer);
    }

    public function requireCacheHashValidateRelevantParametersDataProvider(): array
    {
        return [
            'no extra params' => [
                [],
                false,
            ],
            'with required param' => [
                [
                    'abc' => 1,
                ],
                true,
            ],
            'with required params' => [
                [
                    'abc' => 1,
                    'abcd' => 1,
                ],
                true,
            ],
            'with not required param' => [
                [
                    'fbclid' => 1,
                ],
                false,
            ],
            'with not required params' => [
                [
                    'fbclid' => 1,
                    'gclid' => 1,
                    'foo' => [
                        'bar' => 1,
                    ],
                ],
                false,
            ],
            'with combined params' => [
                [
                    'abc' => 1,
                    'fbclid' => 1,
                ],
                true,
            ],
            'with multiple combined params' => [
                [
                    'abc' => 1,
                    'fbclid' => 1,
                    'abcd' => 1,
                    'gclid' => 1
                ],
                true,
            ]
        ];
    }

    /**
     * @test
     *
     * @dataProvider requireCacheHashValidateRelevantParametersDataProvider
     * @param array $remainingArguments
     * @param bool $expected
     */
    public function requireCacheHashValidateRelevantParameters(array $remainingArguments, bool $expected): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['gclid', 'fbclid', 'foo[bar]'];

        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->subject->_set('pageArguments', new PageArguments(1, '0', ['tx_test' => 1], ['tx_test' => 1], $remainingArguments));

        if ($expected) {
            $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
            $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
            GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
            static::expectException(ImmediateResponseException::class);
        }
        $this->subject->reqCHash();
    }
}
