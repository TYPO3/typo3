<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

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

use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase;

/**
 * Test case
 */
class AbstractHierarchicalFilesystemDriverTest extends BaseTestCase
{
    /**
     * @var AbstractHierarchicalFilesystemDriver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMockForAbstractClass(AbstractHierarchicalFilesystemDriver::class, [], '', false);
    }

    /**
     * @test
     * @dataProvider canonicalizeAndCheckFileIdentifierCanonicalizesPathDataProvider
     * @param string $expectedPath
     * @param string $fileIdentifier
     */
    public function canonicalizeAndCheckFileIdentifierCanonicalizesPath($expectedPath, $fileIdentifier)
    {
        $this->assertSame($expectedPath, $this->subject->_callRef('canonicalizeAndCheckFileIdentifier', $fileIdentifier));
    }

    /**
     * @return array
     */
    public function canonicalizeAndCheckFileIdentifierCanonicalizesPathDataProvider()
    {
        return [
            'File path gets leading slash' => [
                '/foo.php',
                'foo.php',
            ],
            'Absolute path to file is not modified' => [
                '/bar/foo.php',
                '/bar/foo.php',
            ],
            'Relative path to file gets leading slash' => [
                '/bar/foo.php',
                'bar/foo.php',
            ],
            'Empty string is returned as empty string' => [
                '',
                '',
            ],
            'Double slashes in path are removed' => [
                '/bar/foo.php',
                '/bar//foo.php',
            ],
            'Trailing point in path is removed' => [
                '/foo.php',
                './foo.php',
            ],
            'Point is replaced by slash' => [
                '/',
                '.',
            ],
            './ becomes /' => [
                '/',
                './',
            ]
        ];
    }

    /**
     * @test
     * @dataProvider canonicalizeAndCheckFolderIdentifierCanonicalizesFolderIdentifierDataProvider
     * @param string $expectedPath
     * @param string $identifier
     */
    public function canonicalizeAndCheckFolderIdentifierCanonicalizesFolderIdentifier($expectedPath, $identifier)
    {
        $this->assertSame($expectedPath, $this->subject->_callRef('canonicalizeAndCheckFolderIdentifier', $identifier));
    }

    /**
     * @return array
     */
    public function canonicalizeAndCheckFolderIdentifierCanonicalizesFolderIdentifierDataProvider()
    {
        return [
            'Empty string results in slash' => [
                '/',
                '',
            ],
            'Single point results in slash' => [
                '/',
                '.',
            ],
            'Single slash results in single slash' => [
                '/',
                '/',
            ],
            'Double slash results in single slash' => [
                '/',
                '//',
            ],
            'Absolute folder paths without trailing slash gets a trailing slash' => [
                '/foo/',
                '/foo',
            ],
            'Absolute path with trailing and leading slash is not modified' => [
                '/foo/',
                '/foo/',
            ],
            'Relative path to folder becomes absolute path with trailing slash' => [
                '/foo/',
                'foo/',
            ],
        ];
    }
}
