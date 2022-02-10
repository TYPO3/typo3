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
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import '../../renderable/clearable';
import {AbstractInteractableModule} from '../abstract-interactable-module';
import ModuleMenu from '@typo3/backend/module-menu';
import Notification from '@typo3/backend/notification';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Router from '../../router';
import {topLevelModuleImport} from '@typo3/backend/utility/top-level-module-import';

/**
 * Module: @typo3/install/module/extension-configuration
 */
class ExtensionConfiguration extends AbstractInteractableModule {
  private selectorFormListener: string = '.t3js-extensionConfiguration-form';
  private selectorSearchInput: string = '.t3js-extensionConfiguration-search';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getContent();

    // Focus search field on certain user interactions
    currentModal.on('keydown', (e: JQueryEventObject): void => {
      const $searchInput = currentModal.find(this.selectorSearchInput);
      if (e.ctrlKey || e.metaKey) {
        // Focus search field on ctrl-f
        if (String.fromCharCode(e.which).toLowerCase() === 'f') {
          e.preventDefault();
          $searchInput.trigger('focus');
        }
      } else if (e.keyCode === 27) {
        // Clear search on ESC key
        e.preventDefault();
        $searchInput.val('').trigger('focus');
      }
    });

    // Perform expand collapse on search matches
    currentModal.on('keyup', this.selectorSearchInput, (e: JQueryEventObject): void => {
      const typedQuery = $(e.target).val();
      const $searchInput = currentModal.find(this.selectorSearchInput);
      currentModal.find('.search-item').each((index: number, element: any): void => {
        const $item = $(element);
        if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
          $item.removeClass('hidden').addClass('searchhit');
        } else {
          $item.removeClass('searchhit').addClass('hidden');
        }
      });
      currentModal.find('.searchhit').collapse('show');
      // Make search field clearable
      const searchInput = <HTMLInputElement>$searchInput.get(0);
      searchInput.clearable();
      searchInput.focus();
    });

    currentModal.on('submit', this.selectorFormListener, (e: JQueryEventObject): void => {
      e.preventDefault();
      this.write($(e.currentTarget));
    });
  }

  private getContent(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('extensionConfigurationGetContent')))
      .get({cache: 'no-cache'})
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true) {
            modalContent.html(data.html);
            this.initializeWrap();
            this.initializeColorPicker();
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  private initializeColorPicker(): void {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      topLevelModuleImport('@typo3/backend/color-picker.js').then(({default: ColorPicker}: typeof import('@typo3/backend/color-picker')): void => {
        ColorPicker.initialize();
      });
    } else {
      import('@typo3/backend/color-picker').then(({default: ColorPicker}): void => {
        ColorPicker.initialize();
      });
    }
  }

  /**
   * Submit the form and show the result message
   *
   * @param {JQuery} $form The form of the current extension
   */
  private write($form: JQuery): void {
    const modalContent = this.getModalBody();
    const executeToken = this.getModuleContent().data('extension-configuration-write-token');
    const extensionConfiguration: any = {};
    $.each($form.serializeArray(), (index: number, element: any): void => {
      extensionConfiguration[element.name] = element.value;
    });

    (new AjaxRequest(Router.getUrl()))
      .post({
        install: {
          token: executeToken,
          action: 'extensionConfigurationWrite',
          extensionKey: $form.attr('data-extensionKey'),
          extensionConfiguration: extensionConfiguration,
        },
      })
      .then(
        async (response: AjaxResponse): Promise<any> => {
          const data = await response.resolve();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach((element: any): void => {
              Notification.showMessage(element.title, element.message, element.severity);
            });
            if ($('body').data('context') === 'backend') {
              ModuleMenu.App.refreshMenu();
            }
          } else {
            Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
  }

  /**
   * configuration properties
   */
  private initializeWrap(): void {
    this.findInModal('.t3js-emconf-offset').each((index: number, element: any): void => {
      const $me = $(element);
      const $parent = $me.parent();
      const id = $me.attr('id');
      const val = $me.attr('value');
      const valArr = val.split(',');

      $me
        .attr('data-offsetfield-x', '#' + id + '_offset_x')
        .attr('data-offsetfield-y', '#' + id + '_offset_y')
        .wrap('<div class="hidden"></div>');

      const elementX = $('<div>', {'class': 'form-multigroup-item'}).append(
        $('<div>', {'class': 'input-group'}).append(
          $('<div>', {'class': 'input-group-addon'}).text('x'),
          $('<input>', {
            'id': id + '_offset_x',
            'class': 'form-control t3js-emconf-offsetfield',
            'data-target': '#' + id,
            'value': valArr[0]?.trim(),
          }),
        ),
      );
      const elementY = $('<div>', {'class': 'form-multigroup-item'}).append(
        $('<div>', {'class': 'input-group'}).append(
          $('<div>', {'class': 'input-group-addon'}).text('y'),
          $('<input>', {
            'id': id + '_offset_y',
            'class': 'form-control t3js-emconf-offsetfield',
            'data-target': '#' + id,
            'value': valArr[1]?.trim(),
          }),
        ),
      );

      const offsetGroup = $('<div>', {'class': 'form-multigroup-wrap'}).append(elementX, elementY);
      $parent.append(offsetGroup);
      $parent.find('.t3js-emconf-offsetfield').on('keyup', (evt: JQueryEventObject): void => {
        const $target = $parent.find($(evt.currentTarget).data('target'));
        $target.val($parent.find($target.data('offsetfield-x')).val() + ',' + $parent.find($target.data('offsetfield-y')).val());
      });
    });

    this.findInModal('.t3js-emconf-wrap').each((index: number, element: any): void => {
      const $me = $(element);
      const $parent = $me.parent();
      const id = $me.attr('id');
      const val = $me.attr('value');
      const valArr = val.split('|');

      $me.attr('data-wrapfield-start', '#' + id + '_wrap_start')
        .attr('data-wrapfield-end', '#' + id + '_wrap_end')
        .wrap('<div class="hidden"></div>');

      const wrapGroup = $('<div>', {'class': 'form-multigroup-wrap'}).append(
        $('<div>', {'class': 'form-multigroup-item'}).append(
          $('<input>', {
            'id': id + '_wrap_start',
            'class': 'form-control t3js-emconf-wrapfield',
            'data-target': '#' + id,
            'value': valArr[0]?.trim(),
          }),
        ),
        $('<div>', {'class': 'form-multigroup-item'}).append(
          $('<input>', {
            'id': id + '_wrap_end',
            'class': 'form-control t3js-emconf-wrapfield',
            'data-target': '#' + id,
            'value': valArr[1]?.trim(),
          }),
        ),
      );
      $parent.append(wrapGroup);
      $parent.find('.t3js-emconf-wrapfield').on('keyup', (evt: JQueryEventObject): void => {
        const $target = $parent.find($(evt.currentTarget).data('target'));
        $target.val($parent.find($target.data('wrapfield-start')).val() + '|' + $parent.find($target.data('wrapfield-end')).val());
      });
    });
  }
}

export default new ExtensionConfiguration();
