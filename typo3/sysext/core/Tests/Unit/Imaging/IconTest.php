<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class IconTest extends UnitTestCase
{
    /**
     * @var Icon
     */
    protected $subject;

    /**
     * @var string
     */
    protected $iconIdentifier = 'actions-close';

    /**
     * @var string
     */
    protected $overlayIdentifier = 'overlay-readonly';

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('assets')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $iconFactory = new IconFactory($eventDispatcherProphecy->reveal(), new IconRegistry());
        $this->subject = $iconFactory->getIcon($this->iconIdentifier, Icon::SIZE_SMALL, $this->overlayIdentifier, IconState::cast(IconState::STATE_DISABLED));
    }

    public function tearDown(): void
    {
        // Drop cache manager singleton again
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function renderAndCastToStringReturnsTheSameCode()
    {
        self::assertEquals($this->subject->render(), (string)$this->subject);
    }

    /**
     * @test
     */
    public function getIdentifierReturnsCorrectIdentifier()
    {
        self::assertEquals($this->iconIdentifier, $this->subject->getIdentifier());
    }

    /**
     * @test
     */
    public function getOverlayIdentifierReturnsCorrectIdentifier()
    {
        self::assertEquals($this->overlayIdentifier, $this->subject->getOverlayIcon()->getIdentifier());
    }

    /**
     * @test
     */
    public function getSizeIdentifierReturnsCorrectIdentifier()
    {
        self::assertEquals(Icon::SIZE_SMALL, $this->subject->getSize());
    }

    /**
     * @test
     */
    public function getStateReturnsCorrectIdentifier()
    {
        self::assertTrue($this->subject->getState()->equals(IconState::STATE_DISABLED));
    }
}
