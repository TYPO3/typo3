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
import{html as a,nothing as d}from"lit";import{until as h}from"lit/directives/until.js";import{Task as u,TaskStatus as i}from"@lit/task";import t from"~labels/backend.wizards.general";class o{constructor(r,s){this.wizard=r,this.finisher=s,this.key="finisher",this.title=t.get("step.finisher.title"),this.autoAdvance=!1,this.resetButtonTitle=null,this.hasError=!1,this.finisherInstance=null,this.task=new u(this.wizard,{task:async([e])=>e.execute(),args:()=>[this.finisher],autoRun:!1})}isComplete(){return this.task.status===i.COMPLETE||this.task.status===i.ERROR}async beforeAdvance(){if(this.hasError){this.wizard.dismissWizard();return}if(!this.finisherInstance)throw new Error("Finisher instance not loaded");this.wizard.dismissWizard(),await this.finisherInstance.execute()}render(){return this.task.status===i.INITIAL&&this.task.run(),this.task.render({pending:()=>this.wizard.renderLoader(t.get("wizard.status.pending.message")),error:r=>(this.hasError=!0,this.wizard.renderError(t.get("wizard.status.error.message"),r)),complete:r=>r.success===!1?(this.hasError=!0,this.wizard.renderError(t.get("wizard.status.error.message"),r.errors)):(r?.finisher?.data?.resetButtonTitle&&(this.resetButtonTitle=String(r?.finisher?.data?.resetButtonTitle)),this.renderFinisher(r.finisher))})}renderFinisher(r){if(!this.finisherInstance){const s=this.loadFinisher(r).then(e=>(this.finisherInstance=e,e.render())).catch(e=>(console.error("Failed to load finisher:",e),this.hasError=!0,this.wizard.renderError(t.get("wizard.finisher.load_error.message"),e)));return a`${h(s,this.wizard.renderLoader(t.get("wizard.loading_finisher")))}`}return a`${h(this.finisherInstance.render(),d)}`}async loadFinisher(r){if(!r.module)throw new Error("Finisher data does not contain a module path");const e=(await import(r.module)).default;if(!e)throw new Error(`Finisher module ${r.module} does not export a default class`);const n=new e;return n.setConfig(r),n}}export{o as FinisherStep,o as default};
