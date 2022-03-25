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

import {render, TemplateResult} from 'lit';
import {ClassInfo} from 'lit/directives/class-map';

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
}

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
}
