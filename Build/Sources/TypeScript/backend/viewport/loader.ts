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

import { ScaffoldContentArea } from '../enum/viewport/scaffold-identifier';
import { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

class Loader {
  private static el: ProgressBarElement | null = null;

  public static start(): void {
    if (!this.el || !this.el.isConnected) {
      this.el = document.createElement('typo3-backend-progress-bar');
      ScaffoldContentArea.getContentContainer()?.appendChild(this.el);
    }
    this.el.start();
  }

  public static finish(): void {
    if (this.el) {
      this.el.done();
      this.el = null;
    }
  }
}

export default Loader;
