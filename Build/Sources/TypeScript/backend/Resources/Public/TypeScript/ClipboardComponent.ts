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

class ClipboardComponent {
  private static setCheckboxValue(checkboxName: string, check: boolean): void {
    const fullName = 'CBC[' + checkboxName + ']';
    const checkboxElement: HTMLInputElement = document.querySelector('form[name="dblistForm"] [name="' + fullName + '"]');
    if (checkboxElement !== null) {
      checkboxElement.checked = check;
    }
  }

  constructor() {
    this.registerCheckboxTogglers();
  }

  private registerCheckboxTogglers(): void {
    const selector = 'a.t3js-toggle-all-checkboxes';
    document.addEventListener('click', (e: Event): void => {
      let target = <HTMLElement>e.target;
      if (!target.matches(selector)) {
        let closest: HTMLElement = target.closest(selector);
        if (closest !== null) {
          target = closest;
        } else {
          return;
        }
      }

      e.preventDefault();

      let flagAll: boolean;
      if (target.getAttribute('rel') === '') {
        target.setAttribute('rel', 'allChecked');
        flagAll = true;
      } else {
        target.setAttribute('rel', '');
        flagAll = false;
      }

      const listOfCheckboxNames: Array<string> = target.dataset.checkboxesNames.split(',');
      for (let checkboxName of listOfCheckboxNames) {
        ClipboardComponent.setCheckboxValue(checkboxName, flagAll);
      }
    });
  }
}

export = new ClipboardComponent();
