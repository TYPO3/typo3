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

import {NavigationComponentInterface} from './NavigationComponentInterface';

class PageTree {
  private readonly instance: NavigationComponentInterface = null;

  constructor(instance: NavigationComponentInterface) {
    this.instance = instance;
  }

  public refreshTree(): void {
    if (this.instance !== null) {
      this.instance.refreshTree();
    }
  }

  public setTemporaryMountPoint(pid: number): void {
    if (this.instance !== null) {
      this.instance.setTemporaryMountPoint(pid);
    }
  }

  public unsetTemporaryMountPoint(): void {
    if (this.instance !== null) {
      this.instance.unsetTemporaryMountPoint();
    }
  }

  public selectNode(node: object): void {
    if (this.instance !== null) {
      this.instance.selectNode(node);
    }
  }

  public getFirstNode(): object {
    if (this.instance !== null) {
      return this.instance.getFirstNode();
    }

    return {};
  }
}

export = PageTree;
