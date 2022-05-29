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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CaseContentObjectTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    protected CaseContentObject $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->addMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $cObjectFactoryProphecy = $this->prophesize(ContentObjectFactory::class);

        $caseContentObject = new CaseContentObject();
        $caseContentObject->setRequest(($this->prophesize(ServerRequestInterface::class)->reveal()));
        $caseContentObject->setContentObjectRenderer($contentObjectRenderer);
        $cObjectFactoryProphecy->getContentObject('CASE', Argument::cetera())->willReturn($caseContentObject);

        $textContentObject = new TextContentObject();
        $textContentObject->setRequest(($this->prophesize(ServerRequestInterface::class)->reveal()));
        $textContentObject->setContentObjectRenderer($contentObjectRenderer);
        $cObjectFactoryProphecy->getContentObject('TEXT', Argument::cetera())->willReturn($textContentObject);

        $container = new Container();
        $container->set(ContentObjectFactory::class, $cObjectFactoryProphecy->reveal());
        GeneralUtility::setContainer($container);

        $this->subject = new CaseContentObject();
        $this->subject->setContentObjectRenderer($contentObjectRenderer);
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoKeyMatchesAndIfNoDefaultObjectIsSet(): void
    {
        $conf = [
            'key' => 'not existing',
        ];
        self::assertSame('', $this->subject->render($conf));
    }

    /**
     * @test
     */
    public function renderReturnsContentFromDefaultObjectIfKeyDoesNotExist(): void
    {
        $conf = [
            'key' => 'not existing',
            'default' => 'TEXT',
            'default.' => [
                'value' => 'expected value',
            ],
        ];
        self::assertSame('expected value', $this->subject->render($conf));
    }
}
