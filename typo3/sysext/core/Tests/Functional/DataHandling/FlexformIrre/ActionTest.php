<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\FlexformIrre;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

class ActionTest extends AbstractDataHandlerActionTestCase
{
    protected $coreExtensionsToLoad = [
        'workspaces',
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/DataHandling/FlexformIrre/Fixtures/fileadmin' => 'fileadmin/fixture',
    ];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/FlexformIrre/DataSet/';

    /**
     * @test
     */
    public function newVersionOfFileRelationInFlexformFieldIsCreatedOnSave()
    {
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->setWorkspaceId(1);
        $this->getActionService()->modifyRecords(1, [
            //'sys_file_reference' => ['uid' => 10, 'hidden' => 0],
            'tt_content' => ['uid' => 100, 'header' => 'Content #1 (WS)']
        ]);

        // there should be one relation in the live WS and one in the draft WS pointing to the file field.
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $referenceCount = $queryBuilder
            ->count('uid')
            ->from('sys_file_reference')
            ->where($queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter(20, \PDO::PARAM_INT)))
            ->execute()
            ->fetchColumn(0);

        self::assertEquals(2, $referenceCount);
    }
}
