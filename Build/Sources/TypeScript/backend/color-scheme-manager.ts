/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read theÍ
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

enum Identifier {
  switch = 'typo3-backend-color-scheme-switch',
}

class ColorSchemeManager {
  constructor() {
    document.addEventListener('typo3:color-scheme:update', this.onBroadcastSchemeUpdate.bind(this));
  }

  private onBroadcastSchemeUpdate(event: CustomEvent<{ name: string; payload: { name: string }}>) {
    // The tab-local broadcast message is missing the "payload" object #93270
    const colorScheme = event.detail.payload?.name || event.detail.name;

    document.documentElement.setAttribute('data-color-scheme', colorScheme);
    document.list_frame.document.documentElement.setAttribute('data-color-scheme', colorScheme);

    this.updateActiveScheme(colorScheme);
  }

  private updateActiveScheme(colorScheme: string) {
    const colorSchemeSwitch = document.querySelector(Identifier.switch);
    colorSchemeSwitch.activeColorScheme = colorScheme;
  }
}

export default new ColorSchemeManager();
