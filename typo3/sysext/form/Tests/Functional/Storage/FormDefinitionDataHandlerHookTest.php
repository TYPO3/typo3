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

namespace TYPO3\CMS\Form\Tests\Functional\Storage;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Verifies that FormDefinitionDataHandlerHook denies direct DataHandler access
 * to form_definition records while still allowing access through DatabaseStorageAdapter.
 */
final class FormDefinitionDataHandlerHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
    }

    #[Test]
    public function directDataHandlerCreateIsDenied(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'form_definition' => [
                'NEW1' => [
                    'pid' => 0,
                    'label' => 'Injected Form',
                    'identifier' => 'injected-form',
                    'configuration' => '{}',
                ],
            ],
        ], []);

        $dataHandler->process_datamap();
        self::assertSame(['[1.1]: Persisting form definition "NEW1" via DataHandler is denied'], $dataHandler->errorLog);
    }

    #[Test]
    public function directDataHandlerUpdateIsDenied(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'form_definition' => [
                1 => [
                    'label' => 'Tampered Label',
                ],
            ],
        ], []);

        $dataHandler->process_datamap();
        self::assertSame(['[1.2]: Persisting form definition "1" via DataHandler is denied'], $dataHandler->errorLog);
    }

    #[Test]
    public function directDataHandlerDeleteIsDenied(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'form_definition' => [
                1 => ['delete' => 1],
            ],
        ]);

        $dataHandler->process_cmdmap();
        self::assertSame(['[1.3]: Deleting form definition "1" via DataHandler is denied'], $dataHandler->errorLog);
    }
}
