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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Uuid;

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_TargetPageId = 90;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_ElementIdFirst = 1;
    protected const VALUE_ElementIdCreated = 2;
    protected const VALUE_OriginalUuid = '8b078a23-f640-4a4f-b826-0834d10560ad';

    protected const TABLE_Element = 'test_uuid';
    protected const FIELD_Uuid = 'unique_identifier';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_uuid',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
    }

    public function createEmptyRecord(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_TargetPageId, []);
        $this->recordIds['newElementIdFirst'] = $newTableIds[self::TABLE_Element][0];
    }

    public function createNewPrefilledRecord(): void
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_TargetPageId, [self::FIELD_Uuid => Uuid::v4()]);
        $this->recordIds['newElementIdFirst'] = $newTableIds[self::TABLE_Element][0];
    }

    public function copyRecord(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_TargetPageId);
        $this->recordIds['newElementIdFirst'] = $newTableIds[self::TABLE_Element][1];
    }

    public function localizeRecord(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['newElementIdFirst'] = $newTableIds[self::TABLE_Element][1];
    }

    public function moveRecord(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_TargetPageId);
    }
}
