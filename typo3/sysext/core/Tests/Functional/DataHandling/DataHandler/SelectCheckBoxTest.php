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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SelectCheckBoxTest extends FunctionalTestCase
{
    protected const PAGE_ID = 0;
    protected ?BackendUserAuthentication $backendUserAuthentication = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->backendUserAuthentication = $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function validMultipleChecked(): void
    {
        $newUserRecord = $this->createBackendUser([
            'file_permissions' => 'readFolder,writeFolder,renameFolder,moveFolder,writeFile,addFile,moveFile,copyFile',
        ]);
        self::assertEquals(
            'readFolder,writeFolder,renameFolder,moveFolder,writeFile,addFile,moveFile,copyFile',
            $newUserRecord['file_permissions']
        );
    }

    /**
     * @test
     */
    public function validNoneCheckedEmptyValuesAllowed(): void
    {
        $newUserRecord = $this->createBackendUser(['file_permissions' => '']);
        self::assertEquals('', $newUserRecord['file_permissions']);
    }

    protected function createBackendUser(array $backendUser): array
    {
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('be_users', self::PAGE_ID, $backendUser);
        $newUserId = reset($map['be_users']);
        return BackendUtility::getRecord('be_users', $newUserId);
    }
}
