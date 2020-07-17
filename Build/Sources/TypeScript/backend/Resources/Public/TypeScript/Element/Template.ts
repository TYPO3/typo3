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

import SecurityUtility = require('TYPO3/CMS/Core/SecurityUtility');

/**
 * Module: TYPO3/CMS/Backend/Element/Template
 *
 * @example
 * const value = 'Hello World';
 * const template = html`<div>${value}</div>`;
 * console.log(template.content);
 */
export class Template {
  private readonly securityUtility: SecurityUtility;
  private readonly strings: TemplateStringsArray;
  private readonly values: any[];
  private readonly unsafe: boolean;

  private closures: Map<string, Function>;

  public constructor(unsafe: boolean, strings: TemplateStringsArray, ...values: any[]) {
    this.securityUtility = new SecurityUtility();
    this.unsafe = unsafe;
    this.strings = strings;
    this.values = values;
    this.closures = new Map<string, Function>();
  }

  public getHtml(parentScope: Template = null): string {
    if (parentScope === null) {
      parentScope = this;
    }
    return this.strings
      .map((string: string, index: number) => {
        if (this.values[index] === undefined) {
          return string;
        }
        return string + this.getValue(this.values[index], parentScope);
      })
      .join('');
  }

  public getElement(): HTMLTemplateElement {
    const template = document.createElement('template');
    template.innerHTML = this.getHtml();
    return template;
  }

  public mountTo(renderRoot: HTMLElement | ShadowRoot, clear: boolean = false): void {
    if (clear) {
      renderRoot.innerHTML = '';
    }
    const fragment = this.getElement().content;
    const target = fragment.cloneNode(true) as DocumentFragment;
    const closurePattern = new RegExp('^@closure:(.+)$');
    target.querySelectorAll('[\\@click]').forEach((element: HTMLElement) => {
      const pointer = element.getAttribute('@click');
      const matches = closurePattern.exec(pointer);
      const closure = this.closures.get(matches[1]);
      if (matches === null || closure === null) {
        return;
      }
      element.removeAttribute('@click');
      element.addEventListener('click', (evt: Event) => closure.call(null, evt));
    });
    renderRoot.appendChild(target)
  }

  private getValue(value: any, parentScope: Template): string {
    if (value instanceof Array) {
      return value
        .map((item: any) => this.getValue(item, parentScope))
        .filter((item: string) => item !== '')
        .join('');
    }
    if (value instanceof Function) {
      const identifier = this.securityUtility.getRandomHexValue(20);
      parentScope.closures.set(identifier, value);
      return '@closure:' + identifier;
    }
    // @todo `value instanceof Template` is removed somewhere during minification
    if (value instanceof Template || value instanceof Object && value.constructor === this.constructor) {
      return value.getHtml(parentScope);
    }
    if (value instanceof Object) {
      return JSON.stringify(value);
    }
    if (this.unsafe) {
      return (value + '').trim();
    }
    return this.securityUtility.encodeHtml(value).trim();
  }
}

export const html = (strings: TemplateStringsArray, ...values: any[]): Template => {
  return new Template(false, strings, ...values);
}

export const unsafe = (strings: TemplateStringsArray, ...values: any[]): Template => {
  return new Template(true, strings, ...values);
}
