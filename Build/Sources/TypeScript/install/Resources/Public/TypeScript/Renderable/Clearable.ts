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

class Clearable {
  private static createCloseButton(): HTMLButtonElement {
    // The inlined markup represents the current generated markup from the
    // icon api for the icon actions-close that can be found in the official
    // icon repository and is registered in the backend icon api.
    //
    // It´s not possible to use/open the backend icon api without opening
    // new possible vectors for attackers to sniff system information.
    //
    // When the icon definition of actions-close changes also the inline
    // icon should be updated.
    //
    // https://github.com/typo3/typo3.icons
    const closeIcon =
      `<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-close" data-identifier="actions-close">
        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                <path
                    d="M11.9 5.5L9.4 8l2.5 2.5c.2.2.2.5 0
                    .7l-.7.7c-.2.2-.5.2-.7 0L8 9.4l-2.5 2.5c-.2.2-.5.2-.7
                    0l-.7-.7c-.2-.2-.2-.5 0-.7L6.6 8 4.1 5.5c-.2-.2-.2-.5
                    0-.7l.7-.7c.2-.2.5-.2.7 0L8 6.6l2.5-2.5c.2-.2.5-.2.7
                    0l.7.7c.2.2.2.5 0 .7z"
                    class="icon-color"/>
              </svg>
            </span>
          </span>`;

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.tabIndex = -1;
    closeButton.innerHTML = closeIcon;
    closeButton.style.visibility = 'hidden';
    closeButton.classList.add('close');

    return closeButton;
  }

  constructor() {
    if (typeof HTMLInputElement.prototype.clearable === 'function') {
      return;
    }

    this.registerClearable();
  }

  private registerClearable(): void {
    HTMLInputElement.prototype.clearable = function(options: Options = {}): void {
      if (this.dataset.clearable) {
        // input field is already clearable, nothing to do here
        return;
      }

      if (typeof options !== 'object') {
        throw new Error('Passed options must be an object, ' + typeof options + ' given');
      }

      const wrap = document.createElement('div');
      wrap.classList.add('form-control-clearable');
      this.parentNode.insertBefore(wrap, this);
      wrap.appendChild(this);

      const clearButton = Clearable.createCloseButton();
      const toggleClearButtonVisibility = (): void => {
        clearButton.style.visibility = this.value.length === 0 ? 'hidden' : 'visible';
      };

      clearButton.addEventListener('click', (e: Event): void => {
        e.preventDefault();

        this.value = '';

        if (typeof options.onClear === 'function') {
          options.onClear(this);
        }

        this.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
        toggleClearButtonVisibility();
      });
      wrap.appendChild(clearButton);

      this.addEventListener('focus', toggleClearButtonVisibility);
      this.addEventListener('keyup', toggleClearButtonVisibility);

      toggleClearButtonVisibility();
      this.dataset.clearable = 'true';
    };
  }
}

export = new Clearable();
