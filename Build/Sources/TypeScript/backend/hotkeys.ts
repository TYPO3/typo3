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

import HotkeyStorage, { type ScopedHotkeyMap, type HotkeyStruct, type Options, type HotkeySetup } from '@typo3/backend/hotkeys/hotkey-storage';
import RegularEvent from '@typo3/core/event/regular-event';

export enum ModifierKeys {
  META = 'meta',
  CTRL = 'control',
  SHIFT = 'shift',
  ALT = 'alt',
}

type Hotkey = string[];

/**
 * Module: @typo3/backend/hotkeys
 *
 * Provides API to register hotkeys (aka shortcuts) in the TYPO3 backend. It is possible to register hotkeys in
 * different scopes, that can also be switched during runtime. Extensions should always specify their scope when
 * registering hotkeys.
 *
 * Due to how the TYPO3 backend currently works, registered hotkeys are limited to the same document the API is used in.
 */
class Hotkeys {
  // navigator.platform is deprecated, but https://developer.mozilla.org/en-US/docs/Web/API/User-Agent_Client_Hints_API is experimental for now
  public readonly normalizedCtrlModifierKey = navigator.platform.toLowerCase().startsWith('mac') ? ModifierKeys.META : ModifierKeys.CTRL;
  private readonly scopedHotkeyMap: ScopedHotkeyMap;
  private readonly defaultOptions: Options = {
    scope: 'all',
    allowOnEditables: false,
    allowRepeat: false,
    bindElement: undefined
  };

  public constructor() {
    this.scopedHotkeyMap = HotkeyStorage.getScopedHotkeyMap();
    this.setScope('all');
    this.registerEventHandler();
  }

  public setScope(scope: string): void {
    HotkeyStorage.activeScope = scope;
  }

  public getScope(): string {
    return HotkeyStorage.activeScope;
  }

  public register(hotkey: Hotkey, handler: (e: KeyboardEvent) => void, options: Partial<Options> = {}): void {
    if (hotkey.filter((hotkeyPart: string) => !Object.values<string>(ModifierKeys).includes(hotkeyPart)).length === 0) {
      throw new Error('Attempted to register hotkey "' + hotkey.join('+') + '" without a non-modifier key.');
    }

    // Normalize trigger
    hotkey = hotkey.map((h: string) => h.toLowerCase());

    const mergedConfiguration: Options = { ...this.defaultOptions, ...options };
    if (!this.scopedHotkeyMap.has(mergedConfiguration.scope)) {
      this.scopedHotkeyMap.set(mergedConfiguration.scope, new Map());
    }

    let ariaKeyShortcut = this.composeAriaKeyShortcut(hotkey);
    const hotkeyMap = this.scopedHotkeyMap.get(mergedConfiguration.scope);
    const hotkeyStruct = this.createHotkeyStructFromTrigger(hotkey);
    const encodedHotkeyStruct = JSON.stringify(hotkeyStruct);

    if (hotkeyMap.has(encodedHotkeyStruct)) {
      const setup = hotkeyMap.get(encodedHotkeyStruct);

      // Hotkey already exists, remove potentially set `aria-keyshortcuts` for this hotkey
      setup.options.bindElement?.removeAttribute('aria-keyshortcuts');
      // Delete existing hotkey. If the existing hotkey was registered in a different browser scope, the callback is lost
      hotkeyMap.delete(encodedHotkeyStruct);
    }
    hotkeyMap.set(encodedHotkeyStruct, { struct: hotkeyStruct, handler, options: mergedConfiguration });

    if (mergedConfiguration.bindElement instanceof Element) {
      const existingAriaAttribute = mergedConfiguration.bindElement.getAttribute('aria-keyshortcuts');
      if (existingAriaAttribute !== null && !existingAriaAttribute.includes(ariaKeyShortcut)) {
        // Element already has `aria-keyshortcuts`, append composed shortcut
        ariaKeyShortcut = existingAriaAttribute + ' ' + ariaKeyShortcut;
      }
      mergedConfiguration.bindElement.setAttribute('aria-keyshortcuts', ariaKeyShortcut);
    }
  }

  private registerEventHandler(): void {
    new RegularEvent('keydown', (e: KeyboardEvent): void => {
      const hotkeySetup = this.findHotkeySetup(e);
      if (hotkeySetup === null) {
        return;
      }

      if (e.repeat && !hotkeySetup.options.allowRepeat) {
        return;
      }

      if (!hotkeySetup.options.allowOnEditables) {
        const target = e.target as HTMLElement;
        if (target.isContentEditable || (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) && !(e.target as HTMLInputElement|HTMLTextAreaElement).readOnly)) {
          return;
        }
      }

      hotkeySetup.handler(e);
    }).bindTo(document);
  }

  private findHotkeySetup(e: KeyboardEvent): HotkeySetup|undefined {
    // We always consider the global "all" scope first to avoid overriding global hotkeys
    const scopes: string[] = [...new Set(['all', HotkeyStorage.activeScope])];
    const hotkeyStruct = this.createHotkeyStructFromEvent(e);
    const encodedHotkeyStruct = JSON.stringify(hotkeyStruct);

    for (const scope of scopes) {
      const hotkeyMap = this.scopedHotkeyMap.get(scope);
      if (hotkeyMap.has(encodedHotkeyStruct)) {
        return hotkeyMap.get(encodedHotkeyStruct);
      }
    }

    return null;
  }

  private createHotkeyStructFromTrigger(hotkey: Hotkey): HotkeyStruct {
    const nonModifierCodes = hotkey.filter((hotkeyPart: string) => !Object.values<string>(ModifierKeys).includes(hotkeyPart));
    if (nonModifierCodes.length > 1) {
      throw new Error('Cannot register hotkey with more than one non-modifier key, "' + nonModifierCodes.join('+') + '" given.');
    }

    return {
      modifiers: {
        meta: hotkey.includes(ModifierKeys.META),
        ctrl: hotkey.includes(ModifierKeys.CTRL),
        shift: hotkey.includes(ModifierKeys.SHIFT),
        alt: hotkey.includes(ModifierKeys.ALT),
      },
      key: nonModifierCodes[0].toLowerCase(),
    };
  }

  private createHotkeyStructFromEvent(e: KeyboardEvent): HotkeyStruct {
    return {
      modifiers: {
        meta: e.metaKey,
        ctrl: e.ctrlKey,
        shift: e.shiftKey,
        alt: e.altKey,
      },
      key: e.key?.toLowerCase(),
    };
  }

  /**
   * Composes a string for use with `aria-keyshortcuts`
   * @see https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-keyshortcuts
   */
  private composeAriaKeyShortcut(hotkey: Hotkey): string {
    const parts: string[] = [];

    for (let key of hotkey) {
      if (key === '+') {
        key = 'plus';
      } else {
        key = key.replace(/[\u00A0-\u9999<>&]/g, i => '&#' + i.charCodeAt(0) + ';');
      }

      parts.push(key);
    }

    // The standard requires to have modifier keys to be at first
    parts.sort((a: string, b: string): number => {
      const aIsModifierKey = Object.values<string>(ModifierKeys).includes(a);
      const bIsModifierKey = Object.values<string>(ModifierKeys).includes(b);

      if (aIsModifierKey && !bIsModifierKey) {
        return -1;
      }

      if (!aIsModifierKey && bIsModifierKey) {
        return 1;
      }

      if (aIsModifierKey && bIsModifierKey) {
        return -1;
      }

      return 0;
    });

    return parts.join('+');
  }
}

// Helper to always get the same instance within a frame
// @todo: have the module in `top` scope, while being able to register the `keydown` event in each frame
let hotkeysInstance: Hotkeys;
if (!TYPO3.Hotkeys) {
  hotkeysInstance = new Hotkeys();
  TYPO3.Hotkeys = hotkeysInstance;
} else {
  hotkeysInstance = TYPO3.Hotkeys;
}

export default hotkeysInstance;
