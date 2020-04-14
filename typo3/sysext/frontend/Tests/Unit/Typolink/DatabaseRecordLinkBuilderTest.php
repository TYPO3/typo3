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

use Prophecy\Argument;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for DatabaseRecordLinkBuilderTest
 */
class DatabaseRecordLinkBuilderTest extends UnitTestCase
{

    /**
     * Dataprovider with different parameter configurations
     *
     * @return array
     */
    public function attributesSetInRecordLinkOverwriteConfiguredAttributesDataProvider(): array
    {
        return [
            'attributes from db overwrite config' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1 dbTarget dbClass dbTitle',
                '27 dbTarget dbClass dbTitle',
            ],
            'no attributes from db - config is taken' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1',
                '27 tsTarget tsClass tsTitle',
            ],
            'mixed: target from db' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1 dbTarget',
                '27 dbTarget tsClass tsTitle',
            ],
            'mixed: class from db' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1 - dbClass',
                '27 tsTarget dbClass tsTitle',
            ],
            'mixed: title from db' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1 - - dbTitle',
                '27 tsTarget tsClass dbTitle',
            ],
            'mixed: target and title from db' => [
                '27 tsTarget tsClass tsTitle',
                't3://record?identifier=tx_news&uid=1 dbTarget - dbTitle',
                '27 dbTarget tsClass dbTitle',
            ],
            'mixed: target and title from db, no class set' => [
                '27 tsTarget - tsTitle',
                't3://record?identifier=tx_news&uid=1 dbTarget - dbTitle',
                '27 dbTarget - dbTitle',
            ],
            'mixed: title from db, no config set' => [
                '27',
                't3://record?identifier=tx_news&uid=1 - - dbTitle',
                '27 - - dbTitle',
            ],
            'no attributes configured' => [
                '27',
                't3://record?identifier=tx_news&uid=1',
                '27',
            ],

        ];
    }

    /**
     * @test
     *
     * Tests showing that values set in the link record directly will overwrite those configured
     * in the default link handler configuration
     *
     * Note that the TypolinkCodecService is not mocked on purpose to get the full unit tested.
     *
     * @dataProvider attributesSetInRecordLinkOverwriteConfiguredAttributesDataProvider
     * @param string $parameterFromTypoScript
     * @param string $parameterFromDb
     * @param string $expectedParameter
     */
    public function attributesSetInRecordLinkOverwriteConfiguredAttributes(string $parameterFromTypoScript, string $parameterFromDb, string $expectedParameter): void
    {
        $confFromDb = [
            'parameter' => $parameterFromDb,
        ];
        $extractedLinkDetails = [
            'identifier' => 'tx_news',
            'uid' => '1',
            'type' => 'record',
            'typoLinkParameter' => 't3://record?identifier=tx_news&uid=1',
        ];
        $typoScriptConfig = [
            'config.' => [
                'recordLinks.' => [
                    'tx_news.' =>
                        [
                            'forceLink' => '0',
                            'typolink.' =>
                                [
                                    'parameter' => $parameterFromTypoScript,
                                    'additionalParams' => '&tx_news_pi1[news]={field:uid}',
                                    'additionalParams.' =>
                                        [
                                            'insertData' => '1',
                                        ],
                                ],
                        ],
                ],
            ],
        ];
        $pageTsConfig = [
            'TCEMAIN.' =>
                [
                    'linkHandler.' =>
                        [

                            'tx_news.' =>
                                [
                                    'handler' => RecordLinkHandler::class,
                                    'label' => 'News',
                                    'configuration.' =>
                                        [
                                            'table' => 'tx_news_domain_model_news',
                                        ],
                                    'scanAfter' => 'page',
                                ],
                        ],
                ],

        ];
        $target = '';
        $linkText = 'Test Link';

        $expectedConfiguration = [
            'parameter' => $expectedParameter,
            'additionalParams' => '&tx_news_pi1[news]={field:uid}',
            'additionalParams.' => ['insertData' => '1'],
        ];

        // Arrange
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $templateService = $this->prophesize(TemplateService::class);
        $pageRepository = $this->prophesize(PageRepository::class);
        $cObj = $this->prophesize(ContentObjectRenderer::class);

        $GLOBALS['TSFE'] = $tsfe->reveal();
        $tsfe->tmpl = $templateService->reveal();
        $tsfe->tmpl->setup = $typoScriptConfig;
        $tsfe->sys_page = $pageRepository->reveal();
        GeneralUtility::addInstance(ContentObjectRenderer::class, $cObj->reveal());

        $pageRepository->checkRecord('tx_news_domain_model_news', 1)->willReturn(
            [
                'uid' => '1',
            ]
        );

        $cObj->start(Argument::cetera())->shouldBeCalled();
        $cObj->typoLink(Argument::cetera())->shouldBeCalled();

        $tsfe->getPagesTSconfig()->willReturn($pageTsConfig);

        // Act
        $databaseRecordLinkBuilder = new DatabaseRecordLinkBuilder($cObj->reveal());
        try {
            $databaseRecordLinkBuilder->build($extractedLinkDetails, $linkText, $target, $confFromDb);
        } catch (UnableToLinkException $exception) {
            // Assert
            $cObj->typoLink($linkText, $expectedConfiguration)->shouldHaveBeenCalled();
        }
    }
}
