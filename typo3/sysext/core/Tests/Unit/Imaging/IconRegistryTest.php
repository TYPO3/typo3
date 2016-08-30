<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

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

use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TestCase for \TYPO3\CMS\Core\Imaging\IconRegistry
 */
class IconRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\IconRegistry
     */
    protected $subject = null;

    /**
     * @var string
     */
    protected $notRegisteredIconIdentifier = 'my-super-unregistered-identifier';

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Imaging\IconRegistry();
    }

    /**
     * @test
     */
    public function getDefaultIconIdentifierReturnsTheCorrectDefaultIconIdentifierString()
    {
        $result = $this->subject->getDefaultIconIdentifier();
        $this->assertEquals($result, 'default-not-found');
    }

    /**
     * @test
     */
    public function isRegisteredReturnsTrueForRegisteredIcon()
    {
        $result = $this->subject->isRegistered($this->subject->getDefaultIconIdentifier());
        $this->assertEquals($result, true);
    }

    /**
     * @test
     */
    public function isRegisteredReturnsFalseForNotRegisteredIcon()
    {
        $result = $this->subject->isRegistered($this->notRegisteredIconIdentifier);
        $this->assertEquals($result, false);
    }

    /**
     * @test
     */
    public function registerIconAddNewIconToRegistry()
    {
        $unregisterdIcon = 'foo-bar-unregistered';
        $this->assertFalse($this->subject->isRegistered($unregisterdIcon));
        $this->subject->registerIcon($unregisterdIcon, FontawesomeIconProvider::class, [
            'name' => 'pencil',
            'additionalClasses' => 'fa-fw'
        ]);
        $this->assertTrue($this->subject->isRegistered($unregisterdIcon));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function registerIconThrowsInvalidArgumentExceptionWithInvalidIconProvider()
    {
        $this->subject->registerIcon($this->notRegisteredIconIdentifier, GeneralUtility::class);
    }

    /**
     * @expectedException \TYPO3\CMS\Core\Exception
     * @test
     */
    public function getIconConfigurationByIdentifierThrowsExceptionWithUnregisteredIconIdentifier()
    {
        $this->subject->getIconConfigurationByIdentifier($this->notRegisteredIconIdentifier);
    }

    /**
     * @test
     */
    public function getIconConfigurationByIdentifierReturnsCorrectConfiguration()
    {
        $result = $this->subject->getIconConfigurationByIdentifier('default-not-found');
        // result must contain at least provider and options array
        $this->assertArrayHasKey('provider', $result);
        $this->assertArrayHasKey('options', $result);
        // the provider must implement the IconProviderInterface
        $this->assertTrue(in_array(IconProviderInterface::class, class_implements($result['provider'])));
    }

    /**
     * @test
     */
    public function getAllRegisteredIconIdentifiersReturnsAnArrayWithIconIdentiefiers()
    {
        $this->assertInternalType('array', $this->subject->getAllRegisteredIconIdentifiers());
    }

    /**
     * @test
     */
    public function getAllRegisteredIconIdentifiersReturnsArrayWithAllRegisteredIconIdentifiers()
    {
        $result = $this->subject->getAllRegisteredIconIdentifiers();
        $this->assertInternalType('array', $result);
        $this->assertContains('default-not-found', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsDefaultIconIdentifierForEmptyFileExtension()
    {
        $result = $this->subject->getIconIdentifierForFileExtension('');
        $this->assertEquals('mimetypes-other-other', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsDefaultIconIdentifierForUnknownFileExtension()
    {
        $result = $this->subject->getIconIdentifierForFileExtension('xyz');
        $this->assertEquals('mimetypes-other-other', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForFileExtensionReturnsImageIconIdentifierForImageFileExtension()
    {
        $result = $this->subject->getIconIdentifierForFileExtension('jpg');
        $this->assertEquals('mimetypes-media-image', $result);
    }

    /**
     * @test
     */
    public function registerFileExtensionRegisterAnIcon()
    {
        $this->subject->registerFileExtension('abc', 'xyz');
        $result = $this->subject->getIconIdentifierForFileExtension('abc');
        $this->assertEquals('xyz', $result);
    }

    /**
     * @test
     */
    public function registerFileExtensionOverwriteAnExistingIcon()
    {
        $this->subject->registerFileExtension('jpg', 'xyz');
        $result = $this->subject->getIconIdentifierForFileExtension('jpg');
        $this->assertEquals('xyz', $result);
    }

    /**
     * @test
     */
    public function registerMimeTypeIconRegisterAnIcon()
    {
        $this->subject->registerMimeTypeIcon('foo/bar', 'mimetype-foo-bar');
        $result = $this->subject->getIconIdentifierForMimeType('foo/bar');
        $this->assertEquals('mimetype-foo-bar', $result);
    }

    /**
     * @test
     */
    public function registerMimeTypeIconOverwriteAnExistingIcon()
    {
        $this->subject->registerMimeTypeIcon('video/*', 'mimetype-foo-bar');
        $result = $this->subject->getIconIdentifierForMimeType('video/*');
        $this->assertEquals('mimetype-foo-bar', $result);
    }

    /**
     * @test
     */
    public function getIconIdentifierForMimeTypeWithUnknowMimeTypeReturnNull()
    {
        $result = $this->subject->getIconIdentifierForMimeType('bar/foo');
        $this->assertEquals(null, $result);
    }
}
