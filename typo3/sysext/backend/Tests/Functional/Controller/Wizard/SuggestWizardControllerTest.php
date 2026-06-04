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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Wizard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SuggestWizardControllerTest extends FunctionalTestCase
{
    #[Test]
    public function searchActionReturnsResultsForTableWithoutTypeField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_category.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // sys_category does not define ctrl['type'], FormEngine still hands
        // over the default record type "1" for such tables.
        $request = (new ServerRequest('https://example.com/typo3/ajax/wizard/suggest/search', 'POST'))
            ->withParsedBody([
                'value' => 'Category B',
                'tableName' => 'sys_category',
                'fieldName' => 'parent',
                'uid' => '1',
                'pid' => '0',
                'recordTypeValue' => '1',
            ]);

        $response = $this->get(SuggestWizardController::class)->searchAction($request);

        $rows = json_decode((string)$response->getBody(), true);
        self::assertCount(1, $rows);
        self::assertSame('Category B', $rows[0]['label']);
    }
}
