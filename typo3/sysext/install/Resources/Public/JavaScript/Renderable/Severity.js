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
define(["require","exports"],(function(e,s){"use strict";return new class{constructor(){this.loading=-3,this.notice=-2,this.info=-1,this.ok=0,this.warning=1,this.error=2}getCssClass(e){let s;switch(e){case this.loading:s="notice alert-loading";break;case this.notice:s="notice";break;case this.ok:s="success";break;case this.warning:s="warning";break;case this.error:s="danger";break;case this.info:default:s="info"}return s}}}));