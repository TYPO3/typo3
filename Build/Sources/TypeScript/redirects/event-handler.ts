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

import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import NotificationService from '@typo3/backend/notification';
import DeferredAction from '@typo3/backend/action-button/deferred-action';
import type { AbstractAction } from '@typo3/backend/action-button/abstract-action';
import { topDocument } from '@typo3/core/utility/top-window';
import labels from '~labels/redirects.slug_service';

type Correlation = {
  correlationIdPageUpdate: string;
  correlationIdSlugUpdate: string;
  correlationIdRedirectCreation: string;
};

type ChangedPage = {
  uid: number;
  title: string;
  oldSlug: string;
  newSlug: string;
};

type SlugChangeDetail = {
  correlations: Correlation;
  autoUpdateSlugs: boolean;
  autoCreateRedirects: boolean;
  // Not given by notifications stored in the session before an update
  page?: ChangedPage;
  updatedSubPages?: number;
  createdRedirects?: number;
};

/**
 * Module: @typo3/redirects/event-handler
 * @exports @typo3/redirects/event-handler
 */
class EventHandler {
  private pendingSlugChanges: SlugChangeDetail[] = [];

  public constructor() {
    document.addEventListener(
      'typo3:redirects:slugChanged',
      (evt: CustomEvent) => this.onSlugChanged(evt.detail),
    );
  }

  public dispatchCustomEvent(name: string, detail: any = null): void {
    const event = new CustomEvent(name, { detail: detail });
    document.dispatchEvent(event);
  }

  public onSlugChanged(detail: SlugChangeDetail): void {
    // Slug changes of several pages are dispatched in a row, they are combined into one notification,
    // since a revert reloads the backend and would discard the notifications of the other pages
    this.pendingSlugChanges.push(detail);
    if (this.pendingSlugChanges.length === 1) {
      queueMicrotask((): void => this.showSlugChangedNotification());
    }
  }

  private showSlugChangedNotification(): void {
    // Reverted in reverse order, since a later change might be based on an earlier one, e.g. on a parent page
    const details = this.pendingSlugChanges.reverse();
    this.pendingSlugChanges = [];
    const actions: { label: string; action: AbstractAction }[] = [];
    const autoUpdateSlugs = details.filter((detail: SlugChangeDetail): boolean => detail.autoUpdateSlugs);
    const autoCreateRedirects = details.filter((detail: SlugChangeDetail): boolean => detail.autoCreateRedirects);

    if (autoUpdateSlugs.length > 0) {
      actions.push({
        label: labels.get('notification.redirects.button.revert_update'),
        action: new DeferredAction(async () => {
          await this.revert(autoUpdateSlugs.flatMap((detail: SlugChangeDetail): string[] => [
            detail.correlations.correlationIdPageUpdate,
            detail.correlations.correlationIdSlugUpdate,
            detail.correlations.correlationIdRedirectCreation,
          ]));
        }),
      });
    }
    if (autoCreateRedirects.length > 0) {
      actions.push({
        label: labels.get('notification.redirects.button.revert_redirect'),
        action: new DeferredAction(async () => {
          await this.revert(autoCreateRedirects.map((detail: SlugChangeDetail): string => detail.correlations.correlationIdRedirectCreation));
        }),
      });
    }

    const createdRedirects = details.reduce((sum: number, detail: SlugChangeDetail): number => sum + (detail.createdRedirects ?? 0), 0);
    NotificationService.info(
      labels.get(createdRedirects > 0 ? 'notification.slug_and_redirects.title' : 'notification.slug_only.title'),
      this.getSlugChangedMessage([...details].reverse()),
      0,
      actions,
    );
  }

  private getSlugChangedMessage(details: SlugChangeDetail[]): string {
    const pages = details
      .filter((detail: SlugChangeDetail): boolean => detail.page !== undefined)
      .map((detail: SlugChangeDetail): string => labels.get('notification.page', {
        title: detail.page.title !== '' ? detail.page.title : '[' + detail.page.uid + ']',
        oldSlug: detail.page.oldSlug,
        newSlug: detail.page.newSlug,
      }));
    const updatedSubPages = details.reduce((sum: number, detail: SlugChangeDetail): number => sum + (detail.updatedSubPages ?? 0), 0);
    const createdRedirects = details.reduce((sum: number, detail: SlugChangeDetail): number => sum + (detail.createdRedirects ?? 0), 0);

    const sentences: string[] = [];
    if (pages.length > 3) {
      sentences.push(labels.get('notification.message.changed_pages_and_more', { pages: pages.slice(0, 3).join(', '), count: pages.length - 3 }));
    } else if (pages.length > 0) {
      sentences.push(labels.get('notification.message.changed_pages', { pages: pages.join(', '), count: pages.length }));
    }
    if (updatedSubPages > 0) {
      sentences.push(labels.get('notification.message.updated_sub_pages', { count: updatedSubPages }));
    }
    if (createdRedirects > 0) {
      sentences.push(labels.get('notification.message.created_redirects', { count: createdRedirects }));
    }
    return sentences.join(' ');
  }

  private revert(correlationIds: string[]): Promise<AjaxResponse> {
    const request = new AjaxRequest(TYPO3.settings.ajaxUrls.redirects_revert_correlation).post({
      correlation_ids: correlationIds,
    });

    request.then(async (response: AjaxResponse): Promise<void> => {
      const json = await response.resolve();
      if (json.status === 'ok') {
        NotificationService.success(json.title, json.message);
        window.location.reload();
        topDocument.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
      }
      if (json.status === 'error') {
        NotificationService.error(json.title, json.message);
      }
    }).catch((): void => {
      NotificationService.error(
        labels.get('redirects_error_title'),
        labels.get('redirects_error_message'),
      );
    });
    return request;
  }
}

export default new EventHandler();

declare global {
  interface DocumentEventMap {
    'typo3:redirects:slugChanged': CustomEvent<SlugChangeDetail>;
  }
}
