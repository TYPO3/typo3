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
import $ from 'jquery';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import '../../renderable/clearable';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import Router from '../../router';
import DebounceEvent from '@typo3/core/event/debounce-event';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/install/module/upgrade-docs
 */
class UpgradeDocs extends AbstractInteractableModule {
  private selectorFulltextSearch: string = '.t3js-upgradeDocs-fulltext-search';
  private selectorChosenField: string = '.t3js-upgradeDocs-chosen-select';
  private selectorChangeLogsForVersionContainer: string = '.t3js-version-changes';
  private selectorChangeLogsForVersion: string = '.t3js-changelog-list';
  private selectorUpgradeDoc: string = '.t3js-upgrade-doc';

  private chosenField: JQuery;
  private fulltextSearchField: JQuery;

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    const isInIframe = (window.location !== window.parent.location);
    if (isInIframe) {
      topLevelModuleImport('@typo3/install/chosen.jquery.min.js').then((): void => {
        this.getContent();
      });
    } else {
      import('@typo3/install/chosen.jquery.min').then((): void => {
        this.getContent();
      });
    }

    // Mark a file as read
    currentModal.on('click', '.t3js-upgradeDocs-markRead', (e: JQueryEventObject): void => {
      this.markRead(e.target);
    });
    currentModal.on('click', '.t3js-upgradeDocs-unmarkRead', (e: JQueryEventObject): void => {
      this.unmarkRead(e.target);
    });

    // Make jquerys "contains" work case-insensitive
    $.expr[':'].contains = $.expr.createPseudo((arg: string): ((elem: JQuery) => boolean) => {
      return (elem: JQuery): boolean => {
        return $(elem).text().toUpperCase().includes(arg.toUpperCase());
      };
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();

    (new AjaxRequest(Router.getUrl('upgradeDocsGetContent')))
      .get({ cache: 'no-cache' })
      .then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.empty().append(data.html);

            this.initializeFullTextSearch();
            this.initializeChosenSelector();
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
    this.findInModal(this.selectorChangeLogsForVersionContainer).each((index: number, el: HTMLElement): void => {
      const request = (new AjaxRequest(Router.getUrl('upgradeDocsGetChangelogForVersion')))
        .withQueryArguments({
          install: {
            version: el.dataset.version,
          },
        })
        .get({ cache: 'no-cache' })
        .then(
          async (response: AjaxResponse): Promise<void> => {
            const data = await response.resolve();
            if (data.success === true) {
              const $panelGroup = $(el);
              const $container = $panelGroup.find(this.selectorChangeLogsForVersion);
              $container.html(data.html);
              this.moveNotRelevantDocuments($container);

              // Remove loading spinner form panel
              $panelGroup.find('.t3js-panel-loading').remove();
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
      this.fulltextSearchField.prop('disabled', false);
      this.appendItemsToChosenSelector();
    });
  }

  private initializeFullTextSearch(): void {
    this.fulltextSearchField = this.findInModal(this.selectorFulltextSearch);
    const searchInput = <HTMLInputElement>this.fulltextSearchField.get(0);
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

  private initializeChosenSelector(): void {
    this.chosenField = this.getModalBody().find(this.selectorChosenField);

    const config: { [key: string]: { [key: string]: string|number|boolean } } = {
      '.chosen-select': { width: '100%', placeholder_text_multiple: 'tags' },
      '.chosen-select-deselect': { allow_single_deselect: true },
      '.chosen-select-no-single': { disable_search_threshold: 10 },
      '.chosen-select-no-results': { no_results_text: 'Oops, nothing found!' },
      '.chosen-select-width': { width: '100%' },
    };
    for (const selector in config) {
      if (selector in config) {
        this.findInModal(selector).chosen(config[selector]);
      }
    }
    this.chosenField.on('change', (): void => {
      this.combinedFilterSearch();
    });
  }

  /**
   * Appends tags to the chosen selector in multiple steps:
   *
   * 1. create a flat CSV of tags
   * 2. create a Set() with those tags, automatically filtering duplicates
   * 3. reduce remaining duplicates due to the case sensitivity behavior of Set(), while keeping the original case of
   *    the first item of a set of dupes
   * 4. sort tags
   */
  private appendItemsToChosenSelector(): void {
    let tagString = '';
    $(this.findInModal(this.selectorUpgradeDoc)).each((index: number, element: Element): void => {
      tagString += $(element).data('item-tags') + ',';
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

    this.chosenField.prop('disabled', false);
    for (const tag of uniqueTags) {
      this.chosenField.append($('<option>').text(tag));
    }
    this.chosenField.trigger('chosen:updated');
  }

  private combinedFilterSearch(): void {
    const modalContent = this.getModalBody();
    const $items = modalContent.find(this.selectorUpgradeDoc);
    if (this.chosenField.val().length < 1 && this.fulltextSearchField.val().length < 1) {
      const $expandedPanels = this.currentModal.find('.panel-version .panel-collapse.show');
      $expandedPanels.one('hidden.bs.collapse', (): void => {
        if (this.currentModal.find('.panel-version .panel-collapse.collapsing').length === 0) {
          // Bootstrap doesn't offer promises to check whether all panels are collapsed, so we need a helper to do
          // something similar
          $items.removeClass('searchhit filterhit');
        }
      });
      $expandedPanels.collapse('hide');
      return;
    }

    $items.removeClass('searchhit filterhit');

    // apply tags
    if (this.chosenField.val().length > 0) {
      $items
        .addClass('hidden')
        .removeClass('filterhit');

      const tagSelection = this.chosenField.val().map((tag: string) => '[data-item-tags*="' + tag + '"]').join('');
      modalContent.find(tagSelection)
        .removeClass('hidden')
        .addClass('searchhit filterhit');
    } else {
      $items
        .addClass('filterhit')
        .removeClass('hidden');
    }
    // apply fulltext search
    const typedQuery = this.fulltextSearchField.val();
    modalContent.find('.filterhit').each((index: number, element: Element): void => {
      const $item = $(element);
      if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
        $item.removeClass('hidden').addClass('searchhit');
      } else {
        $item.removeClass('searchhit').addClass('hidden');
      }
    });

    modalContent.find('.searchhit').closest('.panel-collapse').each((index: number, item: Element): void => {
      // This is a workaround to improve the browser performance as the panels are not expanded at once
      window.setTimeout((): void => {
        $(item).collapse('show');
      }, 20);
    });

    // Check for empty panels
    modalContent.find('.panel-version').each((index: number, element: Element): void => {
      const $element: JQuery = $(element);
      if ($element.find('.searchhit, .filterhit').length < 1) {
        $element.find(' > .panel-collapse').collapse('hide');
      }
    });
  }

  /**
   * Moves all documents that are either read or not affected
   */
  private moveNotRelevantDocuments($container: JQuery): void {
    $container.find('[data-item-state="read"]').appendTo(this.findInModal('.panel-body-read'));
    $container.find('[data-item-state="notAffected"]').appendTo(this.findInModal('.panel-body-not-affected'));
  }

  private markRead(element: Element): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('upgrade-docs-mark-read-token');
    const $button = $(element).closest('button');
    $button.toggleClass('t3js-upgradeDocs-unmarkRead t3js-upgradeDocs-markRead');
    $button.find('typo3-backend-icon,.t3js-icon').replaceWith('<typo3-backend-icon identifier="actions-ban" size="small"></typo3-backend-icon>');
    $button.closest('.panel').appendTo(this.findInModal('.panel-body-read'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: $button.data('filepath'),
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
    const executeToken = this.getModuleContent().data('upgrade-docs-unmark-read-token');
    const $button = $(element).closest('button');
    const version = $button.closest('.panel').data('item-version');
    $button.toggleClass('t3js-upgradeDocs-markRead t3js-upgradeDocs-unmarkRead');
    $button.find('typo3-backend-icon,.t3js-icon').replaceWith('<typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>');
    $button.closest('.panel').appendTo(this.findInModal('*[data-group-version="' + version + '"] .panel-body'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: $button.data('filepath'),
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
