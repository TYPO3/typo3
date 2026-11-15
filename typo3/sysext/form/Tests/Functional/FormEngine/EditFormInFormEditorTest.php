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

namespace TYPO3\CMS\Form\Tests\Functional\FormEngine;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EditFormInFormEditorTest extends FunctionalTestCase
{
    private const RECORD_EDIT_URL = '/typo3/record/edit?edit%5Btt_content%5D%5B10%5D=edit&returnUrl=%2Ftypo3%2Fmodule%2Fweb%2Flayout%3Fid%3D1';
    private const CONTEXTUAL_EDIT_URL = '/typo3/record/edit/contextual?edit%5Btt_content%5D%5B10%5D=edit&returnUrl=%2Ftypo3%2Fmodule%2Fweb%2Flayout%3Fid%3D1%23element-tt_content-10';

    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/form_definition.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/sys_refindex.csv');
    }

    #[Test]
    public function buttonLinksToTheSelectedFormAndReturnsToTheRecord(): void
    {
        $result = $this->renderButton(2, '1', 'record_edit', self::RECORD_EDIT_URL);

        $link = $this->getLink($result['html']);
        self::assertSame('Edit in form editor', trim($link->textContent));
        self::assertStringContainsString('btn', $link->getAttribute('class'));
        self::assertSame('data[tt_content][10][pi_flexform][data][sDEF][lDEF][settings.persistenceIdentifier][vDEF]', $link->getAttribute('data-element'));
        self::assertSame('1', $link->getAttribute('data-persistence-identifier'));
        $query = $this->getQueryOfHref($link->getAttribute('href'));
        self::assertSame('1', $query['formPersistenceIdentifier'] ?? null);
        self::assertSame(rawurldecode(self::RECORD_EDIT_URL), rawurldecode($query['returnUrl'] ?? ''));
        self::assertSame(
            '@typo3/form/backend/form-engine/edit-form-control.js',
            $result['javaScriptModules'][0]->getName()
        );
    }

    #[Test]
    public function buttonReturnsToThePageModuleFromTheContextualEditSheet(): void
    {
        $result = $this->renderButton(2, '1', 'record_edit_contextual', self::CONTEXTUAL_EDIT_URL);

        $query = $this->getQueryOfHref($this->getLink($result['html'])->getAttribute('href'));
        self::assertSame('/typo3/module/web/layout?id=1#element-tt_content-10', $query['returnUrl'] ?? null);
    }

    #[Test]
    public function buttonIsNotRenderedWithoutAccessToTheFormEditor(): void
    {
        self::assertSame('', $this->renderButton(3, '1', 'record_edit', self::RECORD_EDIT_URL)['html']);
    }

    #[Test]
    public function buttonIsNotRenderedIfNoFormIsSelected(): void
    {
        self::assertSame('', $this->renderButton(2, '', 'record_edit', self::RECORD_EDIT_URL)['html']);
    }

    #[Test]
    public function buttonIsNotRenderedForANewRecord(): void
    {
        self::assertSame('', $this->renderButton(2, '1', 'record_edit', self::RECORD_EDIT_URL, 'new')['html']);
    }

    #[Test]
    public function buttonNamesHowManyContentElementsUseTheForm(): void
    {
        $html = $this->renderButton(2, '1', 'record_edit', self::RECORD_EDIT_URL)['html'];

        self::assertStringContainsString('This form is used by 3 content elements. Changes affect all of them.', $html);
        self::assertStringNotContainsString('not versioned', $html);
    }

    #[Test]
    public function buttonOmitsTheUsageForAFormUsedOnce(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/form_definition_single_use.csv');

        $html = $this->renderButton(2, '4', 'record_edit', self::RECORD_EDIT_URL)['html'];

        self::assertStringContainsString('Edit in form editor', $html);
        self::assertStringNotContainsString('This form is used by', $html);
    }

    #[Test]
    public function buttonTellsThatFormsAreNotVersionedInAWorkspace(): void
    {
        $html = $this->renderButton(2, '1', 'record_edit', self::RECORD_EDIT_URL, 'edit', 1)['html'];

        self::assertStringContainsString('Forms are not versioned. Changes in the form editor are live immediately.', $html);
    }

    private function renderButton(
        int $backendUserUid,
        string $selectedValue,
        string $routeIdentifier,
        string $currentEditingUrl,
        string $command = 'edit',
        int $workspace = 0,
    ): array {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $backendUser->workspace = $workspace;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $route = clone $this->get(Router::class)->getRoute($routeIdentifier);
        $route->setOption('_identifier', $routeIdentifier);
        $request = new ServerRequest('https://typo3-testing.local' . $currentEditingUrl)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $this->get(NodeFactory::class)->create([
            'renderType' => 'formEditorEditForm',
            'request' => $request,
            'tableName' => 'tt_content',
            'command' => $command,
            'returnUrl' => $currentEditingUrl,
            'renderData' => ['fieldWizardOptions' => []],
            'parameterArray' => [
                'itemFormElName' => 'data[tt_content][10][pi_flexform][data][sDEF][lDEF][settings.persistenceIdentifier][vDEF]',
                'itemFormElValue' => $selectedValue === '' ? [] : [$selectedValue],
                'fieldConf' => ['config' => ['type' => 'select', 'renderType' => 'selectSingle']],
            ],
        ])->render();
    }

    private function getLink(string $html): \DOMElement
    {
        $document = new \DOMDocument();
        $document->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);
        $link = $document->getElementsByTagName('a')->item(0);
        self::assertInstanceOf(\DOMElement::class, $link);
        return $link;
    }

    private function getQueryOfHref(string $href): array
    {
        parse_str(new Uri($href)->getQuery(), $query);
        return $query;
    }
}
