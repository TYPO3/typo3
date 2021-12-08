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

import InteractionRequest from './InteractionRequest';
import InteractionRequestAssignment from './InteractionRequestAssignment';

class InteractionRequestMap {
  private assignments: InteractionRequestAssignment[] = [];

  public attachFor(request: InteractionRequest, deferred: any): void {
    let targetAssignment = this.getFor(request);
    if (targetAssignment === null) {
      targetAssignment = {request, deferreds: []} as InteractionRequestAssignment;
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

  public getFor(triggerEvent: InteractionRequest): InteractionRequestAssignment {
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
      (deferred: any) => deferred.resolve(),
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
      (deferred: any) => deferred.reject(),
    );
    this.detachFor(triggerEvent);
    return true;
  }
}

export default new InteractionRequestMap();
