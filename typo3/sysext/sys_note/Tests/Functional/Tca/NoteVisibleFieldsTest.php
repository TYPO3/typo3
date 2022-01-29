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

namespace TYPO3\CMS\SysNote\Tests\Functional\Tca;

use TYPO3\CMS\Backend\Tests\Functional\Form\FormTestService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NoteVisibleFieldsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['sys_note'];

    /**
     * @var array
     */
    protected static $noteFields = [
        'category',
        'personal',
        'subject',
        'message',
    ];

    /**
     * @test
     */
    public function noteFormContainsExpectedFields(): void
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');

        $formEngineTestService = new FormTestService();
        $formResult = $formEngineTestService->createNewRecordForm('sys_note');

        foreach (static::$noteFields as $expectedField) {
            self::assertNotFalse(
                $formEngineTestService->formHtmlContainsField($expectedField, $formResult['html']),
                'The field ' . $expectedField . ' is not in the form HTML'
            );
        }
    }
}
