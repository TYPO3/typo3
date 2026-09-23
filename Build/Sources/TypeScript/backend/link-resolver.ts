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

export interface ResolvedLink {
  type: string | null;
  title: string | null;
  path: string | null;
  url: string | null;
  icon: string;
}

const cache = new Map<string, Promise<ResolvedLink>>();
const settled = new Map<string, ResolvedLink>();

/**
 * Only TYPO3 specific hrefs (t3://…) and legacy notations (page uid, file path) need
 * a roundtrip to the backend. Everything with a well known scheme is self-describing.
 */
export function needsResolving(href: string): boolean {
  return !/^(https?|mailto|tel|ftp|sms):|^[/#?]/i.test(href.trim());
}

function describeLocally(href: string): ResolvedLink {
  const title = href.replace(/^(mailto|tel):/i, '');
  const icon = /^mailto:/i.test(href) ? 'actions-envelope' : /^tel:/i.test(href) ? 'actions-phone' : 'actions-link';
  return { type: null, title, path: null, url: href, icon };
}

/**
 * Resolves an href of the RTE to a title, path and frontend URL. Results are cached
 * per href, failed lookups are not.
 */
export function resolveLink(href: string): Promise<ResolvedLink> {
  href = href.trim();
  if (!needsResolving(href)) {
    return Promise.resolve(describeLocally(href));
  }
  const cached = cache.get(href);
  if (cached !== undefined) {
    return cached;
  }
  const request = new AjaxRequest(TYPO3.settings.ajaxUrls.link_preview)
    .withQueryArguments({ href })
    .get()
    .then((response): Promise<ResolvedLink> => response.resolve())
    .then((resolved): ResolvedLink => {
      settled.set(href, resolved);
      return resolved;
    })
    .catch((): ResolvedLink => {
      cache.delete(href);
      return { type: null, title: null, path: null, url: null, icon: 'actions-link' };
    });
  cache.set(href, request);
  return request;
}

export function clearResolvedLinks(): void {
  cache.clear();
  settled.clear();
}

/**
 * Opens the frontend URL of a link in a new tab. The window is opened synchronously
 * (to not be blocked as a popup) and navigated as soon as the link is resolved.
 */
export function openResolvedLink(href: string): void {
  if (settled.get(href.trim())?.url === null) {
    return;
  }
  const target = window.open('', '_blank');
  if (target === null) {
    return;
  }
  target.opener = null;
  resolveLink(href).then(resolved => {
    if (resolved.url) {
      target.location.href = resolved.url;
    } else {
      target.close();
    }
  });
}
