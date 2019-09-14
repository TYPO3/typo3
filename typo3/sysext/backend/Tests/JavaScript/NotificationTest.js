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
define(["require","exports","jquery","TYPO3/CMS/Backend/Notification"],function(e,t,o,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),describe("TYPO3/CMS/Backend/Notification:",()=>{beforeEach(()=>{o.fx.off=!0,jasmine.clock().install();const e=document.querySelector("#alert-container");for(;null!==e&&e.firstChild;)e.removeChild(e.firstChild)}),afterEach(()=>{jasmine.clock().uninstall()}),describe("can render notifications with dismiss after 1000ms",()=>{for(let e of[{method:a.notice,title:"Notice message",message:"This notification describes a notice",class:"alert-notice"},{method:a.info,title:"Info message",message:"This notification describes an informative action",class:"alert-info"},{method:a.success,title:"Success message",message:"This notification describes a successful action",class:"alert-success"},{method:a.warning,title:"Warning message",message:"This notification describes a harmful action",class:"alert-warning"},{method:a.error,title:"Error message",message:"This notification describes an erroneous action",class:"alert-danger"}])it("can render a notification of type "+e.class,()=>{e.method(e.title,e.message,1);const t="div.alert."+e.class,o=document.querySelector(t);expect(o).not.toBe(null),expect(o.querySelector(".alert-title").textContent).toEqual(e.title),expect(o.querySelector(".alert-message").textContent).toEqual(e.message),jasmine.clock().tick(1200),expect(document.querySelector(t)).toBe(null)})}),it("can render action buttons",()=>{a.info("Info message","Some text",1,[{label:"My action",action:{type:"immediate",callback:e=>e}},{label:"My other action",action:e=>e}]);const e=document.querySelector("div.alert");expect(e.querySelector(".alert-actions")).not.toBe(null),expect(e.querySelectorAll(".alert-actions a").length).toEqual(2),expect(e.querySelectorAll(".alert-actions a")[0].textContent).toEqual("My action"),expect(e.querySelectorAll(".alert-actions a")[1].textContent).toEqual("My other action")})})});