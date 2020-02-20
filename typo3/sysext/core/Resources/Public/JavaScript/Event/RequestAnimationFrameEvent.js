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
define(["require","exports","./RegularEvent"],(function(e,t,n){"use strict";return class extends n{constructor(e,t){super(e,t),this.callback=this.req(this.callback)}req(e){let t=null;return()=>{const n=this,r=arguments;t&&window.cancelAnimationFrame(t),t=window.requestAnimationFrame((function(){e.apply(n,r)}))}}}}));