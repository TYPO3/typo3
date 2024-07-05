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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Container\FileReferenceContainer;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileReferenceContainerTest extends FunctionalTestCase
{
    private const string DELETE_CONTROL_MARKER = 't3js-editform-delete-inline-record';

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = false;
    }

    #[Test]
    public function collapseToggleUsesAttributeSelectorForDottedFlexFormIdentifier(): void
    {
        $this->setUpAdmin();
        $domObjectId = 'data-1-tt_content-13-pi_flexform---data---sDEF---lDEF---settings.image---vDEF';
        $html = $this->renderFileReference($domObjectId);

        self::assertStringContainsString(
            'id="' . $domObjectId . '-sys_file_reference-42_fields"',
            $html
        );
        self::assertStringContainsString(
            'data-bs-target="[id=&quot;' . $domObjectId . '-sys_file_reference-42_fields&quot;]"',
            $html
        );
        self::assertStringNotContainsString('data-bs-target="#', $html);
    }

    #[Test]
    public function fallbackIconHasNoTitleTooltip(): void
    {
        $this->setUpAdmin();
        $domObjectId = 'data-1-tt_content-13-pi_flexform---data---sDEF---lDEF---settings.image---vDEF';
        $html = $this->renderFileReference($domObjectId);
        $iconContainerId = $domObjectId . '-sys_file_reference-42_iconcontainer';

        self::assertMatchesRegularExpression(
            '#id="' . preg_quote($iconContainerId, '#') . '">\s*<span class="t3js-icon #',
            $html,
        );
    }

    /**
     * A file reference attached to a record on the root level - e.g. an additional file field
     * added to "sys_file_metadata" - has no parent page and thus no page permissions to derive
     * the delete permission from. DataHandler allows the deletion for a table declaring
     * "ignoreRootLevelRestriction", so the control must be rendered for an editor who may
     * modify "sys_file_reference". Without that access it has to stay hidden, and the root
     * level exception must not weaken the regular page based permission check either.
     */
    #[Test]
    #[DataProvider('deleteControlVisibilityDataProvider')]
    public function deleteControlVisibilityFollowsPermissions(?int $parentPageUid, int $backendUserUid, bool $deleteControlIsRendered): void
    {
        $this->setUpEditor($backendUserUid);

        self::assertSame(
            $deleteControlIsRendered,
            str_contains($this->renderControls($parentPageUid), self::DELETE_CONTROL_MARKER),
            $deleteControlIsRendered ? 'Delete control was not rendered' : 'Delete control was rendered unexpectedly',
        );
    }

    /**
     * @return \Generator<string, array{parentPageUid: int|null, backendUserUid: int, deleteControlIsRendered: bool}>
     */
    public static function deleteControlVisibilityDataProvider(): \Generator
    {
        yield 'root level, editor may modify sys_file_reference' => [
            'parentPageUid' => null,
            'backendUserUid' => 1,
            'deleteControlIsRendered' => true,
        ];
        yield 'root level, editor may not modify sys_file_reference' => [
            'parentPageUid' => null,
            'backendUserUid' => 2,
            'deleteControlIsRendered' => false,
        ];
        yield 'parent page without content edit permission' => [
            'parentPageUid' => 1,
            'backendUserUid' => 1,
            'deleteControlIsRendered' => false,
        ];
        yield 'parent page with content edit permission' => [
            'parentPageUid' => 2,
            'backendUserUid' => 1,
            'deleteControlIsRendered' => true,
        ];
    }

    private function setUpAdmin(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    private function setUpEditor(int $uid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileReferenceContainer.csv');
        $backendUser = $this->setUpBackendUser($uid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function renderFileReference(string $domObjectId): string
    {
        return $this->renderContainer([
            'databaseRow' => [
                'uid' => 42,
                'pid' => 1,
                'sys_language_uid' => 0,
                'uid_local' => [],
            ],
            'processedTca' => ['ctrl' => []],
            'recordTitle' => 'someFile.jpg',
            'parentPageRow' => ['uid' => 1],
            'inlineData' => [],
            'inlineStructure' => $this->get(InlineStackProcessor::class)->getStructureFromString(
                $domObjectId . '-sys_file_reference-42',
                $this->get(TcaSchemaFactory::class)->all()
            ),
            'inlineFirstPid' => 1,
            'inlineParentConfig' => ['inline' => ['first' => 0, 'last' => 0]],
            'inlineTopMostParentTableName' => 'tt_content',
            'inlineTopMostParentUid' => 13,
        ]);
    }

    /**
     * Renders a single, collapsed file reference of a parent record located on $parentPageUid.
     * A NULL $parentPageUid renders a file reference of a parent record on the root level.
     */
    private function renderControls(?int $parentPageUid): string
    {
        return $this->renderContainer([
            'vanillaUid' => 1,
            'databaseRow' => [
                'uid' => 1,
                'pid' => (int)$parentPageUid,
                'hidden' => 0,
            ],
            'processedTca' => $this->get(TcaSchemaFactory::class)->get('sys_file_reference')->getRawConfiguration(),
            'parentPageRow' => $parentPageUid === null ? null : BackendUtility::getRecord('pages', $parentPageUid),
            'recordTitle' => 'Test file reference',
            'columnsToProcess' => [],
            'inlineData' => [],
            'inlineStructure' => [],
            'inlineFirstPid' => (int)$parentPageUid,
            'inlineTopMostParentTableName' => 'sys_file_metadata',
            'inlineTopMostParentUid' => 1,
            'inlineParentUid' => 1,
            'inlineParentConfig' => [
                'inline' => [
                    'first' => 1,
                    'last' => 1,
                ],
                'appearance' => [
                    'enabledControls' => [
                        'delete' => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderContainer(array $data): string
    {
        $subject = $this->get(FileReferenceContainer::class);
        $subject->setData($data + [
            'command' => 'edit',
            'tableName' => 'sys_file_reference',
            'tcaSchemata' => $this->get(TcaSchemaFactory::class)->all(),
            'returnUrl' => '',
            'isInlineChildExpanded' => false,
            'isInlineDefaultLanguageRecordInLocalizedParentContext' => false,
            'request' => new ServerRequest()
                ->withAttribute('route', new Route('', []))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE),
        ]);

        return $subject->render()['html'];
    }
}
