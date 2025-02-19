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
import*as w from"@ckeditor/ckeditor5-core";import*as x from"@ckeditor/ckeditor5-ui";import*as y from"@ckeditor/ckeditor5-utils";import*as S from"@ckeditor/ckeditor5-typing";class p extends w.Plugin{static{this.pluginName="Whitespace"}static{this.requires=[S.Typing]}init(){const e=this.editor,h=e.commands.get("insertText"),a=y.env.isMac?"Alt":"Ctrl";e.ui.componentFactory.add("softhyphen",s=>{const t=new x.ButtonView(s);return t.label="Soft-Hyphen",t.icon='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" xml:space="preserve"><path d="M4.25 3C3.082 4.683 2 6.917 2 10.026 2 13.083 3.114 15.282 4.25 17H3c-1.008-1.425-2-3.624-2-6.974.016-3.384.992-5.583 2-7.026h1.25zM17 3c1.008 1.443 1.984 3.642 2 7.026 0 3.35-.992 5.549-2 6.974h-1.25c1.136-1.718 2.25-3.917 2.25-6.974 0-3.11-1.082-5.343-2.25-7.026H17zM6 9h8v2H6z"/></svg>',t.keystroke=`${a}+Shift+-`,t.tooltip=!0,t.bind("isEnabled").to(h),t.on("execute",()=>this.insertSoftHyphen()),t}),e.keystrokes.set([a,"Shift",189],(s,t)=>{this.insertSoftHyphen(),t()}),e.keystrokes.set([a,"Shift","Space"],(s,t)=>{this.insertNonBreakingSpace(),t()}),e.conversion.for("editingDowncast").add(s=>{s.on("insert:$text",(t,c,o)=>{if(!o.consumable.consume(c.item,t.name))return;const n=o.writer,m=c.item.data.split(/([\u00AD\u00A0])/).filter(i=>i!=="");let r=c.range.start;m.forEach(i=>{const d=i==="\xAD"?"-":i;if(n.insert(o.mapper.toViewPosition(r),n.createText(d)),i==="\xAD"||i==="\xA0"){const l=i==="\xAD"?"softhyphen":"nbsp",f=Math.random().toString(16).slice(2),g=n.createAttributeElement("span",{class:`ck ck-${l}`},{id:f}),u=n.createRange(o.mapper.toViewPosition(r),o.mapper.toViewPosition(r.getShiftedBy(i.length)));n.wrap(u,g)}r=r.getShiftedBy(i.length)})},{priority:"high"})})}insertNonBreakingSpace(){const e=this.editor;e.execute("insertText",{text:"\xA0"}),e.editing.view.focus()}insertSoftHyphen(){const e=this.editor;e.execute("insertText",{text:"\xAD"}),e.editing.view.focus()}}export{p as Whitespace,p as default};
