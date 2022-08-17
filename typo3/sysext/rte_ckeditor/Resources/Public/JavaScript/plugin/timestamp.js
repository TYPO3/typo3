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
import{Core,UI}from"@typo3/ckeditor5-bundle.js";export default class Timestamp extends Core.Plugin{init(){const e=this.editor;e.ui.componentFactory.add(Timestamp.pluginName,(()=>{const t=new UI.ButtonView;return t.set({label:"Timestamp",withText:!0}),t.on("execute",(()=>{const t=new Date;e.model.change((n=>{e.model.insertContent(n.createText(t.toString()))}))})),t}))}}Timestamp.pluginName="Timestamp";