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

export interface AspectRatioMatch {
  label: string;
  exact: boolean;
}

/**
 * Module: @typo3/backend/utility/aspect-ratio-utility
 *
 * @internal
 */
export class AspectRatioUtility {
  private static readonly commonRatios: Array<[number, number]> = [
    [1, 1], [5, 4], [4, 3], [3, 2], [16, 10], [16, 9], [21, 9], [2, 1], [3, 1],
    [4, 5], [3, 4], [2, 3], [10, 16], [9, 16], [9, 21], [1, 2], [1, 3],
  ];

  /**
   * Returns the common aspect ratio the given size corresponds to, or `null`
   * if none is within the relative `tolerance`.
   */
  public static find(width: number, height: number, tolerance: number = 0.02): AspectRatioMatch | null {
    if (width <= 0 || height <= 0) {
      return null;
    }
    const ratio = width / height;
    const divisor = AspectRatioUtility.greatestCommonDivisor(Math.round(width), Math.round(height));
    let best: { ratio: [number, number]; deviation: number } | null = null;
    for (const candidate of AspectRatioUtility.commonRatios) {
      const deviation = Math.abs(ratio - candidate[0] / candidate[1]) / (candidate[0] / candidate[1]);
      if (best === null || deviation < best.deviation) {
        best = { ratio: candidate, deviation };
      }
    }
    if (best === null || best.deviation > tolerance) {
      return null;
    }
    return {
      label: `${best.ratio[0]}:${best.ratio[1]}`,
      exact: Math.round(width) / divisor === best.ratio[0] && Math.round(height) / divisor === best.ratio[1],
    };
  }

  private static greatestCommonDivisor(a: number, b: number): number {
    return b === 0 ? a : AspectRatioUtility.greatestCommonDivisor(b, a % b);
  }
}
