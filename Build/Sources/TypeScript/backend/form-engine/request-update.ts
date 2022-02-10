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

import {LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import FormEngine from '@typo3/backend/form-engine';

enum UpdateMode {
  ask = 'ask',
  enforce = 'enforce'
}

const selectorConverter = {
  fromAttribute(selector: string) {
    return document.querySelectorAll(selector);
  }
};

@customElement('typo3-formengine-updater')
class RequestUpdate extends LitElement {
  @property({type: String, attribute: 'mode'}) mode: String = UpdateMode.ask;

  @property({attribute: 'field', converter: selectorConverter}) fields: NodeList;

  public connectedCallback(): void {
    super.connectedCallback();
    for (let field of this.fields) {
      field.addEventListener('change', this.requestFormEngineUpdate);
    }
  }

  public disconnectedCallback(): void {
    super.disconnectedCallback();
    for (let field of this.fields) {
      field.removeEventListener('change', this.requestFormEngineUpdate);
    }
  }

  private requestFormEngineUpdate = (): void => {
    const askForUpdate = this.mode === UpdateMode.ask;
    FormEngine.requestFormEngineUpdate(askForUpdate);
  }
}
