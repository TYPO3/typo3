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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import DocumentService from '@typo3/core/document-service';
import Modal from '@typo3/backend/modal';
import Utility from '@typo3/backend/utility';
import Workspaces from './workspaces';
import ThrottleEvent from '@typo3/core/event/throttle-event';
import '@typo3/workspaces/renderable/send-to-stage-form';
import RegularEvent from '@typo3/core/event/regular-event';

enum Identifiers {
  topbar = '.t3js-workspace-topbar',
  stageSliderContainer = '.t3js-stage-slider-container',
  stageSlider = '.t3js-stage-slider',
  liveView = '.t3js-workspace-view-live',
  workspaceView = '.t3js-workspace-view-workspace',
  sendToStageAction = '[data-action="send-to-stage"]',
  discardAction = '[data-action="discard"]',
  stageButtonsContainer = '.t3js-stage-buttons',
  previewModeContainer = '.t3js-preview-mode',
  activePreviewMode = '.t3js-active-preview-mode',
  workspacePreview = '.t3js-workspace-preview',
}

/**
 * Loaded in "Frontend workspace preview". Contains JS for "Send to stage ..." / "Discard"
 * buttons, preview mode selector, preview slider and so on.
 */
class Preview extends Workspaces {
  private currentSlidePosition: number = 100;
  private readonly elements: { [key: string]: HTMLElement } = {};

  constructor() {
    super();

    DocumentService.ready().then((): void => {
      this.getElements();
      this.resizeViews();
      this.registerEvents();
      this.initStageButtons();
    });
  }

  /**
   * Fetches and stores often required elements
   */
  private getElements(): void {
    this.elements.liveView = document.querySelector(Identifiers.liveView) as HTMLElement;
    this.elements.stageSliderContainer = document.querySelector(Identifiers.stageSliderContainer) as HTMLElement;
    this.elements.stageSlider = document.querySelector(Identifiers.stageSlider) as HTMLElement;
    this.elements.workspaceView = document.querySelector(Identifiers.workspaceView) as HTMLElement;
    this.elements.stageButtonsContainer = document.querySelector(Identifiers.stageButtonsContainer) as HTMLElement;
    this.elements.previewModeContainer = document.querySelector(Identifiers.previewModeContainer) as HTMLElement;
    this.elements.activePreviewMode = document.querySelector(Identifiers.activePreviewMode) as HTMLElement;
    this.elements.workspacePreview = document.querySelector(Identifiers.workspacePreview) as HTMLElement;
  }

  /**
   * Registers the events
   */
  private registerEvents(): void {
    new ThrottleEvent('resize', (): void => {
      this.resizeViews();
    }, 50).bindTo(window);
    new RegularEvent('click', this.renderDiscardWindow.bind(this)).delegateTo(document, Identifiers.discardAction);
    new RegularEvent('click', this.renderSendPageToStageWindow.bind(this)).delegateTo(document, Identifiers.sendToStageAction);
    new RegularEvent('click', () => {
      window.top.document.querySelectorAll('.t3js-workspace-recipient:not(:disabled)').forEach((element: HTMLInputElement) => {
        element.checked = true;
      });
    }).delegateTo(document, '.t3js-workspace-recipients-selectall');
    new RegularEvent('click', () => {
      window.top.document.querySelectorAll('.t3js-workspace-recipient:not(:disabled)').forEach((element: HTMLInputElement) => {
        element.checked = false;
      });
    }).delegateTo(document, '.t3js-workspace-recipients-deselectall');

    new ThrottleEvent('input', this.updateSlidePosition.bind(this), 10).bindTo(document.querySelector(Identifiers.stageSlider));
    new RegularEvent('click', this.changePreviewMode.bind(this)).delegateTo(this.elements.previewModeContainer, '[data-preview-mode]');
  }

  private initStageButtons(): void {
    this.sendRemoteRequest([
      this.generateRemotePayloadBody('updateStageChangeButtons', [TYPO3.settings.Workspaces.id]),
    ], Identifiers.topbar).then(async (response: AjaxResponse): Promise<void> => {
      this.renderStageButtons((await response.resolve())[0].result);
    });
  }

  /**
   * Renders the staging buttons
   */
  private renderStageButtons(buttons: string): void {
    this.elements.stageButtonsContainer.innerHTML = buttons;
  }

  /**
   * Updates the position of the comparison slider
   */
  private updateSlidePosition(e: Event): void {
    this.currentSlidePosition = parseInt((e.target as HTMLInputElement).value, 10);
    this.resizeViews();
  }

  /**
   * Resize the views based on the current viewport height and slider position
   */
  private resizeViews(): void {
    if (this.elements.activePreviewMode.dataset.activePreviewMode === 'slider') {
      this.elements.liveView.style.height = (100 - this.currentSlidePosition) + '%';
    }
  }

  /**
   * Renders the discard window
   */
  private renderDiscardWindow(): void {
    const modal = Modal.confirm(
      TYPO3.lang['window.discardAll.title'],
      TYPO3.lang['window.discardAll.message'],
      SeverityEnum.warning,
      [
        {
          text: TYPO3.lang.cancel,
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => {
            modal.hideModal();
          },
        },
        {
          text: TYPO3.lang.ok,
          btnClass: 'btn-warning',
          name: 'ok',
        },
      ],
    );
    modal.addEventListener('button.clicked', (e: Event): void => {
      if ((e.target as HTMLButtonElement).name === 'ok') {
        this.sendRemoteRequest([
          this.generateRemotePayloadBody('discardStagesFromPage', [TYPO3.settings.Workspaces.id]),
          this.generateRemotePayloadBody('updateStageChangeButtons', [TYPO3.settings.Workspaces.id]),
        ], Identifiers.topbar).then(async (response: AjaxResponse): Promise<void> => {
          modal.hideModal();
          this.renderStageButtons((await response.resolve())[1].result);
          // Reloading live view IFRAME
          this.elements.workspaceView.setAttribute('src', this.elements.workspaceView.getAttribute('src'));
        });
      }
    });
  }

  /**
   * Renders the "send page to stage" window
   */
  private renderSendPageToStageWindow(e: Event, target: HTMLElement): void {
    const direction = target.dataset.direction;
    let actionName;

    if (direction === 'prev') {
      actionName = 'sendPageToPreviousStage';
    } else if (direction === 'next') {
      actionName = 'sendPageToNextStage';
    } else {
      throw 'Invalid direction ' + direction + ' requested.';
    }

    this.sendRemoteRequest(
      this.generateRemotePayloadBody(actionName, [TYPO3.settings.Workspaces.id]),
      Identifiers.topbar
    ).then(async (response: AjaxResponse): Promise<void> => {
      const resolvedResponse = await response.resolve();
      const modal = this.renderSendToStageWindow(resolvedResponse);
      modal.addEventListener('button.clicked', (modalEvent: Event): void => {
        const modalTarget = modalEvent.target as HTMLButtonElement;
        if (modalTarget.name === 'ok') {
          const serializedForm = Utility.convertFormToObject(modal.querySelector('form'));
          serializedForm.affects = resolvedResponse[0].result.affects;
          serializedForm.stageId = parseInt(target.dataset.stageId, 10);
          this.sendRemoteRequest([
            this.generateRemotePayloadBody('sendCollectionToStage', [serializedForm]),
            this.generateRemotePayloadBody('updateStageChangeButtons', [TYPO3.settings.Workspaces.id]),
          ], Identifiers.topbar).then(async (updateResponse: AjaxResponse): Promise<void> => {
            modal.hideModal();
            this.renderStageButtons((await updateResponse.resolve())[1].result);
          });
        }
      });
    });
  }

  /**
   * Changes the preview mode
   */
  private changePreviewMode(e: Event, target: HTMLElement): void {
    e.preventDefault();

    const currentPreviewMode = this.elements.activePreviewMode.dataset.activePreviewMode;
    const newPreviewMode = target.dataset.previewMode;

    this.elements.activePreviewMode.textContent = target.textContent;
    this.elements.activePreviewMode.dataset.activePreviewMode = newPreviewMode;
    this.elements.workspacePreview.classList.remove('typo3-workspace-preview-' + currentPreviewMode);
    this.elements.workspacePreview.classList.add('typo3-workspace-preview-' + newPreviewMode);

    if (newPreviewMode === 'slider') {
      this.elements.stageSliderContainer.style.display = '';
      this.resizeViews();
    } else {
      this.elements.stageSliderContainer.style.display = 'none';
      this.elements.liveView.style.height = '';
    }
  }
}

export default new Preview();
