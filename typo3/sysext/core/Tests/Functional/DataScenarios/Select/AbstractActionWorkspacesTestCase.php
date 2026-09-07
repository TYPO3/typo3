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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Select;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

abstract class AbstractActionWorkspacesTestCase extends AbstractActionTestCase
{
    protected const VALUE_WorkspaceId = 1;

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefaultWorkspaces.csv';

    protected array $coreExtensionsToLoad = ['workspaces'];

    public function modifyBothSidesOfLocalizedRelationInSingleRequest(): void
    {
        $GLOBALS['TCA'][self::TABLE_Content]['columns'][self::FIELD_ContentElement]['config']['localizeReferencesAtParentLocalization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->setWorkspaceId(0);
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $this->recordIds['localizedElementIdFirst'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
        $this->recordIds['localizedElementIdSecond'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdSecond];
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Element => ['uid' => $this->recordIds['localizedElementIdFirst'], 'title' => 'Testing #1'],
                self::TABLE_Content => [
                    'uid' => $this->recordIds['localizedContentId'],
                    'header' => 'Testing #1',
                    self::FIELD_ContentElement => $this->recordIds['localizedElementIdFirst'] . ',' . $this->recordIds['localizedElementIdSecond'],
                ],
            ]
        );
    }
}
