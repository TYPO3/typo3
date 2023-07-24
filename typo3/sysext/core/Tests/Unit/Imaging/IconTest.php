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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IconTest extends UnitTestCase
{
    protected ?Icon $subject;
    protected string $iconIdentifier = 'actions-close';
    protected string $overlayIdentifier = 'overlay-readonly';

    protected function setUp(): void
    {
        parent::setUp();
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);
        $iconFactory = new IconFactory(new NoopEventDispatcher(), new IconRegistry(new NullFrontend('test'), 'BackendIcons'), $containerMock);
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

    public static function setSizeSetsExpectedValuesDataProvider(): \Generator
    {
        yield 'SIZE_DEFAULT' => [
            Icon::SIZE_DEFAULT,
            [16, 16],
        ];
        yield 'SIZE_SMALL' => [
            Icon::SIZE_DEFAULT,
            [16, 16],
        ];
        yield 'SIZE_OVERLAY' => [
            Icon::SIZE_OVERLAY,
            [16, 16],
        ];
        yield 'SIZE_MEDIUM' => [
            Icon::SIZE_MEDIUM,
            [32, 32],
        ];
        yield 'SIZE_LARGE' => [
            Icon::SIZE_LARGE,
            [48, 48],
        ];
        yield 'SIZE_MEGA' => [
            Icon::SIZE_MEGA,
            [64, 64],
        ];
    }

    /**
     * @test
     * @dataProvider setSizeSetsExpectedValuesDataProvider
     */
    public function setSizeSetsExpectedValues(string $size, array $expectedDimensions): void
    {
        $icon = new Icon();
        $icon->setSize($size);

        [$width, $height] = $expectedDimensions;

        self::assertSame($width, $icon->getDimension()->getWidth());
        self::assertSame($height, $icon->getDimension()->getHeight());
    }
}
