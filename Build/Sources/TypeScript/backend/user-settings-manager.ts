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

import { BroadcastMessage, type BroadcastEvent } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';
import Persistent from '@typo3/backend/storage/persistent';
import Modal, { type ModalElement } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import labels from '~labels/backend.messages';

enum Identifier {
  colorSchemeSwitch = 'typo3-backend-color-scheme-switch',
}

export type ColorScheme = 'auto' | 'light' | 'dark';
export type Theme = 'modern' | 'classic' | 'fresh';
export type TitleFormat = 'titleFirst' | 'sitenameFirst';
export type DayOfWeek = '' | '1' | '2' | '3' | '4' | '5' | '6' | '7'; // 1=Sunday, 2=Monday, ... 7=Saturday

// Event for typo3:color-scheme:update and typo3:color-scheme:broadcast
export interface ColorSchemeUpdateEventData {
  colorScheme: ColorScheme;
}

// Event for typo3:theme:update and typo3:theme:broadcast
export interface ThemeUpdateEventData {
  theme: Theme;
}

// Event for typo3:title-format:update and typo3:title-format:broadcast
export interface TitleFormatUpdateEventData {
  format: TitleFormat;
}

// Event for typo3:date-time-first-day-of-week:update and typo3:date-time-first-day-of-week:broadcast
export interface DateTimeFirstDayOfWeekUpdateEventData {
  dow: DayOfWeek;
}

// Event for typo3:backend-language:update and typo3:backend-language:broadcast
export interface BackendLanguageUpdateEventData {
  language: string;
}

// Event for typo3:persistent:update and typo3:persistent:broadcast
export interface PersistentUpdateEventData {
  fieldName: string;
  value: string;
}

class UserSettingsManager {
  constructor() {
    // triggered by
    //  * <typo3-backend-color-scheme-switch> (topbar) or
    //  * User setup module (via BackendUtility::setUpdateSignal('updateColorScheme', …))
    document.addEventListener('typo3:color-scheme:update', e => this.onColorSchemeUpdate(e.detail));
    //  triggered by user setup module (via BackendUtility::setUpdateSignal('updateColorScheme', …))
    document.addEventListener('typo3:theme:update', e => this.onThemeUpdate(e.detail));
    //  triggered by user setup module (via BackendUtility::setUpdateSignal('updateTitleFormat', …))
    document.addEventListener('typo3:title-format:update', e => this.onTitleFormatUpdate(e.detail));
    //  triggered by user setup module (via BackendUtility::setUpdateSignal('updateBackendLanguage', …))
    document.addEventListener('typo3:backend-language:update', e => this.onBackendLanguageFormatUpdate(e.detail));
    //  triggered by user setup module (via BackendUtility::setUpdateSignal('updatePersistent', …))
    document.addEventListener('typo3:persistent:update', e => this.onPersistentUpdate(e.detail));
    //  triggered by user setup module (via BackendUtility::setUpdateSignal('updateDateTimeFirstDayOfWeek', …))
    document.addEventListener('typo3:date-time-first-day-of-week:update', e => this.onDateTimeFirstDayOfWeekUpdate(e.detail));
    // broadcast message by other instances
    document.addEventListener('typo3:color-scheme:broadcast', e => this.activateColorScheme(e.detail.payload.colorScheme));
    document.addEventListener('typo3:theme:broadcast', e => this.activateTheme(e.detail.payload.theme));
    document.addEventListener('typo3:title-format:broadcast', e => this.activateTitleFormat(e.detail.payload.format));
    document.addEventListener('typo3:backend-language:broadcast', () => this.requestBackendLanguageRefresh());
    document.addEventListener('typo3:persistent:broadcast', e => this.updatePersistent(e.detail.payload.fieldName, e.detail.payload.value));
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

  private onTitleFormatUpdate(data: TitleFormatUpdateEventData) {
    const { format } = data;
    this.activateTitleFormat(format);

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<TitleFormatUpdateEventData>('title-format', 'broadcast', { format }));
  }

  private onDateTimeFirstDayOfWeekUpdate(data: DateTimeFirstDayOfWeekUpdateEventData) {
    const { dow } = data;
    this.activateDateTimeFirstDayOfWeek(dow);

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<DateTimeFirstDayOfWeekUpdateEventData>('date-time-first-day-of-week', 'broadcast', { dow }));
  }

  private onBackendLanguageFormatUpdate(data: BackendLanguageUpdateEventData) {
    const { language } = data;
    this.requestBackendLanguageRefresh();

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<BackendLanguageUpdateEventData>('backend-language', 'broadcast', { language }));
  }

  private onPersistentUpdate(data: PersistentUpdateEventData) {
    const { fieldName, value } = data;
    this.updatePersistent(fieldName, value);

    // broadcast to other instances
    BroadcastService.post(new BroadcastMessage<PersistentUpdateEventData>('personalization', 'broadcast', { fieldName: fieldName, value }));
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

  private activateTitleFormat(format: TitleFormat) {
    if (format === 'sitenameFirst') {
      document.querySelector('typo3-backend-module-router')?.setAttribute('sitename-first', '');
    } else {
      document.querySelector('typo3-backend-module-router')?.removeAttribute('sitename-first');
    }
  }

  private activateDateTimeFirstDayOfWeek(dow: DayOfWeek) {
    this.updatePersistent('dateTimeFirstDayOfWeek', dow);
  }

  private requestBackendLanguageRefresh(): void {
    const className = 't3js-request-backend-language-refresh';
    if (Modal.currentModal?.querySelector('dialog')?.classList.contains(className)) {
      // Prevent opening multiple modals if the language
      // is switched multiple times in another tab
      return;
    }
    Modal.confirm(
      labels.get('userSettings.requestBackendLanguageRefresh.title'),
      labels.get('userSettings.requestBackendLanguageRefresh.message'),
      SeverityEnum.notice,
      [
        {
          text: labels.get('userSettings.requestBackendLanguageRefresh.buttonCancel'),
          btnClass: 'btn-default',
          trigger: (e: Event, modal: ModalElement) => modal.hideModal(),
          name: 'cancel',
        },
        {
          text: labels.get('userSettings.requestBackendLanguageRefresh.buttonReload'),
          active: true,
          btnClass: 'btn-primary',
          trigger: () => top.window.location.reload(),
          name: 'ok',
        },
      ],
      [className],
    );
  }

  private updatePersistent(fieldName: string, value: string) {
    Persistent.set(fieldName, value);
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
    'typo3:title-format:update': CustomEvent<TitleFormatUpdateEventData>;
    'typo3:title-format:broadcast': BroadcastEvent<TitleFormatUpdateEventData>;
    'typo3:backend-language:update': CustomEvent<BackendLanguageUpdateEventData>;
    'typo3:backend-language:broadcast': BroadcastEvent<BackendLanguageUpdateEventData>;
    'typo3:persistent:update': CustomEvent<PersistentUpdateEventData>;
    'typo3:persistent:broadcast': BroadcastEvent<PersistentUpdateEventData>;
    'typo3:date-time-first-day-of-week:update': CustomEvent<DateTimeFirstDayOfWeekUpdateEventData>;
    'typo3:date-time-first-day-of-week:broadcast': BroadcastEvent<DateTimeFirstDayOfWeekUpdateEventData>;
  }
}
