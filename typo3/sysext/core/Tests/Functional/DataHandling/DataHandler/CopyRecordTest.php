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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Event\BeforeRemoveNonCopyableFieldsEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler copyRecord functionality
 */
final class CopyRecordTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function pageRecordIsCopiedTest(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['pages'][2]['copy'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $this->assertCSVDataSet(__DIR__ . '/DataSet/CopyRecord/PageRecordIsCopiedResult.csv');
    }

    public static function copyAfterAnotherRecordKeepsLanguageOfSourceRecordDataProvider(): array
    {
        return [
            'all languages after language 1' => [10, 12, -1],
            'default after language 1' => [11, 12, 0],
            'language 1 after default' => [12, 11, 1],
            'language 1 after all languages' => [12, 10, 1],
            'default after all languages' => [11, 10, 0],
        ];
    }

    #[Test]
    #[DataProvider('copyAfterAnotherRecordKeepsLanguageOfSourceRecordDataProvider')]
    public function copyAfterAnotherRecordKeepsLanguageOfSourceRecord(int $sourceUid, int $precedingUid, int $expectedLanguage): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/ContentLanguages.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['tt_content'][$sourceUid]['copy'] = -$precedingUid;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $copyUid = $dataHandler->copyMappingArray_merged['tt_content'][$sourceUid] ?? null;
        self::assertIsInt($copyUid);
        $copy = BackendUtility::getRecord('tt_content', $copyUid);
        self::assertSame($expectedLanguage, (int)$copy['sys_language_uid']);
    }

    #[Test]
    public function beforeRemoveNonCopyableFieldsEventIsCalledAndNonCopyableFieldsAreRespected(): void
    {
        $beforeRemoveNonCopyableFieldsEvent = null;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-remove-non-copyable-fields-event',
            static function (BeforeRemoveNonCopyableFieldsEvent $event) use (&$beforeRemoveNonCopyableFieldsEvent) {
                $beforeRemoveNonCopyableFieldsEvent = $event;
                $nonCopyableFields = $event->getNonCopyableFields();
                $nonCopyableFields[] = 'description';
                $event->setNonCopyableFields($nonCopyableFields);
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRemoveNonCopyableFieldsEvent::class, 'before-remove-non-copyable-fields-event');

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['pages'][2]['copy'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        self::assertInstanceOf(BeforeRemoveNonCopyableFieldsEvent::class, $beforeRemoveNonCopyableFieldsEvent);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/CopyRecord/PageRecordIsCopiedWithoutDescriptionFieldResult.csv');
    }
}
