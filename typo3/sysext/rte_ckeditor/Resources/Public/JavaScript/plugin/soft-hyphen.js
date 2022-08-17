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
import{UI,Core}from"@typo3/ckeditor5-bundle.js";export default class SoftHyphen extends Core.Plugin{init(){this.editor.ui.componentFactory.add(SoftHyphen.pluginName,(t=>{const e=new UI.ButtonView(t);return e.label="Soft-Hyphen",e.icon='<svg id="Ebene_1" data-name="Ebene 1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 16 16"><defs><style>.cls-1{fill:none;}.cls-2{fill:#464646;}.cls-3{fill:url(#Unbenannter_Verlauf_19);}</style><linearGradient id="Unbenannter_Verlauf_19" x1="3" y1="8" x2="13" y2="8" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="0.1" stop-color="#464646"/><stop offset="0.9" stop-color="#464646"/><stop offset="1" stop-opacity="0"/></linearGradient></defs><title>typo3_softhyphen</title><rect class="cls-1" width="16" height="16"/><path class="cls-2" d="M1,8a8.51,8.51,0,0,1,.4-2.7A6.12,6.12,0,0,1,2.79,3H4a9.55,9.55,0,0,0-.78,1.13A7,7,0,0,0,2.67,5.3a5.91,5.91,0,0,0-.32,1.27,9.35,9.35,0,0,0,0,2.86,5.91,5.91,0,0,0,.32,1.27,7,7,0,0,0,.55,1.17A9.55,9.55,0,0,0,4,13H2.79A6.12,6.12,0,0,1,1.4,10.7,8.51,8.51,0,0,1,1,8Z"/><path class="cls-2" d="M15,8a8.51,8.51,0,0,1-.4,2.7A6.12,6.12,0,0,1,13.21,13H12a9.55,9.55,0,0,0,.78-1.13,7,7,0,0,0,.55-1.17,5.91,5.91,0,0,0,.32-1.27,9.35,9.35,0,0,0,0-2.86,5.91,5.91,0,0,0-.32-1.27,7,7,0,0,0-.55-1.17A9.55,9.55,0,0,0,12,3h1.21A6.12,6.12,0,0,1,14.6,5.3,8.51,8.51,0,0,1,15,8Z"/><rect class="cls-3" x="3" y="7" width="10" height="2"/></svg>',e.keystroke="Ctrl!+-",e.on("execute",(()=>this.addSoftHyphen())),e}))}addSoftHyphen(){const t=this.editor;t.model.change((e=>{t.model.insertContent(e.createText("­"))}))}}SoftHyphen.pluginName="SoftHyphen";