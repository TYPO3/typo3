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

import { Collapse } from 'bootstrap';
import SecurityUtility from '@typo3/core/security-utility';
import Modal from '@typo3/backend/modal';
import RegularEvent from '@typo3/core/event/regular-event';
import Severity from '@typo3/backend/severity';
import ClientStorage from '@typo3/backend/storage/client';
import FormEngine from '@typo3/backend/form-engine';
import coreLabels from '~labels/core.core';
import backendAltDocLabels from '~labels/backend.alt_doc';
import type FlexFormSectionContainer from './flex-form-section-container';

enum Selectors {
  actionFieldSelector = '.t3js-flex-control-action',
  sectionContentContainerSelector = '.t3js-flex-section-content',
  sectionContainerLabelSelector = '.t3js-formengine-label',
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
  private readonly toggleKeyInLocalStorage: string;

  private readonly panelHeading: HTMLElement;
  private readonly panelButton: HTMLElement;

  constructor(parentContainer: FlexFormSectionContainer, container: HTMLElement) {
    this.securityUtility = new SecurityUtility();
    this.parentContainer = parentContainer;
    this.container = container;
    this.containerContent = container.querySelector(Selectors.sectionContentContainerSelector);
    this.containerId = container.dataset.flexformContainerId;
    this.toggleKeyInLocalStorage = `formengine-flex-${parentContainer.getSectionContainer().id}-${this.containerId}-collapse`;

    this.panelButton = container.querySelector(':scope > .panel-heading .panel-button');
    this.panelHeading = this.panelButton.closest('.panel-heading');

    this.registerEvents();
  }

  private static getCollapseInstance(container: HTMLElement, toggle: boolean): Collapse {
    return Collapse.getInstance(container) ?? new Collapse(container, { toggle });
  }

  public getCollapseContent(): HTMLElement {
    return this.containerContent;
  }

  public getStatus(): ContainerStatus {
    return {
      id: this.containerId,
      collapsed: this.panelButton.getAttribute('aria-expanded') === 'false',
    };
  }

  private async registerEvents(): Promise<void> {
    if (this.parentContainer.isRestructuringAllowed()) {
      this.registerDelete();
    }

    this.registerPanelToggle();
    await this.registerToggle();
    this.registerPreviewUpdate();
  }

  private registerDelete(): void {
    new RegularEvent('click', (): void => {
      const title = backendAltDocLabels.get('flexform.section.delete.title');
      const content = backendAltDocLabels.get('flexform.section.delete.message');
      const modal = Modal.confirm(title, content, Severity.warning, [
        {
          text: backendAltDocLabels.get('buttons.confirm.delete_record.no'),
          active: true,
          btnClass: 'btn-default',
          name: 'no',
        },
        {
          text: backendAltDocLabels.get('buttons.confirm.delete_record.yes'),
          btnClass: 'btn-warning',
          name: 'yes',
        },
      ]);
      modal.addEventListener('button.clicked', (modalEvent: Event): void => {
        if ((modalEvent.target as HTMLButtonElement).name === 'yes') {
          const actionField = this.container.querySelector(Selectors.actionFieldSelector) as HTMLInputElement;
          actionField.value = 'DELETE';

          this.container.appendChild(actionField);
          this.container.classList.add('t3-flex-section--deleted');
          this.container.closest('.t3-form-field-container.t3-form-flex')?.querySelector(Selectors.sectionContainerLabelSelector)?.classList.add('has-change');
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

        modal.hideModal();
      });
    }).bindTo(this.container.querySelector(Selectors.deleteContainerButtonSelector));
  }

  private async registerToggle(): Promise<void> {
    const isCollapsed = (ClientStorage.get(this.toggleKeyInLocalStorage) ?? '1') === '1';
    FlexFormContainerContainer.getCollapseInstance(this.containerContent, !isCollapsed);
    await FormEngine.ready();
    this.generatePreview();
  }

  private registerPanelToggle(): void {
    ['hide.bs.collapse', 'show.bs.collapse'].forEach((eventName): void => {
      new RegularEvent(eventName, (e: Event): void => {
        if (e.target !== this.containerContent) {
          return;
        }
        const isCollapsing = e.type === 'hide.bs.collapse';
        ClientStorage.set(this.toggleKeyInLocalStorage, isCollapsing ? '1' : '0');
      }).bindTo(this.containerContent);
    });
  }

  private registerPreviewUpdate(): void {
    ['input', 'change'].forEach((eventName): void => {
      new RegularEvent(eventName, (): void => {
        this.generatePreview();
      }).delegateTo(this.containerContent, 'input[type="text"], textarea');
    });
  }

  private generatePreview(): void {
    let previewContent = '';
    const formFields: NodeListOf<HTMLInputElement|HTMLTextAreaElement> = this.containerContent.querySelectorAll('input[type="text"], textarea');
    for (const field of formFields) {
      let content = this.securityUtility.stripHtml(field.value);
      if (content.length > 50) {
        content = content.substring(0, 50) + '...';
      }
      previewContent += (previewContent ? ' / ' : '') + content;
    }

    if (previewContent === '') {
      previewContent = '[' + coreLabels.get('labels.no_title') + ']';
    }

    this.panelHeading.querySelector(Selectors.contentPreviewSelector).textContent = previewContent;
  }
}

export default FlexFormContainerContainer;
