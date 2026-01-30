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
class t extends Event{static{this.eventName="typo3:hotkey:requested"}constructor(e){super(t.eventName,{bubbles:!0,composed:!0,cancelable:!1}),this.keyboardEvent=e}}class s extends Event{static{this.eventName="typo3:hotkey:dispatched"}constructor(e){super(s.eventName,{bubbles:!1,composed:!0,cancelable:!0}),this.keyboardEvent=e}}export{s as HotkeyDispatchedEvent,t as HotkeyRequestedEvent};
