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

namespace TYPO3\CMS\Redirects\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Controller\ManagementController;
use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ManagementControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    private ManagementController $subject;
    private NormalizedParams $normalizedParams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = $this->get(ManagementController::class);
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    #[Test]
    public function overviewListsCreatorOfRedirects(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $body = (string)$this->subject->handleRequest($this->createRequest())->getBody();

        // The creator of a redirect, next to their avatar
        self::assertStringContainsString('<span>admin</span>', $body);
        // A deleted backend user, and one which never existed, are rendered without an
        // avatar, just like a redirect whose creator has not been tracked at all
        self::assertStringContainsString('<span class="text-variant">[Not Found]</span>', $body);
        self::assertStringContainsString('<span class="text-variant">[Not Tracked]</span>', $body);
        self::assertStringNotContainsString('deleted.editor', $body);
        // An avatar is only rendered for the four redirects created by an existing user
        self::assertSame(4, substr_count($body, 'class="avatar"'));
        // The filter allows to select the creators
        self::assertStringContainsString('name="demand[createdby]"', $body);
    }

    #[Test]
    public function overviewListsCreationDateOfEachRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $request = $this->createRequest()->withQueryParams(['orderField' => 'createdon', 'orderDirection' => 'desc']);
        $body = (string)$this->subject->handleRequest($request)->getBody();
        preg_match_all('/<td class="col-datetime col-nowrap">.*?(\d{4}-\d{2}-\d{2})/s', $body, $matches);

        // Each row shows its own creation date: uid 1 and 6 are dated in 2038, all others in 1970
        self::assertSame(
            ['2038-01-19', '2038-01-19', '1970-01-01', '1970-01-01', '1970-01-01', '1970-01-01', '1970-01-01'],
            $matches[1]
        );
    }

    #[Test]
    public function overviewFiltersRedirectsByCreator(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $request = $this->createRequest()->withQueryParams(['demand' => ['createdby' => 2]]);
        $body = (string)$this->subject->handleRequest($request)->getBody();

        self::assertStringContainsString('/foo/bar', $body);
        self::assertStringNotContainsString('/foo/baz', $body);
    }

    #[Test]
    public function overviewRendersAvatarsOfListedCreatorsOnly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $request = $this->createRequest()->withQueryParams(['demand' => ['createdby' => 1]]);
        $body = (string)$this->subject->handleRequest($request)->getBody();

        // Only the two listed redirects of "admin" get an avatar, while "editor" is
        // still offered in the filter, without their avatar having been rendered
        self::assertSame(2, substr_count($body, 'class="avatar"'));
        self::assertStringContainsString('<option value="2" >editor</option>', $body);
    }

    #[Test]
    public function overviewOffersASingleFilterOptionForCreatorsWithoutBackendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $body = (string)$this->subject->handleRequest($this->createRequest())->getBody();

        // Redirect 4 has a deleted and redirect 7 a non-existent creator, sharing one option
        self::assertSame(1, substr_count($body, '>[Not Found]</option>'));
        self::assertStringContainsString('<option value="-2" >[Not Found]</option>', $body);
    }

    #[Test]
    public function overviewFiltersRedirectsByCreatorsWithoutBackendUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_redirect.csv');
        $this->importCSVDataSet(__DIR__ . '/../Repository/Fixtures/sys_file.csv');

        $request = $this->createRequest()->withQueryParams(['demand' => ['createdby' => Demand::CREATOR_NOT_FOUND]]);
        $body = (string)$this->subject->handleRequest($request)->getBody();

        // The redirects of the deleted and the non-existent creator, but not the untracked one
        self::assertStringContainsString('/foo/baz', $body);
        self::assertStringContainsString('/foofile/linkparam', $body);
        self::assertStringNotContainsString('/bar/foo', $body);
    }

    #[Test]
    public function modifyRedirectManagementControllerViewDataEventIsTriggered(): void
    {
        $request = $this->createRequest();

        $setHosts = ['*', 'example.com'];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-redirect-management-controller-view-data-event',
            static function (ModifyRedirectManagementControllerViewDataEvent $event) use (
                &$modifyRedirectManagementControllerViewDataEvent,
                $setHosts
            ): void {
                $modifyRedirectManagementControllerViewDataEvent = $event;
                $event->setHosts($setHosts);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyRedirectManagementControllerViewDataEvent::class, 'modify-redirect-management-controller-view-data-event');

        $this->subject->handleRequest($request);

        self::assertInstanceOf(ModifyRedirectManagementControllerViewDataEvent::class, $modifyRedirectManagementControllerViewDataEvent);
        self::assertSame($setHosts, $modifyRedirectManagementControllerViewDataEvent->getHosts());
    }

    private function createRequest(): ServerRequest
    {
        $request = new ServerRequest('https://www.example.com/', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-redirects']))
            ->withAttribute('moduleData', new ModuleData('redirects', ['redirectType' => Demand::DEFAULT_REDIRECT_TYPE]));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }
}
