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

import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import NProgress from 'nprogress';

class Loader {
  public static start(): void {
    NProgress.configure({parent: ScaffoldIdentifierEnum.contentModule, showSpinner: false});
    NProgress.start();
  }

  public static finish(): void {
    NProgress.done();
  }
}

export default Loader;
