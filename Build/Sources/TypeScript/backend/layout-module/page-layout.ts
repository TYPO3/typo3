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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import Notification from '@typo3/backend/notification';
import RegularEvent from '@typo3/core/event/regular-event';
import { sudoModeInterceptor } from '@typo3/backend/security/sudo-mode-interceptor';
import layoutLabels from '~labels/backend.layout';
import { HiddenContentCountChangedEvent } from './page-layout-event';
import '@typo3/backend/element/icon-element';
import type { PageLayoutToggleHidden } from './toggle-hidden-element';

/**
 * Module: @typo3/backend/layout-module/page-layout
 *
 * Main entry point for the page layout module.
 * Registers all web components and provides inline visibility toggling.
 */
new RegularEvent('click', async (event: Event, delegateTarget?: Element): Promise<void> => {
  event.preventDefault();
  event.stopPropagation();

  const target = delegateTarget as HTMLButtonElement;
  if (target.disabled) {
    return;
  }
  target.disabled = true;

  const contentElement = target.closest('.t3js-page-ce') as HTMLElement | null;
  if (contentElement === null) {
    target.disabled = false;
    return;
  }

  const table = contentElement.dataset.table;
  const uid = parseInt(contentElement.dataset.uid ?? '0', 10);
  const isHidden = contentElement.classList.contains('t3js-hidden-record');
  const iconElement = target.querySelector('typo3-backend-icon');

  try {
    const response: AjaxResponse = await new AjaxRequest(TYPO3.settings.ajaxUrls.record_toggle_visibility)
      .addMiddleware(sudoModeInterceptor)
      .post({
        table: table,
        uid: uid,
        action: isHidden ? 'show' : 'hide',
      });

    const data = await response.resolve();

    // Update hidden state classes on the content element wrapper
    contentElement.classList.toggle('t3-page-ce-hidden', !data.isVisible);
    contentElement.classList.toggle('t3js-hidden-record', !data.isVisible);

    // If the element was just hidden and the "show hidden" toggle is inactive,
    // hide the element from view so it disappears from the layout.
    if (!data.isVisible) {
      const toggleHidden = document.querySelector('typo3-backend-page-layout-toggle-hidden') as PageLayoutToggleHidden | null;
      if (toggleHidden && !toggleHidden.active) {
        contentElement.style.display = 'none';
      }
    } else {
      contentElement.style.display = '';
    }

    // Update toggle button icon and title
    iconElement?.setAttribute('identifier', data.isVisible ? 'actions-edit-hide' : 'actions-edit-unhide');
    target.title = data.isVisible ? layoutLabels.get('hide') : layoutLabels.get('unHide');

    // Update record icon in context menu trigger (reflects hidden overlay)
    const recordIconElement = contentElement.querySelector('.t3-page-ce-header-left [data-contextmenu-trigger] .t3js-icon');
    if (recordIconElement && data.icon) {
      recordIconElement.replaceWith(document.createRange().createContextualFragment(data.icon));
    }

    // Update hidden element counter in the toolbar toggle button
    const count = document.querySelectorAll('.t3js-hidden-record').length;
    document.dispatchEvent(new HiddenContentCountChangedEvent(count));
  } catch (error) {
    if (error && typeof (error as AjaxResponse).resolve === 'function') {
      const data = await (error as AjaxResponse).resolve();
      for (const message of data.messages ?? []) {
        Notification.error(message.title, message.message);
      }
    }
  } finally {
    target.disabled = false;
  }
}).delegateTo(document, 'button[data-action="content-element-visibility-toggle"]');

export { HiddenContentCountChangedEvent } from './page-layout-event';
