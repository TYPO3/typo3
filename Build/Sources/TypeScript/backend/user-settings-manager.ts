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
    const colorSchemeSwitch = document.querySelector(Identifier.colorSchemeSwitch);
    if (colorSchemeSwitch) {
      colorSchemeSwitch.activeColorScheme = colorScheme;
    }
    this.setStyleChangingDocumentAttribute('data-color-scheme', colorScheme);
  }

  private activateTheme(theme: Theme) {
    this.setStyleChangingDocumentAttribute('data-theme', theme);
  }

  private async setStyleChangingDocumentAttribute(attributeName: string, attributeValue: string) {
    const rootEl = document.documentElement;
    const frame = window.frames.list_frame?.document.documentElement;

    const action = () => {
      rootEl.classList.add('t3js-disable-transitions');
      frame?.classList.add('t3js-disable-transitions');

      rootEl.setAttribute(attributeName, attributeValue);
      frame?.setAttribute(attributeName, attributeValue);
    };

    const cleanup = () => {
      rootEl.classList.remove('t3js-disable-transitions');
      frame?.classList.remove('t3js-disable-transitions');
    };


    if (
      window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
      // The fallback condition in the next line (currently needed for firefox) can be removed
      // once view transitions enter baseline "Widely available":
      // https://webstatus.dev/features/view-transitions?q=view+transition
      !('startViewTransition' in document) || typeof document.startViewTransition !== 'function'
    ) {
      action();

      // await animation frame in order for the transition disable to be
      // considered by the time the change-transitions are being started.
      await new Promise(resolve => requestAnimationFrame(resolve));
      if (frame) {
        await new Promise(resolve => window.frames.list_frame.requestAnimationFrame(resolve));
      }
      cleanup();
      return;
    }

    await document.startViewTransition(action).finished;
    cleanup();
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
