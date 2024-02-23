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

namespace TYPO3\CMS\Filelist\Tests\Unit\Matcher;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\Matcher\ResourceFileExtensionMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ResourceFileExtensionMatcherTest extends UnitTestCase
{
    protected ResourceStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
    }

    #[Test]
    public function fileExtensionsAreTransformedToLowercase(): void
    {
        $matcher = new ResourceFileExtensionMatcher();

        self::assertFalse($matcher->match($this->getFile('jpg')));
        self::assertFalse($matcher->match($this->getFile('gif')));
        $matcher->setExtensions(['JPG', 'GIF']);
        self::assertTrue($matcher->match($this->getFile('jpg')));
        self::assertTrue($matcher->match($this->getFile('gif')));

        self::assertFalse($matcher->match($this->getFile('png')));
        $matcher->addExtension('PNG');
        self::assertTrue($matcher->match($this->getFile('png')));
    }

    #[Test]
    public function ignoredFileExtensionsAreTransformedToLowercase(): void
    {
        $matcher = new ResourceFileExtensionMatcher();
        $matcher->setExtensions(['jpg', 'gif', 'png']);

        self::assertTrue($matcher->match($this->getFile('jpg')));
        self::assertTrue($matcher->match($this->getFile('gif')));
        self::assertTrue($matcher->match($this->getFile('png')));

        $matcher->setIgnoredExtensions(['JPG', 'GIF']);
        self::assertFalse($matcher->match($this->getFile('jpg')));
        self::assertFalse($matcher->match($this->getFile('gif')));

        $matcher->addIgnoredExtension('PNG');
        self::assertFalse($matcher->match($this->getFile('png')));
    }

    protected function getFile(string $extension): File
    {
        return new File(['identifier' => $extension . '-file', 'name' => 'file.' . $extension], $this->storage);
    }
}
