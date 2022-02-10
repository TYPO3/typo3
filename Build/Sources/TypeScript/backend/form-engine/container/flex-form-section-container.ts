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
import $ from 'jquery';
import Sortable from 'sortablejs';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import DocumentService from '@typo3/core/document-service';
import FlexFormContainerContainer from './flex-form-container-container';
import FormEngine from '@typo3/backend/form-engine';
import RegularEvent from '@typo3/core/event/regular-event';
import {JavaScriptItemProcessor} from '@typo3/core/java-script-item-processor';

enum Selectors {
  toggleAllSelector = '.t3-form-flexsection-toggle',
  addContainerSelector = '.t3js-flex-container-add',
  actionFieldSelector = '.t3js-flex-control-action',
  sectionContainerSelector = '.t3js-flex-section',
  sectionContentContainerSelector = '.t3js-flex-section-content',
  sortContainerButtonSelector = '.t3js-sortable-handle',
}

class FlexFormSectionContainer {
  private readonly sectionContainerId: string;
  private container: HTMLElement;
  private sectionContainer: HTMLElement;
  private allowRestructure: boolean = false;
  private flexformContainerContainers: FlexFormContainerContainer[] = [];

  private static getCollapseInstance(container: HTMLElement): Collapse {
    return Collapse.getInstance(container) ?? new Collapse(container, {toggle: false})
  }

  /**
   * @param {string} elementId
   */
  constructor(elementId: string) {
    this.sectionContainerId = elementId;

    DocumentService.ready().then((document: Document): void => {
      this.container = <HTMLElement>document.getElementById(elementId);
      this.sectionContainer = this.container.querySelector(this.container.dataset.section) as HTMLElement;
      this.allowRestructure = this.sectionContainer.dataset.t3FlexAllowRestructure === '1';

      this.registerEvents();
      this.registerContainers();
    });
  }

  public getContainer(): HTMLElement {
    return this.container;
  }

  public isRestructuringAllowed(): boolean {
    return this.allowRestructure;
  }

  private registerEvents(): void {
    if (this.allowRestructure) {
      this.registerSortable();
      this.registerContainerDeleted();
    }

    this.registerToggleAll();
    this.registerCreateNewContainer();
    this.registerPanelToggle();
  }

  private registerContainers(): void {
    const sectionContainerContainers: NodeListOf<HTMLElement> = this.container.querySelectorAll(Selectors.sectionContainerSelector);
    for (let sectionContainerContainer of sectionContainerContainers) {
      this.flexformContainerContainers.push(new FlexFormContainerContainer(this, sectionContainerContainer));
    }

    this.updateToggleAllState();
  }

  private getToggleAllButton(): HTMLButtonElement {
    return this.container.querySelector(Selectors.toggleAllSelector) as HTMLButtonElement;
  }

  private registerSortable(): void {
    new Sortable(this.sectionContainer, {
      group: this.sectionContainer.id,
      handle: Selectors.sortContainerButtonSelector,
      onSort: this.updateSorting,
    });
  }

  private updateSorting = (e: Sortable.SortableEvent): void => {
    const actionFields: NodeListOf<HTMLInputElement> = this.container.querySelectorAll(Selectors.actionFieldSelector);
    actionFields.forEach((element: HTMLInputElement, key: number): void => {
      element.value = key.toString();
    });

    this.updateToggleAllState();
    this.flexformContainerContainers.splice(e.newIndex, 0, this.flexformContainerContainers.splice(e.oldIndex, 1)[0]);
    document.dispatchEvent(new Event('formengine:flexform:sorting-changed'));
  }

  private registerToggleAll(): void {
    new RegularEvent('click', (e: Event): void => {
      const trigger = e.target as HTMLButtonElement;
      const showAll = trigger.dataset.expandAll === 'true';
      const collapsibles: NodeListOf<HTMLElement> = this.container.querySelectorAll(Selectors.sectionContentContainerSelector);

      for (let collapsible of collapsibles) {
        if (showAll) {
          FlexFormSectionContainer.getCollapseInstance(collapsible).show();
        } else {
          FlexFormSectionContainer.getCollapseInstance(collapsible).hide();
        }
      }
    }).bindTo(this.getToggleAllButton());
  }

  private registerCreateNewContainer(): void {
    new RegularEvent('click', (e: Event, target: HTMLElement): void => {
      e.preventDefault();
      this.createNewContainer(target.dataset);
    }).delegateTo(this.container, Selectors.addContainerSelector);
  }

  private createNewContainer(dataset: DOMStringMap): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.record_flex_container_add)).post({
      vanillaUid: dataset.vanillauid,
      databaseRowUid: dataset.databaserowuid,
      command: dataset.command,
      tableName: dataset.tablename,
      fieldName: dataset.fieldname,
      recordTypeValue: dataset.recordtypevalue,
      dataStructureIdentifier: JSON.parse(dataset.datastructureidentifier),
      flexFormSheetName: dataset.flexformsheetname,
      flexFormFieldName: dataset.flexformfieldname,
      flexFormContainerName: dataset.flexformcontainername,
    }).then(async (response: AjaxResponse): Promise<any> => {
      const data = await response.resolve();
      const createdContainer = new DOMParser().parseFromString(data.html, 'text/html').body.firstElementChild as HTMLElement;

      this.flexformContainerContainers.push(new FlexFormContainerContainer(this, createdContainer));

      const sectionContainer = document.querySelector(dataset.target);
      sectionContainer.insertAdjacentElement('beforeend', createdContainer);

      if (data.scriptItems instanceof Array && data.scriptItems.length > 0) {
        const processor = new JavaScriptItemProcessor();
        processor.processItems(data.scriptItems);
      }

      // @todo deprecate or remove with TYPO3 v12.0
      if (data.scriptCall && data.scriptCall.length > 0) {
        $.each(data.scriptCall, function (index: number, value: string): void {
          // eslint-disable-next-line no-eval
          eval(value);
        });
      }
      if (data.stylesheetFiles && data.stylesheetFiles.length > 0) {
        $.each(data.stylesheetFiles, function (index: number, stylesheetFile: string): void {
          let element = document.createElement('link');
          element.rel = 'stylesheet';
          element.type = 'text/css';
          element.href = stylesheetFile;
          document.head.appendChild(element);
        });
      }

      this.updateToggleAllState();

      FormEngine.reinitialize();
      FormEngine.Validation.initializeInputFields();
      FormEngine.Validation.validate(sectionContainer);
    });
  }

  private registerContainerDeleted(): void {
    new RegularEvent('formengine:flexform:container-deleted', (e: CustomEvent): void => {
      const deletedContainerId = e.detail.containerId;
      this.flexformContainerContainers = this.flexformContainerContainers.filter(
        flexformContainerContainer => flexformContainerContainer.getStatus().id !== deletedContainerId
      );

      FormEngine.Validation.validate(this.container);
      this.updateToggleAllState();
    }).bindTo(this.container);
  }

  private registerPanelToggle(): void {
    ['hide.bs.collapse', 'show.bs.collapse'].forEach((eventName: string): void => {
      new RegularEvent(eventName, (): void => {
        this.updateToggleAllState();
      }).delegateTo(this.container, Selectors.sectionContentContainerSelector);
    });
  }

  private updateToggleAllState(): void {
    if (this.flexformContainerContainers.length > 0) {
      const firstContainer = this.flexformContainerContainers.find(Boolean);
      this.getToggleAllButton().dataset.expandAll = firstContainer.getStatus().collapsed === true ? 'true' : 'false'
    }
  }
}

export default FlexFormSectionContainer;
