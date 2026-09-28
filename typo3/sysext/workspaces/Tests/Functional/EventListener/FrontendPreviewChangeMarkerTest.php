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

namespace TYPO3\CMS\Workspaces\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendPreviewChangeMarkerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FrontendPreviewChangeMarker.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->setUpFrontendRootPage(1, ['EXT:workspaces/Tests/Functional/EventListener/Fixtures/Frontend/setup.typoscript']);
    }

    #[Test]
    public function changedRecordsAreMarkedWithTheirStateInWorkspacePreview(): void
    {
        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/'));

        self::assertStringContainsString(
            '[' . $this->marker(1, 'modified') . 'Workspace text<!--/typo3-workspace-record-->]'
            . '[Unchanged text]'
            . '[' . $this->marker(3, 'new') . 'New text<!--/typo3-workspace-record-->]'
            . '[' . $this->marker(5, 'moved') . 'Moved text<!--/typo3-workspace-record-->]',
            $body
        );
    }

    #[Test]
    public function workspacePreviewRendersToggleWithLiveUrl(): void
    {
        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/'));

        self::assertStringContainsString('id="typo3-preview-highlight-changes" data-live-url="https://acme.com/?ADMCMD_prev=LIVE"', $body);
        self::assertMatchesRegularExpression('#<script type="module" nonce="[^"]+" src="[^"]*frontend-change-highlighter\.js[^"]*"></script>#', $body);
    }

    #[Test]
    public function allRecordsAreMarkedInLiveVersionRequestedForComparison(): void
    {
        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/')->withQueryParameter('ADMCMD_prev', 'LIVE'));

        self::assertStringContainsString(
            '[' . $this->marker(1) . 'Live text<!--/typo3-workspace-record-->]'
            . '[' . $this->marker(2) . 'Unchanged text<!--/typo3-workspace-record-->]'
            . '[' . $this->marker(5) . 'Moved text<!--/typo3-workspace-record-->]',
            $body
        );
        self::assertStringNotContainsString('New text', $body);
        self::assertStringNotContainsString('typo3-preview-info', $body);
    }

    #[Test]
    public function changedRecordsRenderedByContentObjectAreMarkedInWorkspacePreview(): void
    {
        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/')->withQueryParameter('type', '124'));

        self::assertStringContainsString(
            $this->marker(1, 'modified') . '[Workspace text]<!--/typo3-workspace-record-->'
            . '[Unchanged text]'
            . $this->marker(3, 'new') . '[New text]<!--/typo3-workspace-record-->'
            . $this->marker(5, 'moved') . '[Moved text]<!--/typo3-workspace-record-->',
            $body
        );
    }

    #[Test]
    public function allRecordsRenderedByContentObjectAreMarkedInLiveVersionRequestedForComparison(): void
    {
        $body = $this->requestInWorkspace(
            new InternalRequest('https://acme.com/')->withQueryParameter('type', '124')->withQueryParameter('ADMCMD_prev', 'LIVE')
        );

        self::assertStringContainsString(
            $this->marker(1) . '[Live text]<!--/typo3-workspace-record-->'
            . $this->marker(2) . '[Unchanged text]<!--/typo3-workspace-record-->'
            . $this->marker(5) . '[Moved text]<!--/typo3-workspace-record-->',
            $body
        );
    }

    #[Test]
    public function recordsAreNotMarkedWithoutBackendUser(): void
    {
        $body = (string)$this->executeFrontendSubRequest(
            new InternalRequest('https://acme.com/')->withQueryParameter('ADMCMD_prev', 'LIVE')
        )->getBody();

        self::assertStringContainsString('[Live text][Unchanged text][Moved text]', $body);
    }

    #[Test]
    public function recordsAreNotMarkedIfPreviewNotificationIsDisabled(): void
    {
        $this->addTypoScriptToTemplateRecord(1, 'page.config.disablePreviewNotification = 1');

        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/'));

        self::assertStringContainsString('[Workspace text][Unchanged text][New text][Moved text]', $body);
    }

    #[Test]
    public function markersAreRemovedFromResponsesWhichAreNotHtmlPages(): void
    {
        $body = $this->requestInWorkspace(new InternalRequest('https://acme.com/')->withQueryParameter('type', '123'));

        self::assertSame('[Workspace text][Unchanged text][New text][Moved text]', trim($body));
    }

    private function requestInWorkspace(InternalRequest $request): string
    {
        return (string)$this->executeFrontendSubRequest(
            $request,
            new InternalRequestContext()->withBackendUserId(1)->withWorkspaceId(91)
        )->getBody();
    }

    private function marker(int $uid, ?string $state = null): string
    {
        return '<!--typo3-workspace-record {"table":"tt_content","uid":' . $uid
            . ($state === null ? '' : ',"state":"' . $state . '"') . '}-->';
    }
}
