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
define(["require","exports","./RegularEvent"],(function(e,t,n){"use strict";return class extends n{constructor(e,t,n=250,s=!1){super(e,t),this.callback=this.debounce(this.callback,n,s)}debounce(e,t,n){let s=null;return function(...u){const c=n&&!s;clearTimeout(s),c?e.apply(this,u):s=setTimeout(()=>{s=null,n||e.apply(this,u)},t)}}}}));