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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Event\BeforeRemoveNonCopyableFieldsEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler localizeRecord functionality
 */
final class LocalizeRecordTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LocalizeRecord/SysCategory.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function categoryRecordIsLocalizedTest(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['sys_category'][1]['localize'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecord/SysCategoryRecordIsLocalizedResult.csv');
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

        $cmd['sys_category'][1]['localize'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        self::assertInstanceOf(BeforeRemoveNonCopyableFieldsEvent::class, $beforeRemoveNonCopyableFieldsEvent);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecord/SysCategoryRecordIsLocalizedWithoutDescriptionFieldResult.csv');
    }
}
