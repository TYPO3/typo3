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

interface ModuleRequirements {
  app: string;
  viewModel: string;
  mediator?: string;
}

interface FormEditorLike {
  getInstance(options: any, mediator: MediatorLike, viewModel: ViewModelLike): FormEditorLike;
  run(): FormEditorLike;
}

interface FormManagerLike {
  getInstance(options: any, viewModel: ViewModelLike): FormManagerLike;
  run(): FormEditorLike;
}

interface MediatorLike {
}

interface ViewModelLike {
}

/**
 * @exports TYPO3/CMS/Form/Backend/Helper
 */
export class Helper {
  public static dispatchFormEditor(requirements: ModuleRequirements, options: any): void {
    require(
      [requirements.app, requirements.mediator, requirements.viewModel],
      (app: FormEditorLike, mediator: MediatorLike, viewModel: ViewModelLike) => {
        window.TYPO3.FORMEDITOR_APP = app.getInstance(options, mediator, viewModel).run();
      }
    );
  }

  public static dispatchFormManager(requirements: ModuleRequirements, options: any): void {
    require(
      [requirements.app, requirements.viewModel],
      (app: FormManagerLike, viewModel: ViewModelLike) => {
        window.TYPO3.FORMMANAGER_APP = app.getInstance(options, viewModel).run();
      }
    );
  }
}
