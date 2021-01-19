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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar","TYPO3/CMS/Core/Event/ThrottleEvent"],(function(t,e,i,n,o,a,r,s,u){"use strict";i=__importDefault(i);class c{constructor(){this.Loader=a,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=o,i.default(()=>this.initialize()),this.Topbar=new s,this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new n(this.consumerScope)}doLayout(){this.NavigationContainer.cleanup(),this.NavigationContainer.calculateScrollbar()}initialize(){this.doLayout(),new u("resize",()=>{this.doLayout()},100).bindTo(window)}}let l;return top.TYPO3.Backend?l=top.TYPO3.Backend:(l=new c,top.TYPO3.Backend=l),l}));