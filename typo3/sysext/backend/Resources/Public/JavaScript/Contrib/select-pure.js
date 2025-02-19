var k=Object.defineProperty;var I=(n,e)=>{for(var t in e)k(n,t,{get:e[t],enumerable:!0})};var g={};I(g,{OptionPure:()=>p,SelectPure:()=>o});function v(n){return typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?v=function(t){return typeof t}:v=function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},v(n)}function a(n,e,t){var i=t.value;if(typeof i!="function")throw new TypeError("@boundMethod decorator can only be applied to methods not: ".concat(v(i)));var r=!1;return{configurable:!0,get:function(){if(r||this===n.prototype||this.hasOwnProperty(e)||typeof i!="function")return i;var d=i.bind(this);return r=!0,Object.defineProperty(this,e,{configurable:!0,get:function(){return d},set:function(C){i=C,delete this[e]}}),r=!1,d},set:function(d){i=d}}}import{LitElement as _,html as $}from"lit";import{ifDefined as P}from"lit-html/directives/if-defined.js";import{customElement as D}from"lit/decorators/custom-element.js";import{property as f}from"lit/decorators/property.js";var b={ENTER:"Enter",TAB:"Tab"};var y=()=>{};var O={label:"",value:"",select:y,unselect:y,disabled:!1,hidden:!1,selected:!1};import{css as E}from"lit";var x=E`
  .select-wrapper {
    position: relative;
  }
  .select {
    bottom: 0;
    display: flex;
    flex-wrap: wrap;
    left: 0;
    position: absolute;
    right: 0;
    top: 0;
    width: var(--select-width, 100%);
  }
  .label:focus {
    outline: var(--select-outline, 2px solid #e3e3e3);
  }
  .label:after {
    border-bottom: 1px solid var(--color, #000);
    border-right: 1px solid var(--color, #000);
    box-sizing: border-box;
    content: "";
    display: block;
    height: 10px;
    margin-top: -2px;
    transform: rotate(45deg);
    transition: 0.2s ease-in-out;
    width: 10px;
  }
  .label.visible:after {
    margin-bottom: -4px;
    margin-top: 0;
    transform: rotate(225deg);
  }
  select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    position: relative;
    opacity: 0;
  }
  select[multiple] {
    z-index: 0;
  }
  select,
  .label {
    align-items: center;
    background-color: var(--background-color, #fff);
    border-radius: var(--border-radius, 4px);
    border: var(--border-width, 1px) solid var(--border-color, #000);
    box-sizing: border-box;
    color: var(--color, #000);
    cursor: pointer;
    display: flex;
    font-family: var(--font-family, inherit);
    font-size: var(--font-size, 14px);
    font-weight: var(--font-weight, 400);
    min-height: var(--select-height, 44px);
    justify-content: space-between;
    padding: var(--padding, 0 10px);
    width: 100%;
    z-index: 1;
  }
  @media only screen and (hover: none) and (pointer: coarse){
    select {
      z-index: 2;
    }
  }
  .dropdown {
    background-color: var(--border-color, #000);
    border-radius: var(--border-radius, 4px);
    border: var(--border-width, 1px) solid var(--border-color, #000);
    display: none;
    flex-direction: column;
    gap: var(--border-width, 1px);
    justify-content: space-between;
    max-height: calc(var(--select-height, 44px) * var(--dropdown-items, 4) + var(--border-width, 1px) * calc(var(--dropdown-items, 4) - 1));
    overflow-y: scroll;
    position: absolute;
    top: calc(var(--select-height, 44px) + var(--dropdown-gap, 0px));
    width: calc(100% - var(--border-width, 1px) * 2);
    z-index: var(--dropdown-z-index, 2);
  }
  .dropdown.visible {
    display: flex;
    z-index: 100;
  }
  .disabled {
    background-color: var(--disabled-background-color, #bdc3c7);
    color: var(--disabled-color, #ecf0f1);
    cursor: default;
  }
  .multi-selected {
    background-color: var(--selected-background-color, #e3e3e3);
    border-radius: var(--border-radius, 4px);
    color: var(--selected-color, #000);
    display: flex;
    gap: 8px;
    justify-content: space-between;
    padding: 2px 4px;
  }
  .multi-selected-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    width: calc(100% - 30px);
  }
  .cross:after {
    content: '\\00d7';
    display: inline-block;
    height: 100%;
    text-align: center;
    width: 12px;
  }
`;import{css as L}from"lit";var S=L`
  .option {
    align-items: center;
    background-color: var(--background-color, #fff);
    box-sizing: border-box;
    color: var(--color, #000);
    cursor: pointer;
    display: flex;
    font-family: var(--font-family, inherit);
    font-size: var(--font-size, 14px);
    font-weight: var(--font-weight, 400);
    min-height: var(--select-height, 44px);
    justify-content: flex-start;
    padding: var(--padding, 0 10px);
    width: 100%;
  }
  .option:not(.disabled):focus, .option:not(.disabled):not(.selected):hover {
    background-color: var(--hover-background-color, #e3e3e3);
    color: var(--hover-color, #000);
  }
  .selected {
    background-color: var(--selected-background-color, #e3e3e3);
    color: var(--selected-color, #000);
  }
  .disabled {
    background-color: var(--disabled-background-color, #e3e3e3);
    color: var(--disabled-color, #000);
    cursor: default;
  }
`;var u=function(n,e,t,i){var r=arguments.length,s=r<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,t):i,d;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")s=Reflect.decorate(n,e,t,i);else for(var h=n.length-1;h>=0;h--)(d=n[h])&&(s=(r<3?d(s):r>3?d(e,t,s):d(e,t))||s);return r>3&&s&&Object.defineProperty(e,t,s),s},p=class extends _{constructor(){super(...arguments),this.isSelected=!1,this.isDisabled=!1,this.isHidden=!1,this.optionValue="",this.displayedLabel="",this.optionIndex=-1}static get styles(){return S}connectedCallback(){super.connectedCallback(),this.isSelected=this.getAttribute("selected")!==null,this.isDisabled=this.getAttribute("disabled")!==null,this.isHidden=this.getAttribute("hidden")!==null,this.optionValue=this.getAttribute("value")||"",this.assignDisplayedLabel(),this.fireOnReadyCallback()}getOption(){return{label:this.displayedLabel,value:this.optionValue,select:this.select,unselect:this.unselect,selected:this.isSelected,disabled:this.isDisabled,hidden:this.isHidden}}select(){this.isSelected=!0,this.setAttribute("selected","")}unselect(){this.isSelected=!1,this.removeAttribute("selected")}setOnReadyCallback(e,t){this.onReady=e,this.optionIndex=t}setOnSelectCallback(e){this.onSelect=e}render(){let e=["option"];return this.isSelected&&e.push("selected"),this.isDisabled&&e.push("disabled"),$`
      <div
        class="${e.join(" ")}"
        @click=${this.fireOnSelectCallback}
        @keydown="${this.fireOnSelectIfEnterPressed}"
        tabindex="${P(this.isDisabled?void 0:"0")}"
      >
        <slot hidden @slotchange=${this.assignDisplayedLabel}></slot>
        ${this.displayedLabel}
      </div>
    `}assignDisplayedLabel(){if(this.textContent){this.displayedLabel=this.textContent;return}this.getAttribute("label")&&(this.displayedLabel=this.getAttribute("label")||"")}fireOnReadyCallback(){this.onReady&&this.onReady(this.getOption(),this.optionIndex)}fireOnSelectCallback(e){e.stopPropagation(),!(!this.onSelect||this.isDisabled)&&this.onSelect(this.optionValue)}fireOnSelectIfEnterPressed(e){e.key===b.ENTER&&this.fireOnSelectCallback(e)}};u([f()],p.prototype,"isSelected",void 0);u([f()],p.prototype,"isDisabled",void 0);u([f()],p.prototype,"isHidden",void 0);u([f()],p.prototype,"optionValue",void 0);u([f()],p.prototype,"displayedLabel",void 0);u([f()],p.prototype,"optionIndex",void 0);u([a],p.prototype,"getOption",null);u([a],p.prototype,"select",null);u([a],p.prototype,"unselect",null);u([a],p.prototype,"fireOnReadyCallback",null);p=u([D("option-pure")],p);import{LitElement as A,html as m}from"lit";import{ifDefined as w}from"lit-html/directives/if-defined.js";import{customElement as R}from"lit/decorators/custom-element.js";import{property as c}from"lit/decorators/property.js";import{query as z}from"lit/decorators/query.js";var l=function(n,e,t,i){var r=arguments.length,s=r<3?e:i===null?i=Object.getOwnPropertyDescriptor(e,t):i,d;if(typeof Reflect=="object"&&typeof Reflect.decorate=="function")s=Reflect.decorate(n,e,t,i);else for(var h=n.length-1;h>=0;h--)(d=n[h])&&(s=(r<3?d(s):r>3?d(e,t,s):d(e,t))||s);return r>3&&s&&Object.defineProperty(e,t,s),s},o=class extends A{constructor(){super(...arguments),this.options=[],this.visible=!1,this.selectedOption=O,this._selectedOptions=[],this.disabled=!1,this.isMultipleSelect=!1,this.name="",this._id="",this.formName="",this.value="",this.values=[],this.defaultLabel="",this.totalRenderedChildOptions=-1,this.form=null,this.hiddenInput=null}static get styles(){return x}connectedCallback(){super.connectedCallback(),this.disabled=this.getAttribute("disabled")!==null,this.isMultipleSelect=this.getAttribute("multiple")!==null,this.name=this.getAttribute("name")||"",this._id=this.getAttribute("id")||"",this.formName=this.name||this.id,this.defaultLabel=this.getAttribute("default-label")||""}open(){this.disabled||(this.visible=!0,this.removeEventListeners(),document.body.addEventListener("click",this.close,!0))}close(e){e&&this.contains(e.target)||(this.visible=!1,this.removeEventListeners())}enable(){this.disabled=!1}disable(){this.disabled=!0}get selectedIndex(){var e;return(e=this.nativeSelect)===null||e===void 0?void 0:e.selectedIndex}set selectedIndex(e){!e&&e!==0||this.selectOptionByValue(this.options[e].value)}get selectedOptions(){var e;return(e=this.nativeSelect)===null||e===void 0?void 0:e.selectedOptions}render(){let e=["label"];return this.disabled&&e.push("disabled"),this.visible&&e.push("visible"),m`
      <div class="select-wrapper">
        <select
          @change=${this.handleNativeSelectChange}
          ?disabled=${this.disabled}
          ?multiple=${this.isMultipleSelect}
          name="${w(this.name||void 0)}"
          id=${w(this.id||void 0)}
          size="1"
        >
          ${this.getNativeOptionsHtml()}
        </select>
        <div class="select">
          <div
            class="${e.join(" ")}"
            @click="${this.visible?this.close:this.open}"
            @keydown="${this.openDropdownIfProperKeyIsPressed}"
            tabindex="0"
          >
            ${this.getDisplayedLabel()}
          </div>
          <div class="dropdown${this.visible?" visible":""}">
            <slot @slotchange=${this.initializeSelect}></slot>
          </div>
        </div>
      </div>
    `}handleNativeSelectChange(){var e;this.selectedIndex=(e=this.nativeSelect)===null||e===void 0?void 0:e.selectedIndex}getNativeOptionsHtml(){return this.options.map(this.getSingleNativeOptionHtml)}getSingleNativeOptionHtml({value:e,label:t,hidden:i,disabled:r}){return m`
      <option
        value=${e}
        ?selected=${this.isOptionSelected(e)}
        ?hidden=${i}
        ?disabled=${r}
      >
        ${t}
      </option>
    `}isOptionSelected(e){let t=this.selectedOption.value===e;return this.isMultipleSelect&&(t=!!this._selectedOptions.find(i=>i.value===e)),t}openDropdownIfProperKeyIsPressed(e){(e.key===b.ENTER||e.key===b.TAB)&&this.open()}getDisplayedLabel(){return this.isMultipleSelect&&this._selectedOptions.length?this.getMultiSelectLabelHtml():this.selectedOption.label||this.defaultLabel}getMultiSelectLabelHtml(){return m`
      <div class="multi-selected-wrapper">
        ${this._selectedOptions.map(this.getMultiSelectSelectedOptionHtml)}
      </div>
    `}getMultiSelectSelectedOptionHtml({label:e,value:t}){return m`
      <span class="multi-selected">
        ${e}
        <span
          class="cross"
          @click=${i=>this.fireOnSelectCallback(i,t)}
        >
        </span>
      </span>
    `}fireOnSelectCallback(e,t){e.stopPropagation(),this.selectOptionByValue(t)}initializeSelect(){this.processChildOptions(),this.selectDefaultOptionIfNoneSelected(),this.appendHiddenInputToClosestForm()}processChildOptions(){let e=this.querySelectorAll("option-pure");this.totalRenderedChildOptions=e.length;for(let t=0;t<e.length;t++)this.initializeSingleOption(e[t],t)}selectDefaultOptionIfNoneSelected(){!this.selectedOption.value&&!this.isMultipleSelect&&this.options.length&&this.selectOptionByValue(this.options[0].value)}initializeSingleOption(e,t){e.setOnSelectCallback(this.selectOptionByValue),this.options[t]=e.getOption(),this.options[t].selected&&this.selectOptionByValue(this.options[t].value)}removeEventListeners(){document.body.removeEventListener("click",this.close)}appendHiddenInputToClosestForm(){this.form=this.closest("form"),!(!this.form||this.hiddenInput)&&(this.hiddenInput=document.createElement("input"),this.hiddenInput.setAttribute("type","hidden"),this.hiddenInput.setAttribute("name",this.formName),this.form.appendChild(this.hiddenInput))}unselectAllOptions(){for(let e=0;e<this.options.length;e++)this.options[e].unselect()}selectOptionByValue(e){let t=this.options.find(({value:i})=>i===e);t&&this.setSelectValue(t)}setSelectValue(e){this.isMultipleSelect?this.setMultiSelectValue(e):this.setSingleSelectValue(e),this.updateHiddenInputInForm(),this.dispatchChangeEvent()}dispatchChangeEvent(){this.dispatchEvent(new Event("change"))}setMultiSelectValue(e){let t=this._selectedOptions.indexOf(e);t!==-1?(this.values.splice(t,1),this._selectedOptions.splice(t,1),e.unselect()):(this.values.push(e.value),this._selectedOptions.push(e),e.select()),this.requestUpdate()}setSingleSelectValue(e){this.unselectAllOptions(),this.close(),this.selectedOption=e,this.value=e.value,e.select()}updateHiddenInputInForm(){if(!this.form||!this.hiddenInput)return;this.hiddenInput.value=this.isMultipleSelect?this.values.join(","):this.value;let e=new Event("change",{bubbles:!0});this.hiddenInput.dispatchEvent(e)}};l([c()],o.prototype,"options",void 0);l([c()],o.prototype,"visible",void 0);l([c()],o.prototype,"selectedOption",void 0);l([c()],o.prototype,"_selectedOptions",void 0);l([c()],o.prototype,"disabled",void 0);l([c()],o.prototype,"isMultipleSelect",void 0);l([c()],o.prototype,"name",void 0);l([c()],o.prototype,"_id",void 0);l([c()],o.prototype,"formName",void 0);l([c()],o.prototype,"value",void 0);l([c()],o.prototype,"values",void 0);l([c()],o.prototype,"defaultLabel",void 0);l([c()],o.prototype,"totalRenderedChildOptions",void 0);l([z("select")],o.prototype,"nativeSelect",void 0);l([a],o.prototype,"close",null);l([a],o.prototype,"getSingleNativeOptionHtml",null);l([a],o.prototype,"getMultiSelectLabelHtml",null);l([a],o.prototype,"getMultiSelectSelectedOptionHtml",null);l([a],o.prototype,"initializeSelect",null);l([a],o.prototype,"initializeSingleOption",null);l([a],o.prototype,"removeEventListeners",null);l([a],o.prototype,"appendHiddenInputToClosestForm",null);l([a],o.prototype,"selectOptionByValue",null);o=l([R("select-pure")],o);var xe=g;export{xe as default};
