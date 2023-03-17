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
import{Core,UI,Utils}from"@typo3/ckeditor5-bundle.js";class Whitespace extends Core.Plugin{init(){const e=this.editor;e.ui.componentFactory.add("softhyphen",(e=>{const t=new UI.ButtonView(e);return t.label="Soft-Hyphen",t.icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" xml:space="preserve"><path d="M4.25 3C3.082 4.683 2 6.917 2 10.026 2 13.083 3.114 15.282 4.25 17H3c-1.008-1.425-2-3.624-2-6.974.016-3.384.992-5.583 2-7.026h1.25zM17 3c1.008 1.443 1.984 3.642 2 7.026 0 3.35-.992 5.549-2 6.974h-1.25c1.136-1.718 2.25-3.917 2.25-6.974 0-3.11-1.082-5.343-2.25-7.026H17zM6 9h8v2H6z"/></svg>',t.on("execute",(()=>this.insertSoftHyphen())),t}));const t=Utils.env.isMac?"Alt":"Ctrl";e.keystrokes.set([t,"Shift",189],((e,t)=>{this.insertSoftHyphen(),t()})),e.keystrokes.set([t,"Shift","Space"],((e,t)=>{this.insertNonBreakingSpace(),t()})),e.conversion.for("editingDowncast").add((e=>{e.on("insert:$text",((e,t,i)=>{if(!i.consumable.consume(t.item,e.name))return;const n=i.writer,o=t.item.data.split(/([\u00AD,\u00A0])/).filter((e=>""!==e));let s=t.range.start;o.forEach((e=>{if(n.insert(i.mapper.toViewPosition(s),n.createText(e)),"­"===e||" "===e){const t="­"===e?"softhyphen":"nbsp",o=n.createAttributeElement("span",{class:"ck ck-"+t}),r=n.createRange(i.mapper.toViewPosition(s),i.mapper.toViewPosition(s.getShiftedBy(e.length)));n.wrap(r,o)}s=s.getShiftedBy(e.length)}))}),{priority:"high"})}))}insertNonBreakingSpace(){const e=this.editor;e.model.change((t=>{const i=e.model.document.selection.getFirstPosition();t.insertText(" ",i)}))}insertSoftHyphen(){const e=this.editor;e.model.change((t=>{const i=e.model.document.selection.getFirstPosition();t.insertText("­",i)}))}}Whitespace.pluginName="Whitespace";export default Whitespace;