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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
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
        $iconFactory = new IconFactory(new NoopEventDispatcher(), new IconRegistry(new NullFrontend('test'), 'BackendIcons'), $containerMock, $this->createMock(FrontendInterface::class));
        $this->subject = $iconFactory->getIcon($this->iconIdentifier, IconSize::SMALL, $this->overlayIdentifier, IconState::STATE_DISABLED);
    }

    #[Test]
    public function renderAndCastToStringReturnsTheSameCode(): void
    {
        self::assertEquals($this->subject->render(), (string)$this->subject);
    }

    #[Test]
    public function getIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals($this->iconIdentifier, $this->subject->getIdentifier());
    }

    #[Test]
    public function getOverlayIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals($this->overlayIdentifier, $this->subject->getOverlayIcon()->getIdentifier());
    }

    #[Test]
    public function getSizeIdentifierReturnsCorrectIdentifier(): void
    {
        self::assertEquals(IconSize::SMALL->value, $this->subject->getSize());
    }

    #[Test]
    public function getStateReturnsCorrectIdentifier(): void
    {
        self::assertSame($this->subject->getState(), IconState::STATE_DISABLED);
    }

    public static function setSizeSetsExpectedValuesDataProvider(): \Generator
    {
        yield 'IconSize::DEFAULT' => [
            IconSize::DEFAULT,
            [16, 16],
        ];
        yield 'IconSize::SMALL' => [
            IconSize::SMALL,
            [16, 16],
        ];
        yield 'IconSize::OVERLAY' => [
            IconSize::OVERLAY,
            [16, 16],
        ];
        yield 'IconSize::MEDIUM' => [
            IconSize::MEDIUM,
            [32, 32],
        ];
        yield 'IconSize::LARGE' => [
            IconSize::LARGE,
            [48, 48],
        ];
        yield 'IconSize::MEGA' => [
            IconSize::MEGA,
            [64, 64],
        ];
    }

    #[DataProvider('setSizeSetsExpectedValuesDataProvider')]
    #[Test]
    public function setSizeSetsExpectedValues(IconSize $size, array $expectedDimensions): void
    {
        $icon = new Icon();
        $icon->setSize($size);

        [$width, $height] = $expectedDimensions;

        self::assertSame($width, $icon->getDimension()->getWidth());
        self::assertSame($height, $icon->getDimension()->getHeight());
    }
}
