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
define(["require","exports","jquery","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar","TYPO3/CMS/Core/Event/ThrottleEvent"],(function(t,e,n,o,i,a,r,s,c){"use strict";class h{constructor(){this.Loader=a,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=i,n(()=>this.initialize()),this.Topbar=new s,this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new o(this.consumerScope)}doLayout(){this.NavigationContainer.cleanup(),this.NavigationContainer.calculateScrollbar(),n(".t3js-topbar-header").css("padding-right",n(".t3js-scaffold-toolbar").outerWidth())}initialize(){this.doLayout(),new c("resize",()=>{this.doLayout()},100).bindTo(window)}}let u;return top.TYPO3.Backend?u=top.TYPO3.Backend:(u=new h,top.TYPO3.Backend=u),u}));