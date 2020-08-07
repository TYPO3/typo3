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
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import {ResponseError} from 'TYPO3/CMS/Core/Ajax/ResponseError';
import '../../Renderable/Clearable';
import {AbstractInteractableModule} from '../AbstractInteractableModule';
import Notification = require('TYPO3/CMS/Backend/Notification');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import Router = require('../../Router');
import DebounceEvent = require('TYPO3/CMS/Core/Event/DebounceEvent');

/**
 * Module: TYPO3/CMS/Install/Module/UpgradeDocs
 */
class UpgradeDocs extends AbstractInteractableModule {
  private selectorFulltextSearch: string = '.t3js-upgradeDocs-fulltext-search';
  private selectorChosenField: string = '.t3js-upgradeDocs-chosen-select';
  private selectorChangeLogsForVersionContainer: string = '.t3js-version-changes';
  private selectorChangeLogsForVersion: string = '.t3js-changelog-list';
  private selectorUpgradeDoc: string = '.t3js-upgrade-doc';

  private chosenField: JQuery;
  private fulltextSearchField: JQuery;

  private static trimExplodeAndUnique(delimiter: string, string: string): Array<string> {
    const result: Array<string> = [];
    const items = string.split(delimiter);
    for (let i = 0; i < items.length; i++) {
      const item = items[i].trim();
      if (item.length > 0) {
        if ($.inArray(item, result) === -1) {
          result.push(item);
        }
      }
    }
    return result;
  }

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    const isInIframe = (window.location !== window.parent.location);
    if (isInIframe) {
      top.require(['TYPO3/CMS/Install/chosen.jquery.min'], (): void => {
        this.getContent();
      });
    } else {
      require(['TYPO3/CMS/Install/chosen.jquery.min'], (): void => {
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
    jQuery.expr[':'].contains = jQuery.expr.createPseudo((arg: any): Function => {
      return (elem: any): boolean => {
        return jQuery(elem).text().toUpperCase().includes(arg.toUpperCase());
      };
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    modalContent.on('show.bs.collapse', this.selectorUpgradeDoc, (e: JQueryEventObject): void => {
      this.renderTags($(e.currentTarget));
    });
    (new AjaxRequest(Router.getUrl('upgradeDocsGetContent')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.empty().append(data.html);

            this.initializeFullTextSearch();
            this.initializeChosenSelector();
            this.loadChangelogs();
          }
        },
        (error: ResponseError): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private loadChangelogs(): void {
    const promises: Array<Promise<any>> = [];
    const modalContent = this.getModalBody();
    this.findInModal(this.selectorChangeLogsForVersionContainer).each((index: number, el: any): void => {
      const request = (new AjaxRequest(Router.getUrl('upgradeDocsGetChangelogForVersion')))
        .withQueryArguments({
          install: {
            version: el.dataset.version,
          },
        })
        .get({cache: 'no-cache'})
        .then(
          async (response: AjaxResponse): Promise<any> => {
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
          (error: ResponseError): void => {
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

    this.initializeChosenSelector();

    new DebounceEvent('keyup', (): void => {
      this.combinedFilterSearch();
    }).bindTo(searchInput);
  }

  private initializeChosenSelector(): void {
    this.chosenField = this.getModalBody().find(this.selectorChosenField);

    const config: any = {
      '.chosen-select': {width: '100%', placeholder_text_multiple: 'tags'},
      '.chosen-select-deselect': {allow_single_deselect: true},
      '.chosen-select-no-single': {disable_search_threshold: 10},
      '.chosen-select-no-results': {no_results_text: 'Oops, nothing found!'},
      '.chosen-select-width': {width: '100%'},
    };
    for (const selector in config) {
      if (config.hasOwnProperty(selector)) {
        this.findInModal(selector).chosen(config[selector]);
      }
    }
    this.chosenField.on('change', (): void => {
      this.combinedFilterSearch();
    });
  }

  /**
   * Appends tags to the chosen selector
   */
  private appendItemsToChosenSelector(): void {
    let tagString = '';
    $(this.findInModal(this.selectorUpgradeDoc)).each((index: number, element: any): void => {
      tagString += $(element).data('item-tags') + ',';
    });
    const tagArray = UpgradeDocs.trimExplodeAndUnique(',', tagString).sort((a: string, b: string): number => {
      // Sort case-insensitive by name
      return a.toLowerCase().localeCompare(b.toLowerCase());
    });
    this.chosenField.prop('disabled', false);
    $.each(tagArray, (i: number, tag: any): void => {
      this.chosenField.append($('<option>').text(tag));
    });
    this.chosenField.trigger('chosen:updated');
  }

  private combinedFilterSearch(): boolean {
    const modalContent = this.getModalBody();
    const $items = modalContent.find('div.item');
    if (this.chosenField.val().length < 1 && this.fulltextSearchField.val().length < 1) {
      this.currentModal.find('.panel-version .panel-collapse.in').collapse('hide');
      $items.removeClass('hidden searchhit filterhit');
      return false;
    }
    $items.addClass('hidden').removeClass('searchhit filterhit');

    // apply tags
    if (this.chosenField.val().length > 0) {
      $items
        .addClass('hidden')
        .removeClass('filterhit');
      const orTags: Array<string> = [];
      const andTags: Array<string> = [];
      $.each(this.chosenField.val(), (index: number, item: any): void => {
        const tagFilter = '[data-item-tags*="' + item + '"]';
        if (item.includes(':', 1)) {
          orTags.push(tagFilter);
        } else {
          andTags.push(tagFilter);
        }
      });
      const andString = andTags.join('');
      const tags = [];
      if (orTags.length) {
        for (let orTag of orTags) {
          tags.push(andString + orTag);
        }
      } else {
        tags.push(andString);
      }
      const tagSelection = tags.join(',');
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
    modalContent.find('div.item.filterhit').each((index: number, element: any): void => {
      const $item = $(element);
      if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
        $item.removeClass('hidden').addClass('searchhit');
      } else {
        $item.removeClass('searchhit').addClass('hidden');
      }
    });

    modalContent.find('.searchhit').closest('.panel-collapse').collapse('show');

    // Check for empty panels
    modalContent.find('.panel-version').each((index: number, element: any): void => {
      const $element: any = $(element);
      if ($element.find('.searchhit', '.filterhit').length < 1) {
        $element.find(' > .panel-collapse').collapse('hide');
      }
    });

    return true;
  }

  private renderTags($upgradeDocumentContainer: any): void {
    const $tagContainer = $upgradeDocumentContainer.find('.t3js-tags');
    if ($tagContainer.children().length === 0) {
      const tags = $upgradeDocumentContainer.data('item-tags').split(',');
      tags.forEach((value: string): void => {
        $tagContainer.append($('<span />', {'class': 'label'}).text(value));
      });
    }
  }

  /**
   * Moves all documents that are either read or not affected
   */
  private moveNotRelevantDocuments($container: JQuery): void {
    $container.find('[data-item-state="read"]').appendTo(this.findInModal('.panel-body-read'));
    $container.find('[data-item-state="notAffected"]').appendTo(this.findInModal('.panel-body-not-affected'));
  }

  private markRead(element: any): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('upgrade-docs-mark-read-token');
    const $button = $(element).closest('a');
    $button.toggleClass('t3js-upgradeDocs-unmarkRead t3js-upgradeDocs-markRead');
    $button.find('i').toggleClass('fa-check fa-ban');
    $button.closest('.panel').appendTo(this.findInModal('.panel-body-read'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: $button.data('filepath'),
          token: executeToken,
          action: 'upgradeDocsMarkRead',
        },
      })
      .catch((error: ResponseError): void => {
        Router.handleAjaxError(error, modalContent);
      });
  }

  private unmarkRead(element: any): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('upgrade-docs-unmark-read-token');
    const $button = $(element).closest('a');
    const version = $button.closest('.panel').data('item-version');
    $button.toggleClass('t3js-upgradeDocs-markRead t3js-upgradeDocs-unmarkRead');
    $button.find('i').toggleClass('fa-check fa-ban');
    $button.closest('.panel').appendTo(this.findInModal('*[data-group-version="' + version + '"] .panel-body'));
    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          ignoreFile: $button.data('filepath'),
          token: executeToken,
          action: 'upgradeDocsUnmarkRead',
        },
      })
      .catch((error: ResponseError): void => {
        Router.handleAjaxError(error, modalContent);
      });
  }
}

export = new UpgradeDocs();
