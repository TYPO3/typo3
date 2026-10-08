import { CKEditor5Element } from '@typo3/rte-ckeditor/ckeditor5.js';
import { Essentials } from '@ckeditor/ckeditor5-essentials';
import { Paragraph } from '@ckeditor/ckeditor5-paragraph';
import { expect, waitUntil } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import type { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic';
import type { } from 'mocha';

describe('@typo3/rte-ckeditor/ckeditor5-test', () => {
  let container: HTMLElement;
  let resolvePluginsStub: SinonStub;

  beforeEach(() => {
    // Plugins are imported statically, dynamic imports bypass the import map handling of the test server
    resolvePluginsStub = stub(CKEditor5Element.prototype as unknown as { resolvePlugins: () => Promise<unknown[]> }, 'resolvePlugins')
      .resolves([Essentials, Paragraph]);
    container = document.createElement('div');
    document.body.append(container);
  });

  afterEach(async () => {
    const editor = findEditor();
    if (editor) {
      await editor.destroy();
    }
    container.remove();
    resolvePluginsStub.restore();
    delete (document as unknown as Record<string, unknown>).readyState;
  });

  const findEditor = (): ClassicEditor | null => {
    const editable = container.querySelector('.ck-editor__editable') as HTMLElement & { ckeditorInstance?: ClassicEditor } | null;
    return editable?.ckeditorInstance ?? null;
  };

  it('reads the textarea value only after the document has been parsed', async () => {
    Object.defineProperty(document, 'readyState', { configurable: true, get: () => 'loading' });

    const element = document.createElement('typo3-rte-ckeditor-ckeditor5');
    const textarea = document.createElement('textarea');
    textarea.slot = 'textarea';
    textarea.append('<p>first part');
    element.append(textarea);
    container.append(element);

    // Simulate a stalled response: the rest of the textarea content arrives later
    await waitUntil(() => findEditor() !== null, '', { timeout: 2000 }).catch((): void => undefined);
    textarea.append(' and second part</p>');

    delete (document as unknown as Record<string, unknown>).readyState;
    document.dispatchEvent(new Event('DOMContentLoaded'));

    await waitUntil(() => findEditor() !== null, 'CKEditor has not been initialized', { timeout: 10000 });
    expect(findEditor().getData()).to.equal('<p>first part and second part</p>');
  });

  it('initializes immediately when the document has already been parsed', async () => {
    const element = document.createElement('typo3-rte-ckeditor-ckeditor5');
    const textarea = document.createElement('textarea');
    textarea.slot = 'textarea';
    textarea.append('<p>complete</p>');
    element.append(textarea);
    container.append(element);

    await waitUntil(() => findEditor() !== null, 'CKEditor has not been initialized', { timeout: 10000 });
    expect(findEditor().getData()).to.equal('<p>complete</p>');
  });
});
