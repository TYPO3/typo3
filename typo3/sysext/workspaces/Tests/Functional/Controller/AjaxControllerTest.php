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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Controller\AjaxController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AjaxControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_workspace.csv');
    }

    public static function switchWorkspaceActionOnlyRevealsAccessibleWorkspaceDataProvider(): \Generator
    {
        yield 'editor switches to workspace being member of' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 1,
            'expectedWorkspaceId' => 1,
            'expectedTitle' => 'Editor is member',
        ];
        yield 'editor switches to workspace being owner of' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 4,
            'expectedWorkspaceId' => 4,
            'expectedTitle' => 'Editor is owner',
        ];
        yield 'editor switches to workspace being member of by group' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 5,
            'expectedWorkspaceId' => 5,
            'expectedTitle' => 'Editor group is member',
        ];
        yield 'editor switches to workspace being owner of by group' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 6,
            'expectedWorkspaceId' => 6,
            'expectedTitle' => 'Editor group is owner',
        ];
        yield 'editor switches to live workspace' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 0,
            'expectedWorkspaceId' => 0,
            'expectedTitle' => null,
        ];
        yield 'editor switches to workspace not being member of' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 2,
            'expectedWorkspaceId' => 0,
            'expectedTitle' => null,
        ];
        yield 'editor switches to deleted workspace' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 3,
            'expectedWorkspaceId' => 0,
            'expectedTitle' => null,
        ];
        yield 'editor switches to non-existing workspace' => [
            'backendUserId' => 2,
            'requestedWorkspaceId' => 999,
            'expectedWorkspaceId' => 0,
            'expectedTitle' => null,
        ];
        yield 'admin switches to workspace not being member of' => [
            'backendUserId' => 1,
            'requestedWorkspaceId' => 2,
            'expectedWorkspaceId' => 2,
            'expectedTitle' => 'Workspace without editor',
        ];
    }

    #[Test]
    #[DataProvider('switchWorkspaceActionOnlyRevealsAccessibleWorkspaceDataProvider')]
    public function switchWorkspaceActionOnlyRevealsAccessibleWorkspaceUsingParsedBody(
        int $backendUserId,
        int $requestedWorkspaceId,
        int $expectedWorkspaceId,
        ?string $expectedTitle
    ): void {
        $request = (new ServerRequest())->withParsedBody(['workspaceId' => (string)$requestedWorkspaceId]);
        $this->assertSwitchWorkspaceResponse($backendUserId, $request, $expectedWorkspaceId, $expectedTitle);
    }

    #[Test]
    #[DataProvider('switchWorkspaceActionOnlyRevealsAccessibleWorkspaceDataProvider')]
    public function switchWorkspaceActionOnlyRevealsAccessibleWorkspaceUsingQueryParams(
        int $backendUserId,
        int $requestedWorkspaceId,
        int $expectedWorkspaceId,
        ?string $expectedTitle
    ): void {
        $request = (new ServerRequest())->withQueryParams(['workspaceId' => (string)$requestedWorkspaceId]);
        $this->assertSwitchWorkspaceResponse($backendUserId, $request, $expectedWorkspaceId, $expectedTitle);
    }

    private function assertSwitchWorkspaceResponse(
        int $backendUserId,
        ServerRequest $request,
        int $expectedWorkspaceId,
        ?string $expectedTitle
    ): void {
        $backendUser = $this->setUpBackendUser($backendUserId);
        $languageService = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['LANG'] = $languageService;
        $expectedTitle ??= $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:shortcut_onlineWS');

        $response = $this->get(AjaxController::class)->switchWorkspaceAction($request);
        $result = json_decode((string)$response->getBody(), true);

        self::assertSame($expectedTitle, $result['title']);
        self::assertSame($expectedWorkspaceId, $result['workspaceId']);
        self::assertSame($expectedWorkspaceId, $backendUser->workspace);
    }
}
