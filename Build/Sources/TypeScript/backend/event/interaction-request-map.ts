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

import type InteractionRequest from './interaction-request';
import type { InteractionRequestAssignment, PromiseControls } from './interaction-request-assignment';

class InteractionRequestMap {
  private assignments: InteractionRequestAssignment[] = [];

  public attachFor(request: InteractionRequest, deferred: PromiseControls<unknown>): void {
    let targetAssignment: InteractionRequestAssignment = this.getFor(request);
    if (targetAssignment === null) {
      targetAssignment = { request, deferreds: [] };
      this.assignments.push(targetAssignment);
    }
    targetAssignment.deferreds.push(deferred);
  }

  public detachFor(request: InteractionRequest): void {
    const targetAssignment = this.getFor(request);
    this.assignments = this.assignments.filter(
      (assignment: InteractionRequestAssignment) => assignment === targetAssignment,
    );
  }

  public getFor(triggerEvent: InteractionRequest): InteractionRequestAssignment | null {
    let targetAssignment: InteractionRequestAssignment = null;
    this.assignments.some(
      (assignment: InteractionRequestAssignment) => {
        if (assignment.request === triggerEvent) {
          targetAssignment = assignment;
          return true;
        }
        return false;
      },
    );
    return targetAssignment;
  }

  public resolveFor(triggerEvent: InteractionRequest): boolean {
    const targetAssignment = this.getFor(triggerEvent);
    if (targetAssignment === null) {
      return false;
    }
    targetAssignment.deferreds.forEach(
      deferred => deferred.resolve(),
    );
    this.detachFor(triggerEvent);
    return true;
  }

  public rejectFor(triggerEvent: InteractionRequest): boolean {
    const targetAssignment = this.getFor(triggerEvent);
    if (targetAssignment === null) {
      return false;
    }
    targetAssignment.deferreds.forEach(
      deferred => deferred.reject(),
    );
    this.detachFor(triggerEvent);
    return true;
  }
}

export default new InteractionRequestMap();
