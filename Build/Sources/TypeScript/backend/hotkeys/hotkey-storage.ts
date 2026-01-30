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

import type { HotkeyStruct } from '@typo3/backend/hotkeys/hotkey-struct';

export type HotkeyHandler = (e: KeyboardEvent) => void;
export type Options = {
  scope: string,
  allowOnEditables: boolean,
  allowRepeat: boolean,
  bindElement: Element|undefined
};
export type HotkeySetup = {
  struct: HotkeyStruct;
  handler: HotkeyHandler;
  options: Options;
};
type HotkeyMap = Map<string, HotkeySetup>;
export type ScopedHotkeyMap = Map<string, HotkeyMap>;

/**
 * Storage helper for the hotkeys module to keep registered hotkeys anywhere in the backend scaffold available
 */
class HotkeyStorage {
  public constructor(
    private readonly scopedHotkeyMap: ScopedHotkeyMap = new Map([
      ['all', new Map()]
    ]),
    public activeScope: string = 'all'
  ) {
  }

  public getScopedHotkeyMap(): ScopedHotkeyMap {
    return this.scopedHotkeyMap;
  }
}

export default new HotkeyStorage();
