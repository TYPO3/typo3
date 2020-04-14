<?php

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RepositoryRepositoryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockObjectManager = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class)->getMock();
        /** @var $subject \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit\Framework\MockObject\MockObject */
        $this->subject = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class)
            ->setMethods(['findAll'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
    }

    /**
     * @test
     */
    public function findOneTypo3OrgRepositoryReturnsNullIfNoRepositoryWithThisTitleExists()
    {
        $this->subject
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        self::assertNull($this->subject->findOneTypo3OrgRepository());
    }

    /**
     * @test
     */
    public function findOneTypo3OrgRepositoryReturnsRepositoryWithCorrectTitle()
    {
        $mockModelOne = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class)->getMock();
        $mockModelOne
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('foo');
        $mockModelTwo = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository::class)->getMock();
        $mockModelTwo
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('TYPO3.org Main Repository');

        $this->subject
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$mockModelOne, $mockModelTwo]);

        self::assertSame($mockModelTwo, $this->subject->findOneTypo3OrgRepository());
    }
}
