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

import {DraggablePositionEnum} from '@typo3/backend/tree/drag-drop';

/**
 * Drag & drop helper classes for use in TYPO3 backend
 *
 * @internal - heavily WIP; no public API, yet
 */

export type DragDropItemInterface = {
  identifier: string;
  name: string;
}

export type DragDropDataTransferItem = {
  type: string,
  identifier: string,
  name: string,
  extra?: { [key: string]: any }
};

export class DragDropOperation {
  public constructor(
    public readonly source: DragDropItemInterface,
    public readonly type: string,
    public readonly position: DraggablePositionEnum = DraggablePositionEnum.INSIDE,
    public readonly extra: { [key: string]: string } = {},
  ) {
  }
}

export class DragDropOperationCollection {
  /**
   * Builds a FileDragDropOperationCollection based on dataTransfer
   */
  public static fromDataTransfer(dataTransfer: DataTransfer, target: DragDropItemInterface): DragDropOperationCollection {
    return DragDropOperationCollection.fromArray(JSON.parse(dataTransfer.getData('application/json')), target);
  }

  public static fromArray(transferItems: DragDropDataTransferItem[], target: DragDropItemInterface): DragDropOperationCollection {
    const operations: DragDropOperation[] = [];

    for (let transferItem of transferItems) {
      operations.push(new DragDropOperation(
        { identifier: transferItem.identifier, name: transferItem.name },
        transferItem.type,
        DraggablePositionEnum.INSIDE,
        transferItem.extra
      ))
    }

    return new DragDropOperationCollection(operations, target);
  }

  protected constructor(
    public readonly operations: DragDropOperation[],
    public readonly target: DragDropItemInterface,
  ) {
  }
}
