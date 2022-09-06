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

import {loadCKEditor} from '@typo3/rte-ckeditor/ckeditor-loader';
import DocumentService from '@typo3/core/document-service';
import FormEngine from '@typo3/backend/form-engine';

interface CKEditorOptions {
  fieldId: string;
  configuration: any;
  configurationHash: string;
  externalPlugins: CKEditorExternalPlugin[];
}

interface CKEditorExternalPlugin {
  name: string;
  resource: string;
}

/**
 * @exports @typo3/rte-ckeditor/form-engine-initializer
 */
export class FormEngineInitializer {
  public static initializeCKEditor(options: CKEditorOptions): void {
    loadCKEditor().then((CKEDITOR) => {
      CKEDITOR.timestamp += '-' + options.configurationHash;
      options.externalPlugins
        .forEach((item: CKEditorExternalPlugin) => CKEDITOR.plugins.addExternal(item.name, item.resource, ''));
      DocumentService.ready().then((): void => {
        const fieldId = options.fieldId;
        const field = document.getElementById(fieldId) as HTMLTextAreaElement;
        CKEDITOR.replace(fieldId, options.configuration);

        const instance = CKEDITOR.instances[fieldId];
        instance.on('change', (evt: CKEDITOR.eventInfo) => {
          let commands = evt.sender.commands;
          instance.updateElement();
          FormEngine.Validation.validateField(field);
          FormEngine.Validation.markFieldAsChanged(field);

          // remember changes done in maximized state and mark field as changed, once minimized again
          if (typeof commands.maximize !== 'undefined' && commands.maximize.state === 1) {
            instance.once('maximize', (evt: CKEDITOR.eventInfo) => {
              FormEngine.Validation.markFieldAsChanged(field);
            });
          }
        });
        instance.on('mode', (evt: CKEDITOR.eventInfo) => {
          // detect field changes in source mode
          if (evt.editor.mode === 'source') {
            const sourceArea = instance.editable();
            sourceArea.attachListener(sourceArea, 'change', () => {
              FormEngine.Validation.markFieldAsChanged(field);
            });
          }
        });
        ['inline:sorting-changed', 'formengine:flexform:sorting-changed'].forEach((eventType: string): void => {
          document.addEventListener(eventType, () => {
            instance.destroy();
            CKEDITOR.replace(fieldId, options.configuration);
          });
        });
      });
    });
  }
}
