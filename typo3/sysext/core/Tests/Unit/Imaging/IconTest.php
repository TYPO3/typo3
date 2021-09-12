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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class IconTest extends UnitTestCase
{
    use ProphecyTrait;

    protected ?Icon $subject;
    protected string $iconIdentifier = 'actions-close';
    protected string $overlayIdentifier = 'overlay-readonly';

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $iconFactory = new IconFactory($eventDispatcherProphecy->reveal(), new IconRegistry($cacheFrontendProphecy->reveal()), $containerProphecy->reveal());
        $this->subject = $iconFactory->getIcon($this->iconIdentifier, Icon::SIZE_SMALL, $this->overlayIdentifier, IconState::cast(IconState::STATE_DISABLED));
    }

    /**
     * @test
     */
    public function renderAndCastToStringReturnsTheSameCode(): void
    {
        self::assertEquals($this->subject->render(), (string)$this->subject);
    }

    /**
     * @test
     */
    public function getIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals($this->iconIdentifier, $this->subject->getIdentifier());
    }

    /**
     * @test
     */
    public function getOverlayIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals($this->overlayIdentifier, $this->subject->getOverlayIcon()->getIdentifier());
    }

    /**
     * @test
     */
    public function getSizeIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals(Icon::SIZE_SMALL, $this->subject->getSize());
    }

    /**
     * @test
     */
    public function getStateReturnsCorrectIdentifier(): void
    {
        self::assertTrue($this->subject->getState()->equals(IconState::STATE_DISABLED));
    }
}
