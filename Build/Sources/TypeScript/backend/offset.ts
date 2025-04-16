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

/**
 * @internal
 */
export class Offset {
  constructor(
    public left: number,
    public top: number,
    public width: number,
    public height: number
  ) {}

  get right(): number {
    return this.left + this.width;
  }

  get bottom(): number {
    return this.top + this.height;
  }

  public clone(): Offset {
    return new Offset(this.left, this.top, this.width, this.height);
  }
}
