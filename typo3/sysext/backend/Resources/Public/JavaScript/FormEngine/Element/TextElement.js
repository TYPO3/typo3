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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","./Modifier/Resizable","./Modifier/Tabbable","jquery"],(function(e,t,i,l,n){"use strict";n=__importDefault(n);return class{constructor(e){this.element=null,n.default(()=>{this.element=document.getElementById(e),i.Resizable.enable(this.element),l.Tabbable.enable(this.element)})}}}));