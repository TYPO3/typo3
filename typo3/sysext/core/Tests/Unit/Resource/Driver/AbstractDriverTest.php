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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the abstract driver.
 */
class AbstractDriverTest extends UnitTestCase
{
    /**
     * @var AbstractDriver
     */
    protected $subject;

    protected string $basedir = 'basedir';
    protected ?string $mountDir;
    protected array $vfsContents = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mountDir = StringUtility::getUniqueId('mount-');
        $this->basedir = StringUtility::getUniqueId('base-');
        vfsStream::setup($this->basedir);
        // Add an entry for the mount directory to the VFS contents
        $this->vfsContents = [$this->mountDir => []];
        $this->subject = $this->createDriver();
    }

    /**
     * @return AbstractDriver
     */
    protected function createDriver(): AbstractDriver
    {
        return $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
    }

    /**
     * @test
     */
    public function isCaseSensitiveFileSystemReturnsTrueIfNothingIsConfigured(): void
    {
        self::assertTrue($this->subject->isCaseSensitiveFileSystem());
    }
}
