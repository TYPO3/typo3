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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListTableActionsEvent;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Service\FormEditorLinkProvider;

/**
 * Event listener to replace the standard edit/delete actions for form_definition
 * records in the record list with form-editor-specific actions.
 *
 * - The "edit" action is replaced with a link to the Form Editor module, or
 *   removed if the backend user may not edit the form there.
 * - The "delete" action is removed, as deletion should only happen through the Form Manager.
 *
 * @internal
 */
final readonly class ModifyFormDefinitionRecordActionsEventListener
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
        private FormEditorLinkProvider $formEditorLinkProvider,
    ) {}

    #[AsEventListener('form-framework/modify-form-definition-record-list-actions', method: 'modifyRecordListRecordActions')]
    public function modifyRecordListRecordActions(ModifyRecordListRecordActionsEvent $event): void
    {
        if ($event->getRecord()->getMainType() !== FormDefinitionRepository::TABLE_NAME) {
            return;
        }

        // Replace the "edit" action with a link to the Form Editor. The TCA of
        // form_definition is read-only, so FormEngine is no alternative to it.
        if ($event->hasAction('edit', ActionGroup::primary)) {
            $editUri = $this->formEditorLinkProvider->getEditUri(
                (string)$event->getRecord()->getUid(),
                (string)$event->getRecordList()->listURL(),
            );
            if ($editUri === null) {
                $event->removeAction('edit', ActionGroup::primary);
            } else {
                $editButton = $this->componentFactory->createLinkButton()
                    ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
                    ->setTitle($GLOBALS['LANG']->sL('core.mod_web_list:edit'))
                    ->setHref((string)$editUri);
                $event->setAction($editButton, 'edit', ActionGroup::primary);
            }
        }

        // The “delete” action is being removed because references in the FlexForm are not taken into account
        // (no warning is displayed).
        $event->removeAction('delete', ActionGroup::primary);
    }

    /**
     * The “edit” action is being removed, as it is not possible to edit multiple forms simultaneously.
     * The form editor should always be used for editing.
     * The “delete” action is being removed because references in the FlexForm are not taken into account
     * (no warning is displayed).
     */
    #[AsEventListener('form-framework/modify-form-definition-record-list-table-actions', method: 'modifyRecordListTableActions')]
    public function modifyRecordListTableActions(ModifyRecordListTableActionsEvent $event): void
    {
        if ($event->getTable() !== FormDefinitionRepository::TABLE_NAME) {
            return;
        }
        $event->removeAction('edit');
        $event->removeAction('delete');
    }
}
