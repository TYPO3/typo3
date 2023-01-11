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

import {Collapse as BootstrapCollapse} from 'bootstrap';
import Client from '@typo3/backend/storage/client';
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Utility class to store a collapsible's state in the localStorage and to re-apply the states on load.
 * Uses the collapsible's id to store the keys.
 *
 * @internal
 */
export class CollapseStatePersister {
  private readonly localStorageKey: string = 'collapse-states';

  public constructor() {
    DocumentService.ready().then((): void => {
      this.registerEventListener();
      this.recoverStates();
    });
  }

  private registerEventListener(): void {
    const delegateEventTo = '.collapse[data-persist-collapse-state="true"]';

    new RegularEvent('show.bs.collapse', (e: Event): void => {
      this.toStorage((e.target as HTMLElement).id, true);
    }).delegateTo(document, delegateEventTo);

    new RegularEvent('hide.bs.collapse', (e: Event): void => {
      this.toStorage((e.target as HTMLElement).id, false);
    }).delegateTo(document, delegateEventTo);
  }

  private recoverStates(): void {
    const currentStates = this.fromStorage();

    for (const [identifier, isExpanded] of Object.entries(currentStates)) {
      const element = document.getElementById(identifier);
      if (element === null) {
        continue;
      }
      const collapsible = BootstrapCollapse.getOrCreateInstance(element, {
        toggle: false
      });

      if (isExpanded) {
        collapsible.show();
      } else {
        collapsible.hide();
      }
    }
  }

  private fromStorage(): { [key: string]: boolean } {
    const currentStates = Client.get(this.localStorageKey);
    if (currentStates === null) {
      return {};
    }

    return JSON.parse(currentStates);
  }

  private toStorage(key: string, expanded: boolean) {
    const currentStates = this.fromStorage();
    currentStates[key] = expanded;

    Client.set(this.localStorageKey, JSON.stringify(currentStates));
  }
}

export default new CollapseStatePersister();
