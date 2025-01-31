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

import { CSSResultArray, html, HTMLTemplateResult, render, nothing, TemplateResult } from 'lit';
import { ClassInfo } from 'lit/directives/class-map';
import { until } from 'lit/directives/until';

interface LitNonceWindow extends Window {
  litNonce?: string;
}

/**
 * @internal
 */
export const renderNodes = (result: TemplateResult): NodeList => {
  const anvil = document.createElement('div');
  render(result, anvil);
  return anvil.childNodes;
};

/**
 * @internal
 */
export const renderHTML = (result: TemplateResult): string => {
  const anvil = document.createElement('div');
  render(result, anvil);
  return anvil.innerHTML;
};

/**
 * @internal
 */
export const lll = (key: string): string => {
  if (!window.TYPO3 || !window.TYPO3.lang || typeof window.TYPO3.lang[key] !== 'string') {
    return '';
  }
  return window.TYPO3.lang[key];
};

type Writeable<T> = { -readonly [P in keyof T]: T[P] };

export const classesArrayToClassInfo = (classes: Array<string>): ClassInfo => {
  return classes.reduce(
    (classInfo: Writeable<ClassInfo>, name: string): Writeable<ClassInfo> => {
      classInfo[name] = true;
      return classInfo;
    },
    {} as Writeable<ClassInfo>
  );
};

/**
 * Creates style tag having using a nonce-value if declared in `window.litNonce`
 * (see https://lit.dev/docs/api/ReactiveElement/#ReactiveElement.styles)
 *
 * @example
 * ```
 *  return html`
 *    ${styleTag`
 *      .my-style { ... }
 *    `}
 *    <div class="my-style">...</div>
 *  `
 * ```
 * produces a template result containing
 * ```
 *    <style nonce="...">
 *      .my-style { ... }
 *    </style>
 *    <div class="my-style">...</div>
 * ```
 * @deprecated avoid using dynamic styles in light DOM elements
 */
export const styleTag = (strings: string[]|TemplateStringsArray|CSSResultArray, windowRef?: Window): HTMLTemplateResult => {
  const nonce = ((windowRef || window) as LitNonceWindow).litNonce;
  if (nonce) {
    return html`<style nonce="${nonce}">${strings}</style>`;
  }
  return html`<style>${strings}</style>`;
};

/**
 * Delays rendering a renderable by `timeout` milliseconds
 *
 * @example
 *
 * ```
 *   return html`${delay(80, () => html`Loading…`)}`
 * ```
 *
 * @internal
 */
export const delay = <T>(timeout: number, result: () => T, fallback = () => nothing) => until(
  new Promise<T>((resolve) => window.setTimeout(() => resolve(result()), timeout)),
  fallback()
);
