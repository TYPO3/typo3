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

export interface ResourceInterface {
  type: string;
  identifier: string;
  name: string;
  hasPreview: boolean;
  uid: number | null;
  metaUid: number | null;
  url: string | null;
  createdAt: number | null;
  size: number | null;
}

export class Resource implements ResourceInterface {
  public constructor(
    public readonly type: string,
    public readonly identifier: string,
    public readonly name: string,
    public readonly hasPreview: boolean = false,
    public readonly uid: number | null = null,
    public readonly metaUid: number | null = null,
    public readonly url: string | null = null,
    public readonly createdAt: number | null = null,
    public readonly size: number | null = null,
  ) {
  }
}
