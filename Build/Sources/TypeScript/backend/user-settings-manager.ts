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

import { BroadcastMessage, BroadcastEvent } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';

enum Identifier {
  colorSchemeSwitch = 'typo3-backend-color-scheme-switch',
}

export type ColorScheme = 'auto' | 'light' | 'dark';
export type Theme = 'modern' | 'classic';

// Event for typo3:color-scheme:update and typo3:color-scheme:broadcast
export interface ColorSchemeUpdateEventData {
  colorScheme: ColorScheme;
}

// Event for typo3:theme:update and typo3:theme:broadcast
export interface ThemeUpdateEventData {
  theme: Theme;
}

class UserSettingsManager {
  constructor() {
    // triggered by
    //  * <typo3-backend-color-scheme-switch> (topbar) or
    //  * User setup module (via BackendUtility::setUpdateSignal('updateColorScheme', …))
    document.addEventListener('typo3:color-scheme:update', e => this.onColorSchemeUpdate(e.detail));
    //  triggred by user setup module (via BackendUtility::setUpdateSignal('updateColorScheme', …))
    document.addEventListener('typo3:theme:update', e => this.onThemeUpdate(e.detail));

    // broadcast message by other instances
    document.addEventListener('typo3:color-scheme:broadcast', e => this.activateColorScheme(e.detail.payload.colorScheme));
    document.addEventListener('typo3:theme:broadcast', e => this.activateTheme(e.detail.payload.theme));
  }

  private onColorSchemeUpdate(data: ColorSchemeUpdateEventData) {
    const { colorScheme } = data;
    this.activateColorScheme(colorScheme);

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<ColorSchemeUpdateEventData>('color-scheme', 'broadcast', { colorScheme }));
  }

  private onThemeUpdate(data: ThemeUpdateEventData) {
    const { theme } = data;
    this.activateTheme(theme);

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<ThemeUpdateEventData>('theme', 'broadcast', { theme }));
  }

  private activateColorScheme(colorScheme: ColorScheme) {
    document.documentElement.setAttribute('data-color-scheme', colorScheme);
    window.frames.list_frame?.document.documentElement.setAttribute('data-color-scheme', colorScheme);
    const colorSchemeSwitch = document.querySelector(Identifier.colorSchemeSwitch);
    if (colorSchemeSwitch) {
      colorSchemeSwitch.activeColorScheme = colorScheme;
    }
  }

  private activateTheme(theme: Theme) {
    document.documentElement.setAttribute('data-theme', theme);
    window.frames.list_frame?.document.documentElement.setAttribute('data-theme', theme);
  }
}

export default new UserSettingsManager();

declare global {
  interface DocumentEventMap {
    'typo3:color-scheme:update': CustomEvent<ColorSchemeUpdateEventData>;
    'typo3:color-scheme:broadcast': BroadcastEvent<ColorSchemeUpdateEventData>;
    'typo3:theme:update': CustomEvent<ThemeUpdateEventData>;
    'typo3:theme:broadcast': BroadcastEvent<ThemeUpdateEventData>;
  }
}
