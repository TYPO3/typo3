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

import { customElement } from 'lit/decorators';
import { LitElement } from 'lit';

/**
 * Module: @typo3/backend/live-search/element/backend-search
 * Simple wrapper element around search container
 * @exports @typo3/backend/live-search/element/backend-search
 */
@customElement('typo3-backend-live-search')
export class BackendSearch extends LitElement {
  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }
}
