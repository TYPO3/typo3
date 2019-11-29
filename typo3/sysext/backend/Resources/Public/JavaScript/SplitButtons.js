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
define(["require","exports","./DocumentSaveActions"],(function(e,n,t){"use strict";return new class{constructor(){console.warn("TYPO3/CMS/Backend/SplitButtons has been marked as deprecated, consider using TYPO3/CMS/Backend/DocumentSaveActions instead")}addPreSubmitCallback(e){t.getInstance().addPreSubmitCallback(e)}}}));