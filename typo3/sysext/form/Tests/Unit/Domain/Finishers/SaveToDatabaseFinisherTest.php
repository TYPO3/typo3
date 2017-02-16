<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

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

use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher;

/**
 * Test case
 */
class SaveToDatabaseFinisherTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function throwExceptionOnInconsistentConfigurationThrowExceptionOnInconsistentConfiguration()
    {
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1480469086);

        $mockSaveToDatabaseFinisher = $this->getAccessibleMock(SaveToDatabaseFinisher::class, [
            'dummy'
        ], [], '', false);

        $mockSaveToDatabaseFinisher->_set('options', [
            'mode' => 'update',
            'whereClause' => '',
        ]);

        $mockSaveToDatabaseFinisher->_call('throwExceptionOnInconsistentConfiguration');
    }
}
