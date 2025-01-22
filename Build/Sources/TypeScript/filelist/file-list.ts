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

import { lll } from '@typo3/core/lit-helper';
import DocumentService from '@typo3/core/document-service';
import Notification from '@typo3/backend/notification';
import InfoWindow from '@typo3/backend/info-window';
import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import broadcastService from '@typo3/backend/broadcast-service';
import { FileListActionEvent, FileListActionDetail, FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';
import NProgress from 'nprogress';
import Icons from '@typo3/backend/icons';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import RegularEvent from '@typo3/core/event/regular-event';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import { ActionConfiguration, ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import { default as Modal, ModalElement } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import Severity from '@typo3/backend/severity';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import ContextMenu from '@typo3/backend/context-menu';
import { ResourceInterface } from '@typo3/backend/resource/resource';

type QueryParameters = Record<string, string>;

interface EditFileMetadataConfiguration extends ActionConfiguration {
  table: string;
  columnsOnly: Array<string>;
  returnUrl: string;
}
interface DownloadConfiguration extends ActionConfiguration {
  downloadUrl: string;
}

interface DeleteFileMetadataConfiguration {
  ok: string;
  title: string;
  content: string;
}

enum Selectors {
  fileListFormSelector = 'form[name="fileListForm"]',
  commandSelector = 'input[name="cmd"]',
  searchFieldSelector = 'input[name="searchTerm"]',
  pointerFieldSelector = 'input[name="pointer"]',
}

/**
 * @internal
 */
export const fileListOpenElementBrowser = 'typo3:filelist:openElementBrowser';

/**
 * Module: @typo3/filelist/filelist
 * @exports @typo3/filelist/filelist
 */
export default class Filelist {
  constructor() {
    Filelist.processTriggers();

    new RegularEvent(fileListOpenElementBrowser, (event: CustomEvent): void => {
      const url = new URL(event.detail.actionUrl, window.location.origin);

      url.searchParams.set('expandFolder', event.detail.identifier);
      url.searchParams.set('mode', event.detail.mode);

      const modal = Modal.advanced({
        type: Modal.types.iframe,
        content: url.toString(),
        size: Modal.sizes.large
      });
      modal.addEventListener('typo3-modal-hidden', (): void => {
        // @todo: this needs to be done when a folder was created. Apparently, backend user signals are not parsed in
        //        the modal's context. The best solution is probably to reload the "document space" via AJAX.
        top.list_frame.document.location.reload();
      });
    }).bindTo(document);

    // Filelist resource events
    new RegularEvent(FileListActionEvent.primary, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      const resourceElement: HTMLElement = detail.trigger.closest('[data-default-language-access]') as HTMLElement;
      if (resource.type === 'file' && resourceElement !== null) {
        window.location.href = top.TYPO3.settings.FormEngine.moduleUrl
          + '&edit[sys_file_metadata][' + resource.metaUid + ']=edit'
          + '&returnUrl=' + Filelist.getReturnUrl('');
      }
      if (resource.type === 'folder') {
        const parameters = Filelist.parseQueryParameters(document.location);
        parameters.id = resource.identifier;
        let parameterString = '';
        Object.keys(parameters).forEach(key => {
          if (parameters[key] === '') { return; }
          parameterString = parameterString + '&' + key + '=' + parameters[key];
        });
        window.location.href = window.location.pathname + '?' + parameterString.substring(1);
      }
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.primaryContextmenu, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      ContextMenu.show('sys_file', resource.identifier, '', '', '', detail.trigger, detail.event as PointerEvent);
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.show, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      Filelist.openInfoPopup('_' + resource.type.toUpperCase(), resource.identifier);
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.download, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      this.triggerDownload([resource], detail.url, detail.trigger);
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.updateOnlineMedia, (event: CustomEvent): void => {
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      this.updateOnlineMedia(resource, detail.url);
    }).bindTo(document);

    DocumentService.ready().then((): void => {
      new RegularEvent('click', (e: Event, trigger: HTMLAnchorElement): void => {
        e.preventDefault();

        document.dispatchEvent(new CustomEvent(fileListOpenElementBrowser, {
          detail: {
            actionUrl: trigger.href,
            identifier: trigger.dataset.identifier,
            mode: trigger.dataset.mode,
          }
        }));
      }).delegateTo(document, '.t3js-element-browser');
    });

    // Respond to multi record selection action events
    new RegularEvent('multiRecordSelection:action:edit', this.editFileMetadata).bindTo(document);
    new RegularEvent('multiRecordSelection:action:delete', this.deleteMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:download', this.downloadFilesAndFolders).bindTo(document);
    new RegularEvent('multiRecordSelection:action:copyMarked', (event: CustomEvent): void => {
      Filelist.submitClipboardFormWithCommand('copyMarked', event.target as HTMLButtonElement);
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:action:removeMarked', (event: CustomEvent): void => {
      Filelist.submitClipboardFormWithCommand('removeMarked', event.target as HTMLButtonElement);
    }).bindTo(document);

    // Respond to browser related clearable event
    const activeSearch: boolean = (document.querySelector([Selectors.fileListFormSelector, Selectors.searchFieldSelector].join(' ')) as HTMLInputElement)?.value !== '';
    new RegularEvent('search', (event: Event): void => {
      const searchField: HTMLInputElement = event.target as HTMLInputElement;
      if (searchField.value === '' && activeSearch) {
        (searchField.closest(Selectors.fileListFormSelector) as HTMLFormElement)?.submit();
      }
    }).delegateTo(document, Selectors.searchFieldSelector);
  }

  public static submitClipboardFormWithCommand(cmd: string, target: HTMLButtonElement): void {
    const fileListForm: HTMLFormElement = target.closest(Selectors.fileListFormSelector);
    if (!fileListForm) {
      return;
    }
    const commandField: HTMLInputElement = fileListForm.querySelector(Selectors.commandSelector);
    if (!commandField) {
      return;
    }
    commandField.value = cmd;
    // In case we just change elements on the clipboard, we try to fetch a possible pointer from the query
    // parameters, so after the form submit, we get to the same view as before. This is not done for delete
    // commands, since this may lead to empty sites, in case all elements from the current site are deleted.
    if (cmd === 'copyMarked' || cmd === 'removeMarked') {
      const pointerField: HTMLInputElement = fileListForm.querySelector(Selectors.pointerFieldSelector);
      const pointerValue: string = Filelist.parseQueryParameters(document.location).pointer;
      if (pointerField && pointerValue) {
        pointerField.value = pointerValue;
      }
    }
    fileListForm.submit();
  }

  protected static openInfoPopup(type: string, identifier: string): void {
    InfoWindow.showItem(type, identifier);
  }

  private static processTriggers(): void {
    const mainElement: HTMLElement = document.querySelector('.filelist-main');
    if (mainElement === null) {
      return;
    }
    // update ModuleStateStorage to the current folder identifier
    const id = encodeURIComponent(mainElement.dataset.filelistCurrentIdentifier);
    ModuleStateStorage.update('media', id, true, undefined);
    // emit event for currently shown folder so the folder tree gets updated
    Filelist.emitTreeUpdateRequest(
      mainElement.dataset.filelistCurrentIdentifier
    );
  }

  private static emitTreeUpdateRequest(identifier: string): void {
    const message = new BroadcastMessage(
      'filelist',
      'treeUpdateRequested',
      { type: 'folder', identifier: identifier }
    );
    broadcastService.post(message);
  }

  private static parseQueryParameters(location: Location): QueryParameters {
    const queryParameters: QueryParameters = {};
    if (location && Object.prototype.hasOwnProperty.call(location, 'search')) {
      const parameters = location.search.substr(1).split('&');
      for (let i = 0; i < parameters.length; i++) {
        const parameter = parameters[i].split('=');
        queryParameters[decodeURIComponent(parameter[0])] = decodeURIComponent(parameter[1]);
      }
    }
    return queryParameters;
  }

  private static getReturnUrl(returnUrl: string): string {
    if (returnUrl === '') {
      returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
    }
    return encodeURIComponent(returnUrl);
  }

  private deleteMultiple(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail as ActionEventDetails;
    const configuration: DeleteFileMetadataConfiguration = eventDetails.configuration;
    Modal.advanced({
      title: configuration.title || 'Delete',
      content: configuration.content || 'Are you sure you want to delete those files and folders?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: TYPO3.lang['button.close'] || 'Close',
          active: true,
          btnClass: 'btn-default',
          trigger: (modalEvent: Event, modal: ModalElement) => modal.hideModal(),
        },
        {
          text: configuration.ok || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          trigger: (modalEvent: Event, modal: ModalElement) => {
            Filelist.submitClipboardFormWithCommand('delete', e.target as HTMLButtonElement);
            modal.hideModal();
          }
        }
      ]
    });
  }

  private editFileMetadata(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail;
    const configuration: EditFileMetadataConfiguration = eventDetails.configuration;
    if (!configuration || !configuration.idField || !configuration.table) {
      return;
    }
    const list: Array<string> = [];
    (eventDetails.checkboxes as NodeListOf<HTMLInputElement>).forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
        list.push(checkboxContainer.dataset[configuration.idField]);
      }
    });

    if (list.length) {
      let uri = top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[' + configuration.table + '][' + list.join(',') + ']=edit'
        + '&returnUrl=' + Filelist.getReturnUrl(configuration.returnUrl || '');
      const columnsOnly = configuration.columnsOnly || [];
      if (columnsOnly.length > 0) {
        uri += columnsOnly.map((column: string, i: number): string => '&columnsOnly[' + configuration.table + '][' + i + ']=' + column).join('');
      }
      window.location.href = uri;
    } else {
      Notification.warning('The selected elements can not be edited.');
    }
  }

  private readonly downloadFilesAndFolders = (event: CustomEvent): void => {
    event.preventDefault();

    const target: HTMLElement = event.target as HTMLElement;
    const eventDetails: ActionEventDetails = (event.detail as ActionEventDetails);
    const configuration: DownloadConfiguration = (eventDetails.configuration as DownloadConfiguration);

    const filesAndFolders: ResourceInterface[] = [];
    eventDetails.checkboxes.forEach((checkbox: HTMLInputElement) => {
      if (checkbox.checked) {
        const element = checkbox.closest(FileListActionSelector.elementSelector) as HTMLInputElement;
        const resource = FileListActionUtility.getResourceForElement(element);
        filesAndFolders.unshift(resource);
      }
    });

    if (filesAndFolders.length) {
      this.triggerDownload(filesAndFolders, configuration.downloadUrl, target);
    } else {
      Notification.warning(lll('file_download.invalidSelection'));
    }
  };

  private triggerDownload(items: ResourceInterface[], downloadUrl: string, button: HTMLElement | null): void {
    if (items.length === 1) {
      const item = items.at(0);
      if (item.type === 'file') {
        // We deal with a single file in the selection, download directly
        this.invokeDownload(item.url, item.name);
        return;
      }
    }

    // Add notification about the download being prepared
    Notification.info(lll('file_download.prepare'), '', 2);
    // Store the targets' (button) content and replace with a spinner
    // icon, while the download is being prepared. Also disable the
    // button for this time to prevent the user from triggering it again.
    const targetContent: string | null = button?.innerHTML;
    if (button) {
      button.setAttribute('disabled', 'disabled');
      Icons.getIcon('spinner-circle', Icons.sizes.small).then((spinner: string): void => {
        button.innerHTML = spinner;
      });
    }

    // Configure and start the progress bar, while preparing
    NProgress
      .configure({ parent: '#typo3-filelist', showSpinner: false })
      .start();

    const itemIdentifiers = items.map((resource: ResourceInterface) => resource.identifier);
    (new AjaxRequest(downloadUrl)).post({ items: itemIdentifiers })
      .then(async (response: AjaxResponse): Promise<void> => {
        let fileName = response.response.headers.get('Content-Disposition');
        if (!fileName) {
          const data = await response.resolve();
          if (data.success === false && data.status) {
            Notification.warning(lll('file_download.' + data.status), lll('file_download.' + data.status + '.message'), 10);
          } else {
            Notification.error(lll('file_download.error'));
          }
          return;
        }
        fileName = fileName.substring(fileName.indexOf(' filename=') + 10);
        const data = await response.raw().arrayBuffer();
        const blob = new Blob([data], { type: response.raw().headers.get('Content-Type') });
        const downloadUrl = URL.createObjectURL(blob);
        this.invokeDownload(downloadUrl, fileName);
        // Add notification about successful preparation
        Notification.success(lll('file_download.success'), '', 2);
      })
      .catch(() => {
        Notification.error(lll('file_download.error'));
      })
      .finally(() => {
        // Remove progress bar and restore target (button)
        NProgress.done();
        if (button) {
          button.removeAttribute('disabled');
          button.innerHTML = targetContent;
        }
      });
  }

  private updateOnlineMedia(resource: ResourceInterface, url: string): void {
    if (!url || !resource.uid || resource.type !== 'file') {
      return;
    }

    NProgress.configure({ parent: '#typo3-filelist', showSpinner: false }).start();
    (new AjaxRequest(url)).post({ resource: resource })
      .then(() => {
        Notification.success(lll('online_media.update.success'));
      })
      .catch(() => {
        Notification.error(lll('online_media.update.error'));
      })
      .finally(() => {
        NProgress.done();
        window.location.reload();
      });
  }

  private invokeDownload(downloadUrl: string, fileName: string): void {
    const anchorTag = document.createElement('a');
    anchorTag.href = downloadUrl;
    anchorTag.download = fileName;
    document.body.appendChild(anchorTag);
    anchorTag.click();
    URL.revokeObjectURL(downloadUrl);
    document.body.removeChild(anchorTag);
  }
}
