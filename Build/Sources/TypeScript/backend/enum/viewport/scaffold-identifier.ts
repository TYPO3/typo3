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

import { ContentNavigationSlotEnum, type ContentNavigation } from '@typo3/backend/viewport/content-navigation';

export enum ScaffoldIdentifierEnum {
  scaffold = '.t3js-scaffold',
  header = '.t3js-scaffold-header',
  sidebar = '.t3js-scaffold-sidebar',
  content = '.t3js-scaffold-content',
  contentModuleRouter = 'typo3-backend-module-router',
  contentModuleIframe = '.t3js-scaffold-content-module-iframe',
}

/**
 * Helper to get scaffold content area elements scoped to the backend content navigation
 */
export class ScaffoldContentArea {
  public static readonly selector = 'typo3-backend-content-navigation[identifier="backend"]';

  public static getContentNavigation(): ContentNavigation | null {
    return document.querySelector(this.selector) as ContentNavigation | null;
  }

  public static getNavigationContainer(): HTMLElement | null {
    const container = this.getContentNavigation();
    return container?.querySelector(`[slot="${ContentNavigationSlotEnum.navigation}"]`) ?? null;
  }

  public static getContentContainer(): HTMLElement | null {
    const container = this.getContentNavigation();
    return container?.querySelector(`[slot="${ContentNavigationSlotEnum.content}"]`) ?? null;
  }
}
