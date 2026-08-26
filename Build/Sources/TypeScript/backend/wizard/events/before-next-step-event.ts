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
export class BeforeNextStepEvent extends CustomEvent<{ currentStepKey: string; currentStepIndex: number, result?: Promise<void> }> {
  static readonly eventName = 'wizard-before-next-step';

  constructor(currentStepKey: string, currentStepIndex: number) {
    super(BeforeNextStepEvent.eventName, {
      detail: {
        currentStepKey,
        currentStepIndex
      },
    });
  }
}

declare global {
  interface HTMLElementEventMap {
    [BeforeNextStepEvent.eventName]: BeforeNextStepEvent;
  }
}
