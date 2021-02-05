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

import type {TemplateResult} from 'lit-html';
import {html, render, Part} from 'lit-html';
import {unsafeHTML} from 'lit-html/directives/unsafe-html';
import {until} from 'lit-html/directives/until';
import Icons = require('TYPO3/CMS/Backend/Icons');

import 'TYPO3/CMS/Backend/Element/SpinnerElement';

export const renderElement = (result: TemplateResult): HTMLElement => {
  const anvil = document.createElement('div');
  render(result, anvil);
  return anvil;
}
export const renderHTML = (result: TemplateResult): string => {
  return renderElement(result).innerHTML;
};
export const lll = (key: string): string => {
  if (!window.TYPO3 || !window.TYPO3.lang || typeof window.TYPO3.lang[key] !== 'string') {
    return '';
  }
  return window.TYPO3.lang[key];
};

export const icon = (identifier: string, size: any = 'small') => {
  // @todo Fetched and resolved icons should be stored in a session repository in `Icons`
  const icon = Icons.getIcon(identifier, size).then((markup: string) => html`${unsafeHTML(markup)}`);
  return html`${until(icon, html`<typo3-backend-spinner size="${size}"></typo3-backend-spinner>`)}`;
};



