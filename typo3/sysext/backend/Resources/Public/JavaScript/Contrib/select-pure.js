import{css,LitElement,html}from"lit";import{ifDefined}from"lit/directives/if-defined.js";import{customElement}from"lit/decorators/custom-element.js";import{property}from"lit/decorators/property.js";import{query}from"lit/decorators/query.js";function _typeof(e){return _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},_typeof(e)}function boundMethod(e,t,i){var o=i.value;if("function"!=typeof o)throw new TypeError("@boundMethod decorator can only be applied to methods not: ".concat(_typeof(o)));var l=!1;return{configurable:!0,get:function(){if(l||this===e.prototype||this.hasOwnProperty(t)||"function"!=typeof o)return o;var i=o.bind(this);return l=!0,Object.defineProperty(this,t,{configurable:!0,get:function(){return i},set:function(e){o=e,delete this[t]}}),l=!1,i},set:function(e){o=e}}}const KEYS={ENTER:"Enter",TAB:"Tab"},noop=()=>{},defaultOption={label:"",value:"",select:noop,unselect:noop,disabled:!1,hidden:!1,selected:!1},selectStyles=css`
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
`,optionStyles=css`
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
`;var __decorate$1=function(e,t,i,o){var l,s=arguments.length,r=s<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)r=Reflect.decorate(e,t,i,o);else for(var n=e.length-1;n>=0;n--)(l=e[n])&&(r=(s<3?l(r):s>3?l(t,i,r):l(t,i))||r);return s>3&&r&&Object.defineProperty(t,i,r),r};let OptionPure=class extends LitElement{constructor(){super(...arguments),this.isSelected=!1,this.isDisabled=!1,this.isHidden=!1,this.optionValue="",this.displayedLabel="",this.optionIndex=-1}static get styles(){return optionStyles}connectedCallback(){super.connectedCallback(),this.isSelected=null!==this.getAttribute("selected"),this.isDisabled=null!==this.getAttribute("disabled"),this.isHidden=null!==this.getAttribute("hidden"),this.optionValue=this.getAttribute("value")||"",this.assignDisplayedLabel(),this.fireOnReadyCallback()}getOption(){return{label:this.displayedLabel,value:this.optionValue,select:this.select,unselect:this.unselect,selected:this.isSelected,disabled:this.isDisabled,hidden:this.isHidden}}select(){this.isSelected=!0,this.setAttribute("selected","")}unselect(){this.isSelected=!1,this.removeAttribute("selected")}setOnReadyCallback(e,t){this.onReady=e,this.optionIndex=t}setOnSelectCallback(e){this.onSelect=e}render(){const e=["option"];return this.isSelected&&e.push("selected"),this.isDisabled&&e.push("disabled"),html`
      <div
        class="${e.join(" ")}"
        @click=${this.fireOnSelectCallback}
        @keydown="${this.fireOnSelectIfEnterPressed}"
        tabindex="${ifDefined(this.isDisabled?void 0:"0")}"
      >
        <slot hidden @slotchange=${this.assignDisplayedLabel}></slot>
        ${this.displayedLabel}
      </div>
    `}assignDisplayedLabel(){this.textContent?this.displayedLabel=this.textContent:this.getAttribute("label")&&(this.displayedLabel=this.getAttribute("label")||"")}fireOnReadyCallback(){this.onReady&&this.onReady(this.getOption(),this.optionIndex)}fireOnSelectCallback(e){e.stopPropagation(),this.onSelect&&!this.isDisabled&&this.onSelect(this.optionValue)}fireOnSelectIfEnterPressed(e){e.key===KEYS.ENTER&&this.fireOnSelectCallback(e)}};__decorate$1([property()],OptionPure.prototype,"isSelected",void 0),__decorate$1([property()],OptionPure.prototype,"isDisabled",void 0),__decorate$1([property()],OptionPure.prototype,"isHidden",void 0),__decorate$1([property()],OptionPure.prototype,"optionValue",void 0),__decorate$1([property()],OptionPure.prototype,"displayedLabel",void 0),__decorate$1([property()],OptionPure.prototype,"optionIndex",void 0),__decorate$1([boundMethod],OptionPure.prototype,"getOption",null),__decorate$1([boundMethod],OptionPure.prototype,"select",null),__decorate$1([boundMethod],OptionPure.prototype,"unselect",null),__decorate$1([boundMethod],OptionPure.prototype,"fireOnReadyCallback",null),OptionPure=__decorate$1([customElement("option-pure")],OptionPure);var __decorate=function(e,t,i,o){var l,s=arguments.length,r=s<3?t:null===o?o=Object.getOwnPropertyDescriptor(t,i):o;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)r=Reflect.decorate(e,t,i,o);else for(var n=e.length-1;n>=0;n--)(l=e[n])&&(r=(s<3?l(r):s>3?l(t,i,r):l(t,i))||r);return s>3&&r&&Object.defineProperty(t,i,r),r};let SelectPure=class extends LitElement{constructor(){super(...arguments),this.options=[],this.visible=!1,this.selectedOption=defaultOption,this._selectedOptions=[],this.disabled=!1,this.isMultipleSelect=!1,this.name="",this._id="",this.formName="",this.value="",this.values=[],this.defaultLabel="",this.totalRenderedChildOptions=-1,this.form=null,this.hiddenInput=null}static get styles(){return selectStyles}connectedCallback(){super.connectedCallback(),this.disabled=null!==this.getAttribute("disabled"),this.isMultipleSelect=null!==this.getAttribute("multiple"),this.name=this.getAttribute("name")||"",this._id=this.getAttribute("id")||"",this.formName=this.name||this.id,this.defaultLabel=this.getAttribute("default-label")||""}open(){this.disabled||(this.visible=!0,this.removeEventListeners(),document.body.addEventListener("click",this.close,!0))}close(e){e&&this.contains(e.target)||(this.visible=!1,this.removeEventListeners())}enable(){this.disabled=!1}disable(){this.disabled=!0}get selectedIndex(){var e;return null===(e=this.nativeSelect)||void 0===e?void 0:e.selectedIndex}set selectedIndex(e){(e||0===e)&&this.selectOptionByValue(this.options[e].value)}get selectedOptions(){var e;return null===(e=this.nativeSelect)||void 0===e?void 0:e.selectedOptions}render(){const e=["label"];return this.disabled&&e.push("disabled"),this.visible&&e.push("visible"),html`
      <div class="select-wrapper">
        <select
          @change=${this.handleNativeSelectChange}
          ?disabled=${this.disabled}
          ?multiple=${this.isMultipleSelect}
          name="${ifDefined(this.name||void 0)}"
          id=${ifDefined(this.id||void 0)}
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
    `}handleNativeSelectChange(){var e;this.selectedIndex=null===(e=this.nativeSelect)||void 0===e?void 0:e.selectedIndex}getNativeOptionsHtml(){return this.options.map(this.getSingleNativeOptionHtml)}getSingleNativeOptionHtml({value:e,label:t,hidden:i,disabled:o}){return html`
      <option
        value=${e}
        ?selected=${this.isOptionSelected(e)}
        ?hidden=${i}
        ?disabled=${o}
      >
        ${t}
      </option>
    `}isOptionSelected(e){let t=this.selectedOption.value===e;return this.isMultipleSelect&&(t=Boolean(this._selectedOptions.find((t=>t.value===e)))),t}openDropdownIfProperKeyIsPressed(e){e.key!==KEYS.ENTER&&e.key!==KEYS.TAB||this.open()}getDisplayedLabel(){return this.isMultipleSelect&&this._selectedOptions.length?this.getMultiSelectLabelHtml():this.selectedOption.label||this.defaultLabel}getMultiSelectLabelHtml(){return html`
      <div class="multi-selected-wrapper">
        ${this._selectedOptions.map(this.getMultiSelectSelectedOptionHtml)}
      </div>
    `}getMultiSelectSelectedOptionHtml({label:e,value:t}){return html`
      <span class="multi-selected">
        ${e}
        <span
          class="cross"
          @click=${e=>this.fireOnSelectCallback(e,t)}
        >
        </span>
      </span>
    `}fireOnSelectCallback(e,t){e.stopPropagation(),this.selectOptionByValue(t)}initializeSelect(){this.processChildOptions(),this.selectDefaultOptionIfNoneSelected(),this.appendHiddenInputToClosestForm()}processChildOptions(){const e=this.querySelectorAll("option-pure");this.totalRenderedChildOptions=e.length;for(let t=0;t<e.length;t++)this.initializeSingleOption(e[t],t)}selectDefaultOptionIfNoneSelected(){!this.selectedOption.value&&!this.isMultipleSelect&&this.options.length&&this.selectOptionByValue(this.options[0].value)}initializeSingleOption(e,t){e.setOnSelectCallback(this.selectOptionByValue),this.options[t]=e.getOption(),this.options[t].selected&&this.selectOptionByValue(this.options[t].value)}removeEventListeners(){document.body.removeEventListener("click",this.close)}appendHiddenInputToClosestForm(){this.form=this.closest("form"),this.form&&!this.hiddenInput&&(this.hiddenInput=document.createElement("input"),this.hiddenInput.setAttribute("type","hidden"),this.hiddenInput.setAttribute("name",this.formName),this.form.appendChild(this.hiddenInput))}unselectAllOptions(){for(let e=0;e<this.options.length;e++)this.options[e].unselect()}selectOptionByValue(e){const t=this.options.find((({value:t})=>t===e));t&&this.setSelectValue(t)}setSelectValue(e){this.isMultipleSelect?this.setMultiSelectValue(e):this.setSingleSelectValue(e),this.updateHiddenInputInForm(),this.dispatchChangeEvent()}dispatchChangeEvent(){this.dispatchEvent(new Event("change"))}setMultiSelectValue(e){const t=this._selectedOptions.indexOf(e);-1!==t?(this.values.splice(t,1),this._selectedOptions.splice(t,1),e.unselect()):(this.values.push(e.value),this._selectedOptions.push(e),e.select()),this.requestUpdate()}setSingleSelectValue(e){this.unselectAllOptions(),this.close(),this.selectedOption=e,this.value=e.value,e.select()}updateHiddenInputInForm(){if(!this.form||!this.hiddenInput)return;this.hiddenInput.value=this.isMultipleSelect?this.values.join(","):this.value;const e=new Event("change",{bubbles:!0});this.hiddenInput.dispatchEvent(e)}};__decorate([property()],SelectPure.prototype,"options",void 0),__decorate([property()],SelectPure.prototype,"visible",void 0),__decorate([property()],SelectPure.prototype,"selectedOption",void 0),__decorate([property()],SelectPure.prototype,"_selectedOptions",void 0),__decorate([property()],SelectPure.prototype,"disabled",void 0),__decorate([property()],SelectPure.prototype,"isMultipleSelect",void 0),__decorate([property()],SelectPure.prototype,"name",void 0),__decorate([property()],SelectPure.prototype,"_id",void 0),__decorate([property()],SelectPure.prototype,"formName",void 0),__decorate([property()],SelectPure.prototype,"value",void 0),__decorate([property()],SelectPure.prototype,"values",void 0),__decorate([property()],SelectPure.prototype,"defaultLabel",void 0),__decorate([property()],SelectPure.prototype,"totalRenderedChildOptions",void 0),__decorate([query("select")],SelectPure.prototype,"nativeSelect",void 0),__decorate([boundMethod],SelectPure.prototype,"close",null),__decorate([boundMethod],SelectPure.prototype,"getSingleNativeOptionHtml",null),__decorate([boundMethod],SelectPure.prototype,"getMultiSelectLabelHtml",null),__decorate([boundMethod],SelectPure.prototype,"getMultiSelectSelectedOptionHtml",null),__decorate([boundMethod],SelectPure.prototype,"initializeSelect",null),__decorate([boundMethod],SelectPure.prototype,"initializeSingleOption",null),__decorate([boundMethod],SelectPure.prototype,"removeEventListeners",null),__decorate([boundMethod],SelectPure.prototype,"appendHiddenInputToClosestForm",null),__decorate([boundMethod],SelectPure.prototype,"selectOptionByValue",null),SelectPure=__decorate([customElement("select-pure")],SelectPure);var default_1=Object.freeze({__proto__:null,get OptionPure(){return OptionPure},get SelectPure(){return SelectPure}});export{default_1 as default};
