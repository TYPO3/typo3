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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use Prophecy\Argument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RedirectFinisherTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     * @dataProvider pageUidOptionForFinisherAcceptsVariousPageRepresentationsDataProvider
     * @param string|int|null $pageUid
     * @param int $expectedPage
     * @throws Exception
     */
    public function pageUidOptionForFinisherAcceptsVariousPageRepresentations($pageUid, int $expectedPage): void
    {
        $uriPrefix = 'https://site.test/?id=';

        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRendererProphecy->typoLink_URL(Argument::type('array'))->will(function ($arguments) use ($uriPrefix) {
            return $uriPrefix . $arguments[0]['parameter'];
        });
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy->cObj = $contentObjectRendererProphecy->reveal();
        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        $redirectFinisherMock = $this->getAccessibleMock(RedirectFinisher::class, null, [], '', false);
        $redirectFinisherMock->_set('options', [
            'pageUid' => $pageUid,
        ]);

        $response = new Response();
        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->getRequest()->willReturn(new Request());
        $formRuntimeProphecy->getResponse()->willReturn($response);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);
        $finisherContextProphecy->getFormRuntime()->willReturn($formRuntimeProphecy->reveal());
        $finisherContextProphecy->cancel()->shouldBeCalledOnce();

        $translationServiceProphecy = $this->prophesize(TranslationService::class);
        $translationServiceProphecy->translateFinisherOption(Argument::cetera())->willReturnArgument(3);

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->get(UriBuilder::class)->willReturn(new UriBuilder());
        $objectManagerProphecy->get(TranslationService::class)->willReturn($translationServiceProphecy->reveal());
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());
        $redirectFinisherMock->injectObjectManager($objectManagerProphecy->reveal());

        try {
            $redirectFinisherMock->execute($finisherContextProphecy->reveal());
            self::fail('RedirectFinisher did not throw expected exception.');
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (StopActionException $e) {
            self::assertSame($uriPrefix . $expectedPage, $response->getUnpreparedHeaders()['Location'][0]);
        }
    }

    public function pageUidOptionForFinisherAcceptsVariousPageRepresentationsDataProvider(): array
    {
        return [
            'null' => [
                null,
                1,
            ],
            'no page' => [
                '',
                1,
            ],
            'page as integer' => [
                3,
                3,
            ],
            'page as string' => [
                '3',
                3,
            ],
            'page with table prefix' => [
                'pages_3',
                3,
            ],
        ];
    }
}
