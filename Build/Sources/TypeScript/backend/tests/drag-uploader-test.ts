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

import DragUploader from '@typo3/backend/drag-uploader.js';
import Modal, { type ModalElement } from '@typo3/backend/modal.js';
import Notification from '@typo3/backend/notification.js';
import { expect, waitUntil } from '@open-wc/testing';
import {
  stub,
  useFakeXMLHttpRequest,
  type SinonFakeXMLHttpRequest,
  type SinonFakeXMLHttpRequestStatic,
  type SinonStub,
} from 'sinon';
import type { } from 'mocha';

describe('@typo3/backend/drag-uploader', (): void => {
  let container: HTMLElement;
  let originalTypo3: typeof window.TYPO3;
  let fakeXhr: SinonFakeXMLHttpRequestStatic;
  let requests: SinonFakeXMLHttpRequest[];
  let fetchStub: SinonStub;
  let infoStub: SinonStub;
  let errorStub: SinonStub;
  let showMessageStub: SinonStub;
  let modalStub: SinonStub;
  let dismissStub: SinonStub;
  let modal: ModalElement | null;
  let existingFileNames: string[];
  let uploadFinishedCount: number;

  const countUploadFinished = (): void => {
    uploadFinishedCount++;
  };

  const provideModuleImport = (event: Event): void => {
    (event as CustomEvent<{ importPromise: Promise<unknown> | null }>).detail.importPromise = Promise.resolve();
  };

  // The uploader creates another request to detect upload progress support, it is never sent
  const uploadRequests = (): SinonFakeXMLHttpRequest[] => requests.filter(
    (request: SinonFakeXMLHttpRequest): boolean => request.url === '/file-process'
  );

  const upload = async (fileNames: string[], existing: string[] = []): Promise<void> => {
    // The uploader is created by hand, the class t3js-drag-uploader would make DragUploader.init() create a second one
    const element = document.createElement('div');
    element.dataset.dropzoneTarget = '#drag-uploader-test-target';
    element.dataset.progressContainer = '#drag-uploader-test-filelist';
    element.dataset.targetFolder = '1:/';
    element.dataset.reloadUrl = '/reload';
    container.append(element);
    existingFileNames = existing;
    const uploader = new DragUploader(element);
    uploader.processFiles([...fileNames, ...existing].map((fileName: string): File => new File(['x'], fileName)));
    await waitUntil((): boolean => uploadRequests().length === fileNames.length && (existing.length === 0 || modal !== null));
  };

  const chooseAction = (fileName: string, action: string): void => {
    const select = modal.querySelector<HTMLSelectElement>(`.t3js-actions[data-override="${existingFileNames.indexOf(fileName)}"]`);
    select.value = action;
    select.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const clickModalButton = async (name: string): Promise<void> => {
    const button = document.createElement('button');
    button.name = name;
    modal.append(button);
    button.dispatchEvent(new CustomEvent('button.clicked', { bubbles: true }));
    await new Promise((resolve): number => window.setTimeout(resolve, 0));
  };

  const respond = async (index: number, status: number, body: object): Promise<void> => {
    uploadRequests()[index].respond(status, { 'Content-Type': 'application/json' }, JSON.stringify(body));
    // The reload notice is shown in a timeout
    await new Promise((resolve): number => window.setTimeout(resolve, 0));
  };

  const successResponse = (fileName: string): object => ({
    upload: [{
      name: fileName,
      id: '1:/' + fileName,
      uid: 1,
      icon: '',
      extension: 'txt',
      permissions: { read: true, write: true },
      size: 1,
      date: '',
      mtime: 0,
      thumbUrl: '',
      type: 'file',
      path: '/',
    }],
  });

  beforeEach((): void => {
    originalTypo3 = window.TYPO3;
    window.TYPO3 = {
      settings: { ajaxUrls: { file_exists: '/file-exists', file_process: '/file-process' } },
    } as unknown as typeof window.TYPO3;

    container = document.createElement('div');
    container.innerHTML = '<div id="drag-uploader-test-target"></div>' +
      '<div><table id="drag-uploader-test-filelist"><thead><tr><th></th><th></th></tr></thead><tbody></tbody></table></div>';
    document.body.append(container);

    fetchStub = stub(window, 'fetch').callsFake(async (input: RequestInfo | URL): Promise<Response> => {
      const fileName = new URL(input instanceof Request ? input.url : String(input), window.location.href).searchParams.get('fileName');
      const existingFile = existingFileNames.includes(fileName)
        ? { uid: 1, name: fileName, thumbUrl: '', icon: '', size: 1, mtime: 0 }
        : {};
      return new Response(JSON.stringify(existingFile), { headers: { 'Content-Type': 'application/json' } });
    });
    requests = [];
    fakeXhr = useFakeXMLHttpRequest();
    fakeXhr.onCreate = (request: SinonFakeXMLHttpRequest): void => {
      requests.push(request);
    };
    infoStub = stub(Notification, 'info');
    errorStub = stub(Notification, 'error');
    showMessageStub = stub(Notification, 'showMessage');

    modal = null;
    existingFileNames = [];
    modalStub = stub(Modal, 'advanced').callsFake((configuration): ModalElement => {
      modal = document.createElement('div') as unknown as ModalElement;
      modal.append(configuration.content as HTMLElement);
      modal.hideModal = (): void => {
        modal.dispatchEvent(new CustomEvent('typo3-modal-hidden'));
      };
      container.append(modal);
      return modal;
    });
    dismissStub = stub(Modal, 'dismiss').callsFake((): void => modal.hideModal());
    // Answers the module import of the top level window, the uploader loads the datetime element for the modal
    document.addEventListener('typo3:import-javascript-module', provideModuleImport);

    uploadFinishedCount = 0;
    document.addEventListener('typo3:drag-uploader:upload-finished', countUploadFinished);
  });

  afterEach((): void => {
    document.removeEventListener('typo3:drag-uploader:upload-finished', countUploadFinished);
    document.removeEventListener('typo3:import-javascript-module', provideModuleImport);
    dismissStub.restore();
    modalStub.restore();
    infoStub.restore();
    errorStub.restore();
    showMessageStub.restore();
    fakeXhr.restore();
    fetchStub.restore();
    container.remove();
    document.querySelectorAll('.upload-file-picker, typo3-backend-progress-bar').forEach((element: Element): void => element.remove());
    window.TYPO3 = originalTypo3;
  });

  it('offers to reload the file list after a successful upload', async (): Promise<void> => {
    await upload(['file.txt']);
    await respond(0, 200, successResponse('file.txt'));

    expect(infoStub.callCount).to.equal(1);
    expect(infoStub.firstCall.args[0]).to.equal('file_upload.reload.filelist');
  });

  it('does not offer to reload the file list when the upload failed', async (): Promise<void> => {
    await upload(['file.txt']);
    await respond(0, 500, {});

    expect(infoStub.callCount).to.equal(0);
  });

  it('does not offer to reload the file list when every upload failed', async (): Promise<void> => {
    await upload(['first.txt', 'second.txt']);
    await respond(0, 500, {});
    await respond(1, 200, { hasErrors: true });

    expect(infoStub.callCount).to.equal(0);
  });

  it('offers to reload the file list when one of several uploads succeeded', async (): Promise<void> => {
    await upload(['first.txt', 'second.txt']);
    await respond(0, 500, {});
    await respond(1, 200, successResponse('second.txt'));

    expect(infoStub.callCount).to.equal(1);
    expect(infoStub.firstCall.args[0]).to.equal('file_upload.reload.filelist');
  });

  it('offers to reload the file list when the other file of a batch was skipped', async (): Promise<void> => {
    await upload(['new.txt'], ['existing.txt']);
    await respond(0, 200, successResponse('new.txt'));
    await clickModalButton('continue');

    expect(infoStub.callCount).to.equal(1);
    expect(uploadFinishedCount).to.equal(1);
  });

  it('finishes the upload after the last file chosen to upload in the modal', async (): Promise<void> => {
    await upload([], ['skipped.txt', 'renamed.txt']);
    chooseAction('renamed.txt', 'rename');
    await clickModalButton('continue');

    expect(uploadFinishedCount).to.equal(0);

    await respond(0, 200, successResponse('renamed_01.txt'));

    expect(infoStub.callCount).to.equal(1);
    expect(uploadFinishedCount).to.equal(1);
  });

  it('finishes the upload when every file is skipped', async (): Promise<void> => {
    await upload([], ['existing.txt']);
    await clickModalButton('continue');

    expect(infoStub.callCount).to.equal(0);
    expect(uploadFinishedCount).to.equal(1);
  });

  it('finishes the upload when the modal is cancelled', async (): Promise<void> => {
    await upload(['new.txt'], ['existing.txt']);
    await clickModalButton('cancel');

    expect(uploadFinishedCount).to.equal(0);

    await respond(0, 200, successResponse('new.txt'));

    expect(infoStub.callCount).to.equal(1);
    expect(uploadFinishedCount).to.equal(1);
  });

  it('finishes the upload when the modal is closed without a choice', async (): Promise<void> => {
    await upload([], ['existing.txt']);
    modal.hideModal();
    await new Promise((resolve): number => window.setTimeout(resolve, 0));

    expect(infoStub.callCount).to.equal(0);
    expect(uploadFinishedCount).to.equal(1);
  });
});
