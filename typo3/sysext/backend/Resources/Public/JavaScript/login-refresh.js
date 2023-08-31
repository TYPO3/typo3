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
var MarkupIdentifiers,__decorate=function(e,t,o,s){var i,r=arguments.length,n=r<3?t:null===s?s=Object.getOwnPropertyDescriptor(t,o):s;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)n=Reflect.decorate(e,t,o,s);else for(var a=e.length-1;a>=0;a--)(i=e[a])&&(n=(r<3?i(n):r>3?i(t,o,n):i(t,o))||n);return r>3&&n&&Object.defineProperty(t,o,n),n};import{html,LitElement}from"lit";import{customElement,state}from"lit/decorators.js";import Modal,{Styles,Sizes}from"@typo3/backend/modal.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Notification from"@typo3/backend/notification.js";!function(e){e.loginrefresh="t3js-modal-loginrefresh",e.lockedModal="t3js-modal-backendlocked",e.loginFormModal="t3js-modal-backendloginform"}(MarkupIdentifiers||(MarkupIdentifiers={}));class LoginRefresh{constructor(){this.intervalTime=60,this.intervalId=null,this.backendIsLocked=!1,this.timeoutModal=null,this.backendLockedModal=null,this.loginForm=null,this.requestTokenUrl="",this.loginFramesetUrl="",this.logoutUrl="",this.submitForm=async(e,t)=>{e.preventDefault();const o=await new AjaxRequest(this.requestTokenUrl).post({}),s=await o.resolve("application/json");if(!s.headerName||!s.requestToken)return;const i=t.querySelector("input[name=p_field]"),r=t.querySelector("input[name=userident]"),n=i.value;if(""===n&&""===r.value)return Notification.error(TYPO3.lang["mess.refresh_login_failed"],TYPO3.lang["mess.refresh_login_emptyPassword"]),void i.focus();n&&(r.value=n,i.value="");const a={login_status:"login"};for(const[e,o]of new FormData(t))a[e]=o.toString();const l=new Headers;l.set(s.headerName,s.requestToken);const d=await new AjaxRequest(t.getAttribute("action")).post(a,{headers:l});(await d.resolve()).login.success?this.hideLoginForm():(Notification.error(TYPO3.lang["mess.refresh_login_failed"],TYPO3.lang["mess.refresh_login_failed_message"]),i.focus())},this.checkActiveSession=async()=>{try{const e=await new AjaxRequest(TYPO3.settings.ajaxUrls.login_timedout).get(),t=await e.resolve();t.login.locked?this.backendIsLocked||(this.backendIsLocked=!0,this.showBackendLockedModal()):this.backendIsLocked&&(this.backendIsLocked=!1,this.hideBackendLockedModal()),this.backendIsLocked||(t.login.timed_out||t.login.will_time_out)&&(t.login.timed_out?this.showLoginForm():this.showTimeoutModal())}catch{this.backendIsLocked=!0,this.showBackendLockedModal()}}}initialize(e){"object"==typeof e&&this.applyOptions(e),this.startTask()}startTask(){if(null!==this.intervalId)return;const e=1e3*this.intervalTime;this.intervalId=setInterval(this.checkActiveSession,e)}stopTask(){clearInterval(this.intervalId),this.intervalId=null}setIntervalTime(e){this.intervalTime=Math.min(e,86400)}setLogoutUrl(e){this.logoutUrl=e}setLoginFramesetUrl(e){this.loginFramesetUrl=e}showTimeoutModal(){this.timeoutModal=this.createTimeoutModal(),this.timeoutModal.addEventListener("typo3-modal-hidden",(()=>this.timeoutModal=null)),this.timeoutModal.addEventListener("show-login-form",(()=>{this.timeoutModal.hideModal(),this.showLoginForm()}))}hideTimeoutModal(){this.timeoutModal?.hideModal()}showBackendLockedModal(){this.backendLockedModal||(this.backendLockedModal=this.createBackendLockedModal(),this.backendLockedModal.addEventListener("typo3-modal-hidden",(()=>this.backendLockedModal=null)))}hideBackendLockedModal(){this.backendLockedModal?.hideModal()}showLoginForm(){this.loginForm||new AjaxRequest(TYPO3.settings.ajaxUrls.logout).get().then((()=>{TYPO3.configuration.showRefreshLoginPopup?this.showLoginPopup():(this.loginForm=this.createLoginFormModal(),this.loginForm.addEventListener("typo3-modal-hidden",(()=>this.loginForm=null)))}))}showLoginPopup(){const e=window.open(this.loginFramesetUrl,"relogin_"+Math.random().toString(16).slice(2),"height=450,width=700,status=0,menubar=0,location=1");e&&e.focus()}hideLoginForm(){this.loginForm?.hideModal()}createBackendLockedModal(){return Modal.advanced({additionalCssClasses:[MarkupIdentifiers.lockedModal],title:TYPO3.lang["mess.please_wait"],severity:SeverityEnum.notice,style:Styles.light,size:Sizes.small,staticBackdrop:!0,hideCloseButton:!0,content:html`
        <p>${TYPO3.lang["mess.be_locked"]}</p>
      `})}createTimeoutModal(){const e=Modal.advanced({additionalCssClasses:[MarkupIdentifiers.loginrefresh],title:TYPO3.lang["mess.login_about_to_expire_title"],severity:SeverityEnum.notice,style:Styles.light,size:Sizes.small,staticBackdrop:!0,hideCloseButton:!0,buttons:[{text:TYPO3.lang["mess.refresh_login_logout_button"],active:!1,btnClass:"btn-default",name:"logout",trigger:()=>top.location.href=this.logoutUrl},{text:TYPO3.lang["mess.refresh_login_refresh_button"],active:!0,btnClass:"btn-primary",name:"refreshSession",trigger:async(e,t)=>{const o=await new AjaxRequest(TYPO3.settings.ajaxUrls.login_refresh).get(),s=await o.resolve();t.hideModal(),s.refresh.success||t.dispatchEvent(new Event("show-login-form"))}}],content:html`
        <p>${TYPO3.lang["mess.login_about_to_expire"]}</p>
        <typo3-login-refresh-progress-bar
          @progress-bar-overdue=${()=>e.dispatchEvent(new Event("show-login-form"))}
          ></typo3-login-refresh-progress-bar>
      `});return e.addEventListener("typo3-modal-hidden",(()=>{this.startTask()})),e.addEventListener("typo3-modal-shown",(()=>{this.stopTask()})),e}createLoginFormModal(){const e=String(TYPO3.lang["mess.refresh_login_title"]).replace("%s",TYPO3.configuration.username),t=Modal.advanced({additionalCssClasses:[MarkupIdentifiers.loginFormModal],title:e,severity:SeverityEnum.notice,style:Styles.light,size:Sizes.small,staticBackdrop:!0,hideCloseButton:!0,buttons:[{text:TYPO3.lang["mess.refresh_exit_button"],active:!1,btnClass:"btn-default",name:"logout",trigger:()=>top.location.href=this.logoutUrl},{text:TYPO3.lang["mess.refresh_login_button"],active:!1,btnClass:"btn-primary",name:"refreshSession",trigger:async(e,t)=>{t.querySelector("form").requestSubmit();const o=await new AjaxRequest(TYPO3.settings.ajaxUrls.login_refresh).get(),s=await o.resolve();t.hideModal(),s.refresh.success||t.dispatchEvent(new Event("show-login-form"))}}],content:html`
        <p>${TYPO3.lang["mess.login_expired"]}</p>
        <form
            id="beLoginRefresh"
            method="POST"
            action=${TYPO3.settings.ajaxUrls.login}
            @submit=${e=>this.submitForm(e,e.currentTarget)}>
          <div>
            <input
                type="text"
                name="username"
                class="d-none"
                autocomplete="username"
                .value=${TYPO3.configuration.username}>
            <input
                type="hidden"
                name="userident"
                id="t3-loginrefresh-userident">
          </div>
          <div class="form-group">
            <input
                type="password"
                name="p_field"
                autofocus
                class="form-control"
                autocomplete="current-password"
                placeholder=${TYPO3.lang["mess.refresh_login_password"]}>
          </div>
        </form>
      `});return t.addEventListener("typo3-modal-hidden",(()=>{this.startTask()})),t.addEventListener("typo3-modal-shown",(()=>{this.stopTask()})),t}applyOptions(e){void 0!==e.intervalTime&&this.setIntervalTime(e.intervalTime),void 0!==e.loginFramesetUrl&&this.setLoginFramesetUrl(e.loginFramesetUrl),void 0!==e.logoutUrl&&this.setLogoutUrl(e.logoutUrl),void 0!==e.requestTokenUrl&&(this.requestTokenUrl=e.requestTokenUrl)}}let loginRefreshObject,ProgressBarElement=class extends LitElement{constructor(){super(...arguments),this.current=0,this.max=100,this.advanceProgressBar=()=>{this.current++;this.current>=this.max&&this.dispatchEvent(new Event("progress-bar-overdue"))}}connectedCallback(){super.connectedCallback(),this.intervalId&&clearInterval(this.intervalId),this.intervalId=setInterval(this.advanceProgressBar,300)}disconnectedCallback(){super.disconnectedCallback(),this.intervalId&&(clearInterval(this.intervalId),this.intervalId=null)}createRenderRoot(){return this}render(){return html`
      <div class="progress">
        <div
            class="progress-bar progress-bar-warning progress-bar-striped progress-bar-animated"
            role="progressbar"
            aria-valuemin="0"
            aria-valuenow=${this.current}
            aria-valuemax="100"
            style="width: ${this.current}%">
          <span class="visually-hidden">${this.current}%</span>
        </div>
      </div>
    `}};__decorate([state()],ProgressBarElement.prototype,"current",void 0),ProgressBarElement=__decorate([customElement("typo3-login-refresh-progress-bar")],ProgressBarElement);export{ProgressBarElement};try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.LoginRefresh&&(loginRefreshObject=window.opener.TYPO3.LoginRefresh),parent&&parent.window.TYPO3&&parent.window.TYPO3.LoginRefresh&&(loginRefreshObject=parent.window.TYPO3.LoginRefresh),top&&top.TYPO3&&top.TYPO3.LoginRefresh&&(loginRefreshObject=top.TYPO3.LoginRefresh)}catch{}loginRefreshObject||(loginRefreshObject=new LoginRefresh,"undefined"!=typeof TYPO3&&(TYPO3.LoginRefresh=loginRefreshObject));export default loginRefreshObject;