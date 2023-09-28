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
import{Core,UI,Utils,Typing}from"@typo3/ckeditor5-bundle.js";class Whitespace extends Core.Plugin{init(){const e=this.editor,t=e.commands.get("insertText"),i=Utils.env.isMac?"Alt":"Ctrl";e.ui.componentFactory.add("softhyphen",(e=>{const n=new UI.ButtonView(e);return n.label="Soft-Hyphen",n.icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" xml:space="preserve"><path d="M4.25 3C3.082 4.683 2 6.917 2 10.026 2 13.083 3.114 15.282 4.25 17H3c-1.008-1.425-2-3.624-2-6.974.016-3.384.992-5.583 2-7.026h1.25zM17 3c1.008 1.443 1.984 3.642 2 7.026 0 3.35-.992 5.549-2 6.974h-1.25c1.136-1.718 2.25-3.917 2.25-6.974 0-3.11-1.082-5.343-2.25-7.026H17zM6 9h8v2H6z"/></svg>',n.keystroke=`${i}+Shift+-`,n.tooltip=!0,n.bind("isEnabled").to(t),n.on("execute",(()=>this.insertSoftHyphen())),n})),e.keystrokes.set([i,"Shift",189],((e,t)=>{this.insertSoftHyphen(),t()})),e.keystrokes.set([i,"Shift","Space"],((e,t)=>{this.insertNonBreakingSpace(),t()})),e.conversion.for("editingDowncast").add((e=>{e.on("insert:$text",((e,t,i)=>{if(!i.consumable.consume(t.item,e.name))return;const n=i.writer,s=t.item.data.split(/([\u00AD\u00A0])/).filter((e=>""!==e));let o=t.range.start;s.forEach((e=>{const t="­"===e?"-":e;if(n.insert(i.mapper.toViewPosition(o),n.createText(t)),"­"===e||" "===e){const t="­"===e?"softhyphen":"nbsp",s=Math.random().toString(16).slice(2),r=n.createAttributeElement("span",{class:`ck ck-${t}`},{id:s}),c=n.createRange(i.mapper.toViewPosition(o),i.mapper.toViewPosition(o.getShiftedBy(e.length)));n.wrap(c,r)}o=o.getShiftedBy(e.length)}))}),{priority:"high"})}))}insertNonBreakingSpace(){const e=this.editor;e.execute("insertText",{text:" "}),e.editing.view.focus()}insertSoftHyphen(){const e=this.editor;e.execute("insertText",{text:"­"}),e.editing.view.focus()}}Whitespace.pluginName="Whitespace",Whitespace.requires=[Typing.Typing];export default Whitespace;