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

import { html } from 'lit';
import Modal, { Sizes } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import RegularEvent from '@typo3/core/event/regular-event';

new RegularEvent('click', (event: MouseEvent, target: HTMLElement): void => {
  event.preventDefault();
  const codeId = target.dataset.codeTarget;
  if (!codeId) {
    return;
  }
  const codeTemplate = document.getElementById(codeId);
  if (!(codeTemplate instanceof HTMLTemplateElement)) {
    return;
  }

  const code = (codeTemplate.content.textContent ?? '').trim();
  const languageClass = `language-${target.dataset.codeLanguage ?? ''}`;

  Modal.advanced({
    title: target.dataset.codeTitle,
    severity: SeverityEnum.notice,
    size: Sizes.default,
    content: html`<pre class=${languageClass}>${code}</pre>`,
  });
}).delegateTo(document, '.t3js-gif-builder-code-trigger');
