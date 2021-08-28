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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FileList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Cases concerning sys_file_metadata records
 */
class FileMetaDataCest
{
    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param ApplicationTester $I
     */
    public function metaDataCanBeEdited(ApplicationTester $I)
    {
        $I->wantToTest('Metadata can be edited through search list results');
        $I->click('Filelist');

        $I->switchToContentFrame();
        $I->canSee('fileadmin');

        $I->fillField('input[name="searchTerm"]', 'bus');
        $I->click('button[type="submit"]');
        $I->waitForElementVisible('table.table-striped');

        $I->click('bus_lane.jpg');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('#EditDocumentController');

        $I->canSee('Edit File Metadata "bus_lane.jpg"', 'h1');
    }
}
