<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

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

/**
 * Processed file repository test
 */
class ProcessedFileRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function cleanUnavailableColumnsWorks()
    {
        $fixture = $this->getAccessibleMock(\TYPO3\CMS\Core\Resource\ProcessedFileRepository::class, ['dummy'], [], '', false);
        $databaseMock = $this->getAccessibleMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['admin_get_fields']);
        $databaseMock->expects($this->once())->method('admin_get_fields')->will($this->returnValue(['storage' => '', 'checksum' => '']));
        $fixture->_set('databaseConnection', $databaseMock);

        $actual = $fixture->_call('cleanUnavailableColumns', ['storage' => 'a', 'checksum' => 'b', 'key3' => 'c']);

        $this->assertSame(['storage' => 'a', 'checksum' => 'b'], $actual);
    }
}
