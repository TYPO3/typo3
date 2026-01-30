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

enum ModifierKeys {
  META = 'meta',
  CTRL = 'control',
  SHIFT = 'shift',
  ALT = 'alt',
}

export class HotkeyStruct {
  constructor(
    public readonly ctrl: boolean,
    public readonly meta: boolean,
    public readonly alt: boolean,
    public readonly shift: boolean,
    public readonly key: string,
  ) {
  }


  public static fromEvent(e: KeyboardEvent): HotkeyStruct {
    return new HotkeyStruct(e.ctrlKey, e.metaKey, e.altKey, e.shiftKey, e.key.toLowerCase());
  }

  public static fromHotkey(hotkey: string[]): HotkeyStruct {
    const nonModifierCodes = hotkey.filter((hotkeyPart: string) => !Object.values<string>(ModifierKeys).includes(hotkeyPart));
    if (nonModifierCodes.length > 1) {
      throw new Error('Cannot create HotkeyStruct with more than one non-modifier key, "' + nonModifierCodes.join('+') + '" given.');
    }

    return new HotkeyStruct(
      hotkey.includes(ModifierKeys.CTRL),
      hotkey.includes(ModifierKeys.META),
      hotkey.includes(ModifierKeys.ALT),
      hotkey.includes(ModifierKeys.SHIFT),
      nonModifierCodes[0].toLowerCase()
    );
  }

  public hasAnyModifier(): boolean {
    return this.ctrl || this.meta || this.alt || this.shift;
  }

  public toString(): string {
    return JSON.stringify(this);
  }
}
