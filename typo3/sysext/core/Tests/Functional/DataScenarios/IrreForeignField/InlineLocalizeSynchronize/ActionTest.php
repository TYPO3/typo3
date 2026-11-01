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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\InlineLocalizeSynchronize;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\AbstractActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\InlineLocalizeSynchronize\Fixtures\DenyModifyAccessToTable;

final class ActionTest extends AbstractActionTestCase
{
    protected function tearDown(): void
    {
        DenyModifyAccessToTable::$deniedTable = '';
        parent::tearDown();
    }

    /**
     * Content element 297 and its two hotels are localized. The translation of hotel 3 is then
     * changed to point to hotel 2, which is no child of the default language content element.
     * Synchronizing the translation removes it, which fails if the user may not modify hotels.
     * The error must be reported.
     */
    #[Test]
    public function synchronizeReportsErrorOfRemovingTranslatedChild(): void
    {
        $this->expectedErrorLogEntries = 1;
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, self::VALUE_LanguageId);
        $localizedContentId = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdFirst];
        $localizedHotelId = $newTableIds[self::TABLE_Hotel][self::VALUE_HotelIdFirst];
        $this->getConnectionPool()->getConnectionForTable(self::TABLE_Hotel)->update(self::TABLE_Hotel, ['l18n_parent' => 2], ['uid' => $localizedHotelId]);
        $this->get(ReferenceIndex::class)->updateRefIndexTable(self::TABLE_Hotel, $localizedHotelId);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = DenyModifyAccessToTable::class;
        DenyModifyAccessToTable::$deniedTable = self::TABLE_Hotel;
        $this->actionService->invoke(
            [],
            [
                self::TABLE_Content => [
                    $localizedContentId => [
                        'inlineLocalizeSynchronize' => [
                            'field' => self::FIELD_ContentHotel,
                            'language' => self::VALUE_LanguageId,
                            'action' => 'synchronize',
                        ],
                    ],
                ],
            ]
        );

        self::assertSame(
            ['[1.2]: Attempt to modify table "' . self::TABLE_Hotel . '" without permission'],
            $this->actionService->getDataHandler()->errorLog
        );
    }
}
