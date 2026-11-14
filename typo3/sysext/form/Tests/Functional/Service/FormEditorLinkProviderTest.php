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

namespace TYPO3\CMS\Form\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Service\FormEditorLinkProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormEditorLinkProviderTest extends FunctionalTestCase
{
    private const EXTENSION_FORM = 'EXT:form_storage_tests/Resources/Private/Forms/ContactForm.form.yaml';

    protected array $coreExtensionsToLoad = ['form'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_storage_tests',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/form_definition.csv');
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://typo3-testing.local/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    public static function canEditDataProvider(): iterable
    {
        yield 'admin, database form' => [1, '1', true];
        yield 'editor with module and write access, database form' => [2, '1', true];
        yield 'editor without form editor module' => [3, '1', false];
        yield 'editor with read-only access to form_definition' => [4, '1', false];
        yield 'deleted database form' => [1, '2', false];
        yield 'not existing database form' => [1, '4711', false];
        yield 'empty identifier' => [1, '', false];
        yield 'identifier no storage supports' => [1, 'not-a-form', false];
        yield 'extension form without allowSaveToExtensionPaths' => [1, self::EXTENSION_FORM, false];
    }

    #[Test]
    #[DataProvider('canEditDataProvider')]
    public function canEditDecidesWhetherTheFormEditorMayOpenAForm(int $backendUserUid, string $persistenceIdentifier, bool $expected): void
    {
        $this->setUpBackendUser($backendUserUid);

        self::assertSame($expected, $this->get(FormEditorLinkProvider::class)->canEdit($persistenceIdentifier));
    }

    #[Test]
    public function canEditReturnsFalseWithoutBackendUser(): void
    {
        unset($GLOBALS['BE_USER']);

        self::assertFalse($this->get(FormEditorLinkProvider::class)->canEdit('1'));
    }

    #[Test]
    public function getEditUriLinksToTheFormEditorWithReturnUrl(): void
    {
        $this->setUpBackendUser(2);

        $uri = $this->get(FormEditorLinkProvider::class)->getEditUri('1', '/typo3/module/web/list?id=0');

        self::assertNotNull($uri);
        self::assertStringEndsWith('typo3/module/form/editor', $uri->getPath());
        parse_str($uri->getQuery(), $query);
        self::assertSame('1', $query['formPersistenceIdentifier'] ?? null);
        self::assertSame('/typo3/module/web/list?id=0', $query['returnUrl'] ?? null);
    }

    #[Test]
    public function getEditUriOmitsEmptyReturnUrl(): void
    {
        $this->setUpBackendUser(1);

        $uri = $this->get(FormEditorLinkProvider::class)->getEditUri('1');

        self::assertNotNull($uri);
        parse_str($uri->getQuery(), $query);
        self::assertArrayNotHasKey('returnUrl', $query);
    }

    #[Test]
    public function getEditUriReturnsNullIfTheFormMayNotBeEdited(): void
    {
        $this->setUpBackendUser(3);

        self::assertNull($this->get(FormEditorLinkProvider::class)->getEditUri('1', '/typo3/module/web/list?id=0'));
    }
}
