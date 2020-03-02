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
define(["require","exports","./RegularEvent"],(function(t,e,r){"use strict";return class extends r{constructor(t,e,r){super(t,e),this.callback=this.throttle(e,r)}throttle(t,e){let r=!1;return function(...s){r||(t.apply(this,s),r=!0,setTimeout(()=>{r=!1,t.apply(this,s)},e))}}}}));