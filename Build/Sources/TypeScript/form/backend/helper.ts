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

import { loadModule, JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';
import DocumentService from '@typo3/core/document-service';

interface ModuleRequirements {
  app: JavaScriptItemPayload;
  viewModel: JavaScriptItemPayload;
  mediator?: JavaScriptItemPayload;
}

interface FormEditorLike {
  getInstance(options: any, mediator: MediatorLike, viewModel: typeof import('@typo3/form/backend/form-manager/view-model')): FormEditorLike;
  run(): FormEditorLike;
}

// eslint-disable-next-line @typescript-eslint/no-empty-interface
interface MediatorLike {
}

/**
 * @exports @typo3/form/backend/helper
 */
export class Helper {
  public static dispatchFormEditor(requirements: ModuleRequirements, options: any): void {
    DocumentService.ready().then((): void => {
      Promise.all([
        loadModule(requirements.app),
        loadModule(requirements.mediator),
        loadModule(requirements.viewModel),
      ]).then((modules: [any, any, any]) =>
        ((app: FormEditorLike, mediator: MediatorLike, viewModel: typeof import('@typo3/form/backend/form-manager/view-model')) => {
          window.TYPO3.FORMEDITOR_APP = app.getInstance(options, mediator, viewModel).run();
        })(...modules)
      );
    });
  }

  public static dispatchFormManager(requirements: ModuleRequirements, options: any): void {
    DocumentService.ready().then((): void => {
      Promise.all([
        loadModule(requirements.app),
        loadModule(requirements.viewModel)
      ]).then((modules: [any, any]) =>
        ((
          formManager: typeof import('@typo3/form/backend/form-manager'),
          viewModel: typeof import('@typo3/form/backend/form-manager/view-model')
        ) => {
          window.TYPO3.FORMMANAGER_APP = formManager.getInstance(options, viewModel).run();
        })(...modules)
      );
    });
  }
}
