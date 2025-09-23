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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Scheduler\Controller\NewSchedulerTaskController;
use TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NewSchedulerTaskControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/scheduler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function handleRequestReturnsWizardContent(): void
    {
        $request = (new ServerRequest('http://localhost/typo3/scheduler/task/wizard/new', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/scheduler/task/wizard/new', ['packageName' => 'typo3/cms-scheduler', '_identifier' => 'ajax_new_scheduler_task_wizard']));

        $controller = $this->get(NewSchedulerTaskController::class);
        $response = $controller->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        $content = (string)$response->getBody();

        // Should contain the wizard web component
        self::assertStringContainsString('typo3-backend-new-record-wizard', $content);
        self::assertStringContainsString('new-scheduler-task', $content);
    }

    #[Test]
    public function modifyNewSchedulerTaskWizardItemsEventIsCalled(): void
    {
        $eventReference = null;
        $capturedWizardItems = null;

        $container = $this->get('service_container');
        $container->set(
            'test-wizard-event-listener',
            static function (ModifyNewSchedulerTaskWizardItemsEvent $event) use (&$eventReference, &$capturedWizardItems) {
                $eventReference = $event;

                // Modify wizard items to test event functionality
                $event->addWizardItem('test_custom_task', [
                    'title' => 'Custom Test Task',
                    'description' => 'A custom task added by event listener',
                    'icon' => 'content-test',
                    'taskType' => 'CustomTestTask',
                    'taskClass' => 'TYPO3\\CMS\\Test\\CustomTestTask',
                ]);

                $capturedWizardItems = $event->getWizardItems();
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyNewSchedulerTaskWizardItemsEvent::class, 'test-wizard-event-listener');

        $request = (new ServerRequest('http://localhost/typo3/scheduler/task/wizard/new', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/scheduler/task/wizard/new', ['packageName' => 'typo3/cms-scheduler', '_identifier' => 'ajax_new_scheduler_task_wizard']));
        $controller = $this->get(NewSchedulerTaskController::class);

        $response = $controller->handleRequest($request);

        self::assertInstanceOf(ModifyNewSchedulerTaskWizardItemsEvent::class, $eventReference);
        self::assertIsArray($capturedWizardItems);
        self::assertArrayHasKey('test_custom_task', $capturedWizardItems);

        // Check that wizard items contain expected structure
        $hasSchedulerCategory = false;
        $hasTaskItems = false;
        foreach ($capturedWizardItems as $item) {
            if (isset($item['header']) && $item['header'] === 'Scheduler') {
                $hasSchedulerCategory = true;
            }
            if (isset($item['taskType']) && str_contains($item['taskType'], 'CachingFrameworkGarbageCollectionTask')) {
                $hasTaskItems = true;
                self::assertArrayHasKey('title', $item);
                self::assertArrayHasKey('description', $item);
                self::assertArrayHasKey('icon', $item);
                self::assertArrayHasKey('taskClass', $item);
            }
        }

        self::assertTrue($hasSchedulerCategory);
        self::assertTrue($hasTaskItems);

        // Verify the response contains our custom task
        $content = (string)$response->getBody();
        self::assertStringContainsString('Custom Test Task', $content);
    }

    #[Test]
    public function wizardContainsRegisteredSchedulerTasks(): void
    {
        $request = (new ServerRequest('http://localhost/typo3/scheduler/task/wizard/new', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/scheduler/task/wizard/new', ['packageName' => 'typo3/cms-scheduler', '_identifier' => 'ajax_new_scheduler_task_wizard']));
        $controller = $this->get(NewSchedulerTaskController::class);

        $response = $controller->handleRequest($request);
        $content = (string)$response->getBody();

        // Should contain registered scheduler tasks
        self::assertStringContainsString('Caching framework garbage collection', $content);
        self::assertStringContainsString('File Abstraction Layer', $content);
        self::assertStringContainsString('Table garbage collection', $content);
    }

    #[Test]
    public function queryParamsAreProcessed(): void
    {
        $defaultValues = ['description' => 'Test default description'];

        $request = (new ServerRequest('http://localhost/typo3/scheduler/task/wizard/new', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/scheduler/task/wizard/new', ['packageName' => 'typo3/cms-scheduler', '_identifier' => 'ajax_new_scheduler_task_wizard']));
        $request = $request->withQueryParams([
            'returnUrl' => Environment::getPublicPath() . 'typo3/scheduler/manage?token=123&test=value',
            'defaultValues' => $defaultValues,
        ]);

        $controller = $this->get(NewSchedulerTaskController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();

        // Default values should be included in the FormEngine URLs
        self::assertStringContainsString('typo3\/scheduler\/manage?token%3D123%26test%3Dvalue', $content);
        self::assertStringContainsString('Test%20default%20description', $content);
    }
}
