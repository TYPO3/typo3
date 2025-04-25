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

import 'bootstrap';
import '../../renderable/clearable';
import { AbstractInteractableModule, type ModuleLoadedResponse } from '../abstract-interactable-module';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import DebounceEvent from '@typo3/core/event/debounce-event';
import '@typo3/backend/element/icon-element';
import RegularEvent from '@typo3/core/event/regular-event';
import { Collapse } from 'bootstrap';
import type { SelectPure } from 'select-pure/lib/components';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  fulltextSearch = '.t3js-upgradeDocs-fulltext-search',
  changeLogsForVersionContainer = '.t3js-version-changes',
  changeLogsForVersion = '.t3js-changelog-list',
  selectPureField = '.t3js-upgradeDocs-select-pure',
  upgradeDoc = '.t3js-upgrade-doc'
}

/**
 * Module: @typo3/install/module/upgrade-docs
 */
class UpgradeDocs extends AbstractInteractableModule {
  private selectPureField: SelectPure;
  private fulltextSearchField: HTMLInputElement;

  public override initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);

    this.loadModuleFrameAgnostic('select-pure').then((): void => {
      this.getContent();
    });

    // Mark a file as read
    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.markRead(target);
    }).delegateTo(currentModal, '.t3js-upgradeDocs-markRead');

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      this.unmarkRead(target);
    }).delegateTo(currentModal, '.t3js-upgradeDocs-unmarkRead');
  }

  private getContent(): void {
    const modalContent = this.getModalBody();

    (new AjaxRequest(Router.getUrl('upgradeDocsGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data: ModuleLoadedResponse = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.innerHTML = data.html;

            this.initializeFullTextSearch();
            this.initializeSelectPure();
            this.loadChangelogs();
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private loadChangelogs(): void {
    const promises: Array<Promise<void>> = [];
    const modalContent = this.getModalBody();
    this.currentModal.querySelectorAll<HTMLElement>(Identifiers.changeLogsForVersionContainer).forEach((el: HTMLElement): void => {
      const request = (new AjaxRequest(Router.getUrl('upgradeDocsGetChangelogForVersion')))
        .withQueryArguments({
          install: {
            version: el.dataset.version,
          },
        })
        .get({ cache: 'no-cache' })
        .then(
          async (response: AjaxResponse): Promise<void> => {
            const data: ModuleLoadedResponse = await response.resolve();
            if (data.success === true) {
              const panelGroup = el;
              const container = panelGroup.querySelector(Identifiers.changeLogsForVersion);
              container.innerHTML = data.html;
              this.moveNotRelevantDocuments(container);

              // Remove loading spinner form panel
              panelGroup.querySelector('.t3js-panel-loading').remove();
            } else {
              Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
            }
          },
          (error: AjaxResponse): void => {
            Router.handleAjaxError(error, modalContent);
          }
        );

      promises.push(request);
    });

    Promise.all(promises).then((): void => {
      this.fulltextSearchField.disabled = false;
      this.appendItemsToSelectPure();
    });
  }

  private initializeFullTextSearch(): void {
    this.fulltextSearchField = this.findInModal(Identifiers.fulltextSearch) as HTMLInputElement;
    const searchInput = <HTMLInputElement>this.fulltextSearchField;
    searchInput.clearable({
      onClear: (): void => {
        this.combinedFilterSearch();
      }
    });
    searchInput.focus();

    new DebounceEvent('keyup', (): void => {
      this.combinedFilterSearch();
    }).bindTo(searchInput);
  }

  private initializeSelectPure(): void {
    this.selectPureField = this.getModalBody().querySelector(Identifiers.selectPureField);
    this.selectPureField.addEventListener('change', () => {
      this.combinedFilterSearch();
      this.selectPureField.close();
    });
  }

  /**
   * Appends tags to the <select-pure> element in multiple steps:
   *
   * 1. create a flat CSV of tags
   * 2. create a Set() with those tags, automatically filtering duplicates
   * 3. reduce remaining duplicates due to the case sensitivity behavior of Set(), while keeping the original case of
   *    the first item of a set of dupes
   * 4. sort tags
   */
  private appendItemsToSelectPure(): void {
    let tagString = '';
    this.currentModal.querySelectorAll(Identifiers.upgradeDoc).forEach((element: HTMLElement): void => {
      tagString += element.dataset.itemTags + ',';
    });
    const tagSet = new Set(tagString.slice(0, -1).split(','));
    const uniqueTags = [...tagSet.values()].reduce((tagList: string[], tag: string): string[] => {
      const normalizedTag = tag.toLowerCase();
      if (tagList.every(otherElement => otherElement.toLowerCase() !== normalizedTag)) {
        tagList.push(tag);
      }

      return tagList;
    }, []).sort((a: string, b: string): number => {
      // Sort case-insensitive by name
      return a.toLowerCase().localeCompare(b.toLowerCase());
    });

    this.selectPureField.enable();
    for (const tag of uniqueTags) {
      const option = this.selectPureField.ownerDocument.createElement('option-pure');
      option.textContent = tag;
      option.setAttribute('value', tag);
      this.selectPureField.appendChild(option);
    }
  }

  private combinedFilterSearch(): void {
    const modalContent = this.getModalBody();
    const items = modalContent.querySelectorAll(Identifiers.upgradeDoc);
    if (this.selectPureField.values.length < 1 && this.fulltextSearchField.value.length < 1) {
      const expandedPanels = this.currentModal.querySelectorAll('.panel-version .panel-collapse.show');
      expandedPanels.forEach((panel: HTMLElement) => {
        new RegularEvent('hidden.bs.collapse', (): void => {
          if (this.currentModal.querySelectorAll('.panel-version .panel-collapse.collapsing').length === 0) {
            // Bootstrap doesn't offer promises to check whether all panels are collapsed, so we need a helper to do
            // something similar
            items.forEach((item: HTMLElement) => {
              item.classList.remove('hidden', 'searchhit', 'filterhit');
            });
          }
        }, { once: true }).bindTo(panel);

        Collapse.getOrCreateInstance(panel).hide();
      });
      return;
    }

    items.forEach((item: HTMLElement) => {
      item.classList.remove('searchhit', 'filterhit');
    });

    // apply tags
    if (this.selectPureField.values.length > 0) {
      items.forEach((item: HTMLElement) => {
        item.classList.add('hidden');
        item.classList.remove('filterhit');
      });

      const tagSelection = this.selectPureField.values.map((tag: string) => '[data-item-tags*="' + tag + '"]').join('');
      modalContent.querySelectorAll(tagSelection).forEach((result: HTMLElement) => {
        result.classList.remove('hidden');
        result.classList.add('searchhit', 'filterhit');
      });
    } else {
      items.forEach((item: HTMLElement) => {
        item.classList.add('filterhit');
        item.classList.remove('hidden');
      });
    }
    // apply fulltext search
    const typedQuery = this.fulltextSearchField.value;
    modalContent.querySelectorAll('.filterhit').forEach((element: Element): void => {
      if (element.textContent.toLowerCase().trim().includes(typedQuery.toLowerCase())) {
        element.classList.remove('hidden');
        element.classList.add('searchhit');
      } else {
        element.classList.remove('searchhit');
        element.classList.add('hidden');
      }
    });

    modalContent.querySelectorAll('.searchhit').forEach((hitElement: HTMLElement) => {
      const panelElement = hitElement.closest('.panel-collapse');

      window.setTimeout((): void => {
        Collapse.getOrCreateInstance(panelElement).show();
      }, 20);
    });

    // Check for empty panels
    modalContent.querySelectorAll('.panel-version').forEach((element: Element): void => {
      if (element.querySelectorAll('.searchhit, .filterhit').length < 1) {
        const panelElement = element.querySelector(':scope > .panel-collapse');

        Collapse.getOrCreateInstance(panelElement).hide();
      }
    });
  }

  /**
   * Moves all documents that are either read or not affected
   */
  private moveNotRelevantDocuments(container: Element): void {
    this.findInModal('.panel-body-read').append(container.querySelector('[data-item-state="read"]') ?? '');
    this.findInModal('.panel-body-not-affected').append(container.querySelector('[data-item-state="notAffected"]') ?? '');
  }

  private markRead(element: Element): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.upgradeDocsMarkReadToken;
    const button = element.closest('button');
    button.classList.toggle('t3js-upgradeDocs-unmarkRead');
    button.classList.toggle('t3js-upgradeDocs-markRead');
    button.querySelectorAll<HTMLElement>('typo3-backend-icon,.t3js-icon').forEach((iconElement) => {
      iconElement.outerHTML = '<typo3-backend-icon identifier="actions-ban" size="small"></typo3-backend-icon>';
    });
    this.findInModal('.panel-body-read').append(button.closest('.panel'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: button.dataset.filepath,
          token: executeToken,
          action: 'upgradeDocsMarkRead',
        },
      })
      .catch((error: AjaxResponse): void => {
        Router.handleAjaxError(error, modalContent);
      });
  }

  private unmarkRead(element: Element): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().dataset.upgradeDocsUnmarkReadToken;
    const button = element.closest('button');
    const version = button.closest<HTMLElement>('.panel').dataset.itemVersion;
    button.classList.toggle('t3js-upgradeDocs-markRead');
    button.classList.toggle('t3js-upgradeDocs-unmarkRead');
    button.querySelectorAll<HTMLElement>('typo3-backend-icon,.t3js-icon').forEach((iconElement) => {
      iconElement.outerHTML = '<typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>';
    });
    this.findInModal('*[data-group-version="' + version + '"] .panel-body').append(button.closest('.panel'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: button.dataset.filepath,
          token: executeToken,
          action: 'upgradeDocsUnmarkRead',
        },
      })
      .catch((error: AjaxResponse): void => {
        Router.handleAjaxError(error, modalContent);
      });
  }
}

export default new UpgradeDocs();
