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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class IconRegistryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    protected string $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

    /** @var ObjectProphecy<FrontendInterface> */
    protected ObjectProphecy $cacheFrontendProphecy;

    public function setUp(): void
    {
        parent::setUp();
        $this->cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $this->cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $this->cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
    }

    /**
     * @test
     */
    public function getDefaultIconIdentifierReturnsTheCorrectDefaultIconIdentifierString(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getDefaultIconIdentifier();
        self::assertEquals('default-not-found', $result);
    }

    /**
     * @test
     */
    public function isRegisteredReturnsTrueForRegisteredIcon(): void
    {
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        $result = $subject->isRegistered($subject->getDefaultIconIdentifier());
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isRegisteredReturnsFalseForNotRegisteredIcon(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->isRegistered($this->notRegisteredIconIdentifier);
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function registerIconAddNewIconToRegistry(): void
    {
        $unregisteredIcon = 'foo-bar-unregistered';
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        self::assertFalse($subject->isRegistered($unregisteredIcon));
        $subject->registerIcon($unregisteredIcon, BitmapIconProvider::class, [
            'name' => 'pencil',
            'source' => 'EXT:core/Resoureces/Public/Icons/pencil.png',
        ]);
        self::assertTrue($subject->isRegistered($unregisteredIcon));
    }

    /**
     * @test
     */
    public function registerIconThrowsInvalidArgumentExceptionWithInvalidIconProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437425803);

        (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->registerIcon($this->notRegisteredIconIdentifier, GeneralUtility::class);
    }

    /**
     * @test
     */
    public function getIconConfigurationByIdentifierThrowsExceptionWithUnregisteredIconIdentifier(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1437425804);

        (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconConfigurationByIdentifier($this->notRegisteredIconIdentifier);
    }

    /**
     * @test
     */
    public function getIconConfigurationByIdentifierReturnsCorrectConfiguration(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconConfigurationByIdentifier('default-not-found');
        // result must contain at least provider and options array
        self::assertArrayHasKey('provider', $result);
        self::assertArrayHasKey('options', $result);
        // the provider must implement the IconProviderInterface
        self::assertContains(IconProviderInterface::class, class_implements($result['provider']));
    }

    /**
     * @test
     */
    public function getAllRegisteredIconIdentifiersReturnsAnArrayWithIconIdentifiers(): void
    {
        self::assertIsArray((new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getAllRegisteredIconIdentifiers());
    }

    /**
     * @test
     */
    public function getAllRegisteredIconIdentifiersReturnsArrayWithAllRegisteredIconIdentifiers(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getAllRegisteredIconIdentifiers();
        self::assertIsArray($result);
        self::assertContains('default-not-found', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsDefaultIconIdentifierForEmptyFileExtension(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconIdentifierForFileExtension('');
        self::assertEquals('mimetypes-other-other', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsDefaultIconIdentifierForUnknownFileExtension(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconIdentifierForFileExtension('xyz');
        self::assertEquals('mimetypes-other-other', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsImageIconIdentifierForImageFileExtension(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconIdentifierForFileExtension('jpg');
        self::assertEquals('mimetypes-media-image', $result);
    }

    /**
     * @test
     */
    public function registerFileExtensionRegisterAnIcon(): void
    {
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        $subject->registerFileExtension('abc', 'xyz');
        $result = $subject->getIconIdentifierForFileExtension('abc');
        self::assertEquals('xyz', $result);
    }

    /**
     * @test
     */
    public function registerFileExtensionOverwriteAnExistingIcon(): void
    {
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        $subject->registerFileExtension('jpg', 'xyz');
        $result = $subject->getIconIdentifierForFileExtension('jpg');
        self::assertEquals('xyz', $result);
    }

    /**
     * @test
     */
    public function registerMimeTypeIconRegisterAnIcon(): void
    {
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        $subject->registerMimeTypeIcon('foo/bar', 'mimetype-foo-bar');
        $result = $subject->getIconIdentifierForMimeType('foo/bar');
        self::assertEquals('mimetype-foo-bar', $result);
    }

    /**
     * @test
     */
    public function registerMimeTypeIconOverwriteAnExistingIcon(): void
    {
        $subject = new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons');
        $subject->registerMimeTypeIcon('video/*', 'mimetype-foo-bar');
        $result = $subject->getIconIdentifierForMimeType('video/*');
        self::assertEquals('mimetype-foo-bar', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForMimeTypeWithUnknownMimeTypeReturnNull(): void
    {
        $result = (new IconRegistry($this->cacheFrontendProphecy->reveal(), 'BackendIcons'))->getIconIdentifierForMimeType('bar/foo');
        self::assertNull($result);
    }
}
