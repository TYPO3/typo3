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

namespace TYPO3\CMS\Reactions\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Reactions\Controller\ManagementController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ManagementControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ManagementControllerTest_reactions.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function listLinksTheTryOutToAModalRatherThanRenderingItInline(): void
    {
        $content = $this->renderModule();

        self::assertMatchesRegularExpression('#<typo3-reactions-try-out[^>]+url="[^"]*/ajax/reactions/try-out\?[^"]*reaction=30#', $content);
        self::assertStringContainsString('/ajax/reactions/try-out', $content);
        self::assertStringNotContainsString('curl -X POST', $content);
        self::assertStringNotContainsString('/typo3/reaction/8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f70', $content);
    }

    #[Test]
    public function listTitlesTheTryOutModalWithWhatItIsForAndTheReaction(): void
    {
        self::assertMatchesRegularExpression('#<typo3-reactions-try-out[^>]+modal-title="Try Out: Mapped Reaction"#', $this->renderModule());
    }

    #[Test]
    public function listOffersTheTryOutOnlyForReactionsWithAnImplementation(): void
    {
        self::assertSame(4, substr_count($this->renderModule(), '/ajax/reactions/try-out'));
    }

    #[Test]
    public function listOffersTheDryRunInsideTheTryOutRatherThanAsAnActionOfItsOwn(): void
    {
        $content = $this->renderModule();

        self::assertStringContainsString('Try Out', $content);
        self::assertStringNotContainsString('/ajax/reactions/dry-run', $content);
    }

    #[Test]
    public function listDoesNotRenderCodeWithAHardcodedDarkBackground(): void
    {
        $content = $this->renderModule();

        self::assertStringNotContainsString('bg-dark', $content);
        self::assertStringNotContainsString('text-bg-primary', $content);
    }

    #[Test]
    public function tryOutNamesThePayloadKeysOfThatReaction(): void
    {
        $content = $this->renderTryOut(30);

        self::assertStringContainsString('/typo3/reaction/8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f70', $content);
        self::assertStringContainsString('{&quot;headline&quot;:&quot;value&quot;,&quot;customer&quot;:{&quot;name&quot;:&quot;value&quot;}}', $content);
    }

    #[Test]
    public function tryOutFallsBackToAMinimalBodyWithoutPlaceholders(): void
    {
        $content = $this->renderTryOut(31);

        self::assertStringContainsString('{&quot;key&quot;:&quot;value&quot;}', $content);
    }

    #[Test]
    public function tryOutDeclaresTheContentTypeAndDropsTheUnreadAcceptHeader(): void
    {
        $content = $this->renderTryOut(30);

        self::assertStringContainsString('Content-Type: application/json', $content);
        self::assertStringNotContainsString('accept: application/json', $content);
    }

    #[Test]
    public function tryOutOffersToCopyTheUrlAndTheCommand(): void
    {
        $content = $this->renderTryOut(30);

        self::assertSame(2, substr_count($content, '<typo3-copy-to-clipboard'));
        self::assertMatchesRegularExpression('#<typo3-copy-to-clipboard[^>]+text="https?://[^"]+/typo3/reaction/8f5e5d0a-1f2b-4c3d-9e7a-0b1c2d3e4f70"#', $content);
        self::assertMatchesRegularExpression('#<typo3-copy-to-clipboard[^>]+text="curl -X POST [^"]+x-api-key: &lt;your-secret&gt;[^"]+"#', $content);
    }

    #[Test]
    public function tryOutOffersADryRunPrefilledWithTheSamplePayload(): void
    {
        $content = $this->renderTryOut(30);

        self::assertStringContainsString('data-reactions-dry-run', $content);
        self::assertStringContainsString('/ajax/reactions/dry-run', $content);
        self::assertMatchesRegularExpression('#<textarea[^>]+name="payload"[^>]*>[^<]*&quot;headline&quot;: &quot;value&quot;#', $content);
    }

    #[Test]
    public function tryOutEditsTheDryRunPayloadInAJsonCodeEditor(): void
    {
        $content = $this->renderTryOut(30);

        self::assertMatchesRegularExpression('#<typo3-t3editor-codemirror[^>]+mode="[^"]*json[^"]*"[^>]*>\s*<textarea[^>]+name="payload"#', $content);
    }

    #[Test]
    public function tryOutCodeEditorStartsWithoutWaitingToBeScrolledIntoView(): void
    {
        // The modal is a dialog in the top layer, which an observer rooted at the body never
        // sees intersect, so the lazily started editor would never leave its spinner.
        self::assertMatchesRegularExpression('#<typo3-t3editor-codemirror[^>]+nolazyload#', $this->renderTryOut(30));
    }

    #[Test]
    public function tryOutDryRunCannotBeSentBeforeTheScriptEnablesIt(): void
    {
        self::assertMatchesRegularExpression('#<button[^>]+data-reactions-dry-run-submit[^>]*\sdisabled#', $this->renderTryOut(30));
    }

    #[Test]
    public function tryOutDryRunCannotFallBackToANativeSubmit(): void
    {
        // Without the script a submit would navigate to the AJAX route and show its bare
        // response as a page of its own, so the form offers no way to submit natively.
        $content = $this->renderTryOut(30);

        self::assertStringNotContainsString('type="submit"', $content);
        self::assertDoesNotMatchRegularExpression('#<form[^>]*\saction=#', $content);
    }

    #[Test]
    public function dryRunShowsWhatWouldBeWrittenAndWritesNothing(): void
    {
        $content = $this->resolveDryRun(30, '{"headline":"Hello","customer":{"name":"Jane"}}');

        self::assertStringContainsString('Hello', $content);
        self::assertStringContainsString('Jane', $content);
        self::assertSame(0, $this->countPagesTitled('Hello'));
    }

    #[Test]
    public function dryRunNamesWhyAFieldWouldBeSkipped(): void
    {
        $content = $this->resolveDryRun(30, '{"headline":"Hello"}');

        self::assertMatchesRegularExpression('#nav_title.+The payload carries no value under the path of the placeholder#s', $content);
    }

    #[Test]
    public function dryRunResolvesAsTheImpersonatedUserRatherThanTheOneAsking(): void
    {
        // The admin asks, reaction 33 impersonates an editor who may not write nav_title.
        $content = $this->resolveDryRun(33, '{"headline":"Hello","customer":{"name":"Jane"}}');

        self::assertMatchesRegularExpression('#nav_title.+The impersonated user may not write this field#s', $content);
        self::assertStringNotContainsString('Jane', $content);
        self::assertSame(1, $GLOBALS['BE_USER']->getUserId());
    }

    #[Test]
    public function dryRunReadsInvalidJsonAsAnEmptyPayloadLikeTheEndpoint(): void
    {
        $content = $this->resolveDryRun(30, '{"headline":');

        self::assertStringContainsString('The payload is not a JSON object', $content);
        self::assertStringNotContainsString('No field of the record could be written', $content);
        self::assertStringNotContainsString('Fields that would be skipped', $content);
    }

    #[Test]
    public function dryRunReadsJsonThatIsNoObjectAsAnEmptyPayloadLikeTheEndpoint(): void
    {
        $content = $this->resolveDryRun(30, '"5"');

        self::assertStringContainsString('The payload is not a JSON object', $content);
        self::assertStringNotContainsString('A record would be created', $content);
    }

    #[Test]
    public function dryRunReadsASubmittedPayloadThatIsNoStringAsNoPayload(): void
    {
        $request = $this->createRequest('/reactions/dry-run', 30, 'POST')->withParsedBody(['payload' => ['{"headline":"Hello"}']]);

        $response = $this->get(ManagementController::class)->resolveDryRunAction($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('The payload is not a JSON object', (string)$response->getBody());
    }

    #[Test]
    public function dryRunNamesWhyTheEndpointWouldCreateNoRecord(): void
    {
        $content = $this->resolveDryRun(34, '{"headline":"Hello"}');

        self::assertStringContainsString('No record would be created', $content);
        self::assertStringContainsString('The reaction is disabled', $content);
        self::assertStringNotContainsString('A record would be created', $content);
    }

    #[Test]
    public function dryRunOfAWritableReactionNamesNothingInItsWay(): void
    {
        $content = $this->resolveDryRun(30, '{"headline":"Hello"}');

        self::assertStringContainsString('A record would be created', $content);
        self::assertStringNotContainsString('No record would be created', $content);
    }

    #[Test]
    public function ajaxRoutesInheritTheAccessOfTheModule(): void
    {
        $router = $this->get(Router::class);

        self::assertSame('integrations_reactions', $router->getRoute('ajax_reactions_try_out')->getOption('inheritAccessFromModule'));
        self::assertSame('integrations_reactions', $router->getRoute('ajax_reactions_dry_run')->getOption('inheritAccessFromModule'));
    }

    #[Test]
    public function dryRunOfAReactionThatCannotResolveARecordIsNotFound(): void
    {
        $response = $this->get(ManagementController::class)->resolveDryRunAction($this->createDryRunRequest(32, '{}'));

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function dryRunIsRefusedForANonAdmin(): void
    {
        $this->setUpBackendUser(9);

        self::assertSame(403, $this->get(ManagementController::class)->resolveDryRunAction($this->createDryRunRequest(30, '{}'))->getStatusCode());
    }

    #[Test]
    public function tryOutOfAnUnknownReactionIsNotFound(): void
    {
        $response = $this->get(ManagementController::class)->tryOutAction($this->createTryOutRequest(9999));

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function tryOutIsRefusedForANonAdmin(): void
    {
        $this->setUpBackendUser(9);

        $response = $this->get(ManagementController::class)->tryOutAction($this->createTryOutRequest(30));

        self::assertSame(403, $response->getStatusCode());
    }

    private function renderModule(): string
    {
        $request = new ServerRequest('https://example.com/typo3/module/integrations/reactions')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/integrations/reactions', [
                'packageName' => 'typo3/cms-reactions',
                '_identifier' => 'integrations_reactions',
            ]));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ManagementController::class)->handleRequest($request)->getBody();
    }

    private function renderTryOut(int $uid): string
    {
        $response = $this->get(ManagementController::class)->tryOutAction($this->createTryOutRequest($uid));
        self::assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    private function createTryOutRequest(int $uid): ServerRequest
    {
        return $this->createRequest('/reactions/try-out', $uid);
    }

    private function createDryRunRequest(int $uid, string $payload): ServerRequest
    {
        return $this->createRequest('/reactions/dry-run', $uid, 'POST')->withParsedBody(['payload' => $payload]);
    }

    private function createRequest(string $path, int $uid, string $method = 'GET'): ServerRequest
    {
        $request = new ServerRequest('https://example.com/typo3/ajax' . $path, $method)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route($path, [
                'packageName' => 'typo3/cms-reactions',
                '_identifier' => 'ajax_' . str_replace(['/', '-'], '_', trim($path, '/')),
            ]))
            ->withQueryParams(['reaction' => $uid]);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function resolveDryRun(int $uid, string $payload): string
    {
        $response = $this->get(ManagementController::class)->resolveDryRunAction($this->createDryRunRequest($uid, $payload));
        self::assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    private function countPagesTitled(string $title): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder->count('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title)))
            ->executeQuery()
            ->fetchOne();
    }
}
