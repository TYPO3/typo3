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

import {Collapse} from 'bootstrap';
import SecurityUtility from '@typo3/core/security-utility';
import FlexFormSectionContainer from './flex-form-section-container';
import Modal from '@typo3/backend/modal';
import RegularEvent from '@typo3/core/event/regular-event';
import Severity from '@typo3/backend/severity';

enum Selectors {
  toggleSelector = '[data-bs-toggle="flexform-inline"]',
  actionFieldSelector = '.t3js-flex-control-action',
  toggleFieldSelector = '.t3js-flex-control-toggle',
  controlSectionSelector = '.t3js-formengine-irre-control',
  sectionContentContainerSelector = '.t3js-flex-section-content',
  deleteContainerButtonSelector = '.t3js-delete',
  contentPreviewSelector = '.content-preview',
}

interface ContainerStatus {
  id: string;
  collapsed: boolean;
}

class FlexFormContainerContainer {
  private readonly securityUtility: SecurityUtility;
  private readonly parentContainer: FlexFormSectionContainer;
  private readonly container: HTMLElement;
  private readonly containerContent: HTMLElement;
  private readonly containerId: string;

  private readonly panelHeading: HTMLElement;
  private readonly panelButton: HTMLElement;
  private readonly toggleField: HTMLInputElement;

  private static getCollapseInstance(container: HTMLElement): Collapse {
    return Collapse.getInstance(container) ?? new Collapse(container, {toggle: false})
  }

  constructor(parentContainer: FlexFormSectionContainer, container: HTMLElement) {
    this.securityUtility = new SecurityUtility();
    this.parentContainer = parentContainer;
    this.container = container;
    this.containerContent = container.querySelector(Selectors.sectionContentContainerSelector);
    this.containerId = container.dataset.flexformContainerId;

    this.panelHeading = container.querySelector('[data-bs-target="#flexform-container-' + this.containerId + '"]');
    this.panelButton = this.panelHeading.querySelector('[aria-controls="flexform-container-' + this.containerId + '"]');
    this.toggleField = container.querySelector(Selectors.toggleFieldSelector);

    this.registerEvents();
    this.generatePreview();
  }

  public getStatus(): ContainerStatus {
    return {
      id: this.containerId,
      collapsed: this.panelButton.getAttribute('aria-expanded') === 'false',
    }
  }

  private registerEvents(): void {
    if (this.parentContainer.isRestructuringAllowed()) {
      this.registerDelete();
    }

    this.registerToggle();
    this.registerPanelToggle();
  }

  private registerDelete(): void {
    new RegularEvent('click', (): void => {
      const title = TYPO3.lang['flexform.section.delete.title'] || 'Delete this container?';
      const content = TYPO3.lang['flexform.section.delete.message'] || 'Are you sure you want to delete this container?';
      const $modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'no',
        },
        {
          text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, delete this container',
          btnClass: 'btn-warning',
          name: 'yes',
        },
      ]);
      $modal.on('button.clicked', (modalEvent: Event): void => {
        if ((modalEvent.target as HTMLAnchorElement).name === 'yes') {
          const actionField = this.container.querySelector(Selectors.actionFieldSelector) as HTMLInputElement;
          actionField.value = 'DELETE';

          this.container.appendChild(actionField);
          this.container.classList.add('t3-flex-section--deleted');
          new RegularEvent('transitionend', (): void => {
            this.container.classList.add('hidden');

            const event = new CustomEvent('formengine:flexform:container-deleted', {
              detail: {
                containerId: this.containerId
              }
            });
            this.parentContainer.getContainer().dispatchEvent(event);
          }).bindTo(this.container);
        }

        Modal.dismiss();
      });
    }).bindTo(this.container.querySelector(Selectors.deleteContainerButtonSelector));
  }

  private registerToggle(): void {
    new RegularEvent('click', (): void => {
      FlexFormContainerContainer.getCollapseInstance(this.containerContent).toggle();
      this.generatePreview();
    }).delegateTo(this.container, `${Selectors.toggleSelector} .form-irre-header-cell:not(${Selectors.controlSectionSelector}`);
  }

  private registerPanelToggle(): void {
    ['hide.bs.collapse', 'show.bs.collapse'].forEach((eventName: string): void => {
      new RegularEvent(eventName, (e: Event): void => {
        const collapseTriggered = e.type === 'hide.bs.collapse';

        this.toggleField.value = collapseTriggered ? '1' : '0';
        this.panelButton.setAttribute('aria-expanded', collapseTriggered ? 'false' : 'true');
        this.panelButton.parentElement.classList.toggle('collapsed', collapseTriggered);
      }).bindTo(this.containerContent);
    });
  }

  private generatePreview(): void {
    let previewContent = '';
    if (this.getStatus().collapsed) {
      const formFields: NodeListOf<HTMLInputElement|HTMLTextAreaElement> = this.containerContent.querySelectorAll('input[type="text"], textarea');
      for (let field of formFields) {
        let content = this.securityUtility.stripHtml(field.value);
        if (content.length > 50) {
          content = content.substring(0, 50) + '...';
        }
        previewContent += (previewContent ? ' / ' : '') + content;
      }
    }

    this.panelHeading.querySelector(Selectors.contentPreviewSelector).textContent = previewContent;
  }
}

export default FlexFormContainerContainer;
