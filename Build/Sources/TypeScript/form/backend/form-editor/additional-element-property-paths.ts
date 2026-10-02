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

/**
 * Module: @typo3/form/backend/form-editor/additional-element-property-paths
 */
import type { EditorConfiguration, FormElement } from '@typo3/form/backend/form-editor/core';

export function writeAdditionalElementPropertyPaths(
  formElement: Pick<FormElement, 'set' | 'unset'>,
  editorConfiguration: Pick<EditorConfiguration, 'additionalElementPropertyPaths' | 'doNotSetIfPropertyValueIsEmpty'>,
  value: unknown,
  isEmpty: boolean,
): void {
  if (!Array.isArray(editorConfiguration.additionalElementPropertyPaths)) {
    return;
  }
  for (const path of editorConfiguration.additionalElementPropertyPaths) {
    if (editorConfiguration.doNotSetIfPropertyValueIsEmpty && isEmpty) {
      formElement.unset(path);
    } else {
      formElement.set(path, value);
    }
  }
}
