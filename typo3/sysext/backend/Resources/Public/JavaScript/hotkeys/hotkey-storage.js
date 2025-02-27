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
class HotkeyStorage{constructor(t=new Map([["all",new Map]]),e="all"){this.scopedHotkeyMap=t,this.activeScope=e}getScopedHotkeyMap(){return this.scopedHotkeyMap}}let hotkeysStorageInstance;top.TYPO3.HotkeyStorage?hotkeysStorageInstance=top.TYPO3.HotkeyStorage:(hotkeysStorageInstance=new HotkeyStorage,top.TYPO3.HotkeyStorage=hotkeysStorageInstance);export default hotkeysStorageInstance;