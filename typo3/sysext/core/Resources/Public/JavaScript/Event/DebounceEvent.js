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
define(["require","exports","./RegularEvent"],(function(e,t,u){"use strict";return class extends u{constructor(e,t,u=250,l=!1){super(e,t),this.callback=this.debounce(this.callback,u,l)}debounce(e,t,u){let l=null;return function(...n){const s=u&&!l;clearTimeout(l),s?(e.apply(this,n),l=setTimeout(()=>{l=null},t)):l=setTimeout(()=>{l=null,u||e.apply(this,n)},t)}}}}));