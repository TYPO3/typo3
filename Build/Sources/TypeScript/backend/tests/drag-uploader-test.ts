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

  // The uploader creates another request to detect upload progress support, it is never sent
  const uploadRequests = (): SinonFakeXMLHttpRequest[] => requests.filter(
    (request: SinonFakeXMLHttpRequest): boolean => request.url === '/file-process'
  );

  const upload = async (fileNames: string[]): Promise<void> => {
    // The uploader is created by hand, the class t3js-drag-uploader would make DragUploader.init() create a second one
    const element = document.createElement('div');
    element.dataset.dropzoneTarget = '#drag-uploader-test-target';
    element.dataset.progressContainer = '#drag-uploader-test-filelist';
    element.dataset.targetFolder = '1:/';
    element.dataset.reloadUrl = '/reload';
    container.append(element);
    const uploader = new DragUploader(element);
    uploader.processFiles(fileNames.map((fileName: string): File => new File(['x'], fileName)));
    await waitUntil((): boolean => uploadRequests().length === fileNames.length);
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

    fetchStub = stub(window, 'fetch').callsFake(
      async (): Promise<Response> => new Response('{}', { headers: { 'Content-Type': 'application/json' } })
    );
    requests = [];
    fakeXhr = useFakeXMLHttpRequest();
    fakeXhr.onCreate = (request: SinonFakeXMLHttpRequest): void => {
      requests.push(request);
    };
    infoStub = stub(Notification, 'info');
    errorStub = stub(Notification, 'error');
    showMessageStub = stub(Notification, 'showMessage');
  });

  afterEach((): void => {
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
});
