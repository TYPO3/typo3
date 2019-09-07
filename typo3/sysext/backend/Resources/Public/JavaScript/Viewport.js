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
define(["require","exports","jquery","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar"],function(t,i,o,n,e,a,r,s){"use strict";class c{constructor(){this.Loader=a,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=e,o(()=>this.initialize()),this.Topbar=new s,this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new n(this.consumerScope)}doLayout(){this.NavigationContainer.cleanup(),this.NavigationContainer.calculateScrollbar(),o(".t3js-topbar-header").css("padding-right",o(".t3js-scaffold-toolbar").outerWidth())}initialize(){this.doLayout(),o(window).on("resize",()=>{this.doLayout()})}}let u;return top.TYPO3.Backend?u=top.TYPO3.Backend:(u=new c,top.TYPO3.Backend=u),u});