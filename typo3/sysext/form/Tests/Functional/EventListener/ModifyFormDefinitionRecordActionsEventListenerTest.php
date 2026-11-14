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

namespace TYPO3\CMS\Form\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ComponentGroup;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\EventListener\ModifyFormDefinitionRecordActionsEventListener;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModifyFormDefinitionRecordActionsEventListenerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/FormEditorLinkProvider/form_definition.csv');
    }

    #[Test]
    public function editActionLinksToFormEditorIfTheFormMayBeEdited(): void
    {
        $event = $this->dispatchForBackendUser(2);

        $editAction = $event->getAction('edit', ActionGroup::primary);
        self::assertInstanceOf(LinkButton::class, $editAction);
        $editUri = new Uri($editAction->getHref());
        self::assertStringEndsWith('typo3/module/form/editor', $editUri->getPath());
        parse_str($editUri->getQuery(), $query);
        self::assertSame('1', $query['formPersistenceIdentifier'] ?? null);
        self::assertNotEmpty($query['returnUrl'] ?? null);
    }

    #[Test]
    public function editActionIsRemovedIfTheFormMayNotBeEdited(): void
    {
        $event = $this->dispatchForBackendUser(3);

        self::assertFalse($event->hasAction('edit', ActionGroup::primary));
    }

    #[Test]
    public function deleteActionIsRemoved(): void
    {
        $event = $this->dispatchForBackendUser(1);

        self::assertFalse($event->hasAction('delete', ActionGroup::primary));
    }

    private function dispatchForBackendUser(int $backendUserUid): ModifyRecordListRecordActionsEvent
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $route = clone $this->get(Router::class)->getRoute('records');
        $route->setOption('_identifier', 'records');
        $request = new ServerRequest('https://typo3-testing.local/typo3/module/web/list')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest($request);

        $componentFactory = $this->get(ComponentFactory::class);
        $primary = new ComponentGroup('primary');
        $primary->add('edit', $componentFactory->createLinkButton()->setHref('#record-edit'));
        $primary->add('delete', $componentFactory->createLinkButton()->setHref('#record-delete'));

        $event = new ModifyRecordListRecordActionsEvent(
            $primary,
            new ComponentGroup('secondary'),
            $this->get(RecordFactory::class)->createRawRecord('form_definition', ['uid' => 1, 'pid' => 0]),
            $recordList,
            $request,
        );
        $this->get(ModifyFormDefinitionRecordActionsEventListener::class)->modifyRecordListRecordActions($event);
        return $event;
    }
}
