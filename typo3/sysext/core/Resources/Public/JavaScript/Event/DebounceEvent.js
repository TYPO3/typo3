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
define(["require","exports","./RegularEvent"],(function(e,t,n){"use strict";return class extends n{constructor(e,t,n=250,u=!1){super(e,t),this.callback=this.debounce(this.callback,n,u)}debounce(e,t,n){let u=null;return()=>{const c=this,l=arguments,s=function(){u=null,n||e.apply(c,l)},r=n&&!u;clearTimeout(u),r?e.apply(c,l):u=setTimeout(s,t)}}}}));