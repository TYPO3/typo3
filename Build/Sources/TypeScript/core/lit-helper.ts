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

import { type CSSResultArray, html, type HTMLTemplateResult, render, nothing, type TemplateResult } from 'lit';
import type { ClassInfo } from 'lit/directives/class-map';
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
export const lll = (key: string, ...args: any[]): string => {
  let languagePool = null;
  if (window.TYPO3 && window.TYPO3.lang && typeof window.TYPO3.lang[key] === 'string') {
    languagePool = window.TYPO3.lang;
  } else if (top.TYPO3 && top.TYPO3.lang && typeof top.TYPO3.lang[key] === 'string') {
    languagePool = top.TYPO3.lang;
  }
  if (languagePool === null) {
    return '';
  }

  let index = 0;
  return languagePool[key].replace(/%[sdf]/g, (match) => {
    const arg = args[index++];
    switch (match) {
      case '%s':
        return String(arg);
      case '%d':
        return String(parseInt(arg, 10));
      case '%f':
        return String(parseFloat(arg).toFixed(2));
      default:
        return match;
    }
  });
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
