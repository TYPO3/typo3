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
define(["require","exports","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar"],(function(n,t,e,o,i,r,a){"use strict";class s{constructor(){this.Loader=i,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=o,this.Topbar=new a,this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new e(this.consumerScope)}}let c;return top.TYPO3.Backend?c=top.TYPO3.Backend:(c=new s,top.TYPO3.Backend=c),c}));