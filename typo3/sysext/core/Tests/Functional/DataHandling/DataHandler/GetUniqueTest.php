<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests related to DataHandler::getUnique()
 */
class GetUniqueTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $connection->insert('pages', ['title' => 'ExistingPage']);
        $connection->insert('pages', ['title' => 'ManyPages']);
        for ($i = 0; $i <= 100; $i++) {
            $connection->insert('pages', ['title' => 'ManyPages' . $i]);
        }
    }

    /**
     * Data provider for getUnique
     * @return array
     */
    public function getUniqueDataProvider(): array
    {
        $randomValue = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(10);

        return [
            'unique value' => [$randomValue, $randomValue],
            'non-unique value' => ['ExistingPage', 'ExistingPage0'],
            'uniqueness not enforceable' => ['ManyPages', 'ManyPages100'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     * @test
     * @dataProvider getUniqueDataProvider
     */
    public function getUnique(string $value, string $expected)
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $this->assertSame(
            $expected,
            $dataHandler->getUnique('pages', 'title', $value, 0, 0)
        );
    }
}
