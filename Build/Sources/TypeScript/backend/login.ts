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

import 'bootstrap';
import '@typo3/backend/input/clearable';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import RegularEvent from '@typo3/core/event/regular-event';

interface PreflightResponse {
  capabilities: PreflightResponseCapabilities;
}

interface PreflightResponseCapabilities {
  cookie: boolean,
  referrer: boolean
}

/**
 * Module: @typo3/backend/login
 * JavaScript module for the backend login form
 * @exports @typo3/backend/login
 *
 * Class and file name do not match as the class was renamed, but to keep overrides in place, the filename has to stay!
 */
class BackendLogin {
  public options: any;
  public ready: boolean = true;

  constructor() {
    this.options = {
      error: '.t3js-login-error',
      errorNoCookies: '.t3js-login-error-nocookies',
      errorNoReferrer: '.t3js-login-error-noreferrer',
      formFields: '.t3js-login-formfields',
      loginForm: '#typo3-login-form',
      loginUrlLink: 't3js-login-url',
      submitButton: '.t3js-login-submit',
      submitHandler: null,
      useridentField: '.t3js-login-userident-field',
    };

    this.checkLoginRefresh();
    this.checkCookieSupport();
    this.checkDocumentReferrerSupport();
    this.initializeEvents();

    // prevent opening the login form in the backend frameset
    if (top.location.href !== location.href) {
      this.ready = false;
      top.location.href = location.href;
    }
    if (this.ready) {
      document.body.setAttribute('data-typo3-login-ready', 'true');
    }
  }

  /**
   * Hide all form fields and show a progress message and icon
   */
  private showLoginProcess(): void {
    this.showLoadingIndicator();
    document.querySelector(this.options.error)?.classList.add('hidden');
    document.querySelector(this.options.errorNoCookies)?.classList.add('hidden');
  }

  /**
   * Show the loading spinner in the submit button
   */
  private showLoadingIndicator(): void {
    const button = document.querySelector(this.options.submitButton) as HTMLButtonElement;
    button.innerHTML = button.dataset.loadingText;
  }

  /**
   * Pass on to registered submit handler
   *
   * @param {Event} event
   */
  private handleSubmit(event: Event): void {
    this.showLoginProcess();

    if (typeof this.options.submitHandler === 'function') {
      this.options.submitHandler(event);
    }
  }

  private checkDocumentReferrerSupport(): void {
    const loginUrlLink = document.getElementById(this.options.loginUrlLink) as HTMLAnchorElement;
    // skip referrer check if explicitly disabled
    if (loginUrlLink !== null
      && typeof loginUrlLink.dataset.referrerCheckEnabled === 'undefined'
      && loginUrlLink.dataset.referrerCheckEnabled !== '1'
    ) {
      return;
    }
    if (typeof TYPO3.settings === 'undefined' || typeof TYPO3.settings.ajaxUrls === 'undefined') {
      return;
    }
    new AjaxRequest(TYPO3.settings.ajaxUrls.login_preflight).get()
      .then(async (response: AjaxResponse) => {
        const result = await response.resolve('application/json') as PreflightResponse;
        if (result.capabilities.referrer !== true) {
          document.querySelectorAll(this.options.errorNoReferrer)
            .forEach((element: HTMLElement): void => element.classList.remove('hidden'));
        }
      });
  }

  /**
   * Hides input fields and shows cookie warning
   */
  private showCookieWarning(): void {
    document.querySelector(this.options.formFields)?.classList.add('hidden');
    document.querySelector(this.options.errorNoCookies)?.classList.remove('hidden');
  }

  /**
   * Hides cookie warning and shows input fields
   */
  private hideCookieWarning(): void {
    document.querySelector(this.options.formFields)?.classList.remove('hidden');
    document.querySelector(this.options.errorNoCookies)?.classList.add('hidden');
  }

  private checkLoginRefresh(): void {
    const loginRefresh = document.querySelector(this.options.loginForm + ' input[name="loginRefresh"]');
    if (loginRefresh instanceof HTMLInputElement && loginRefresh.value) {
      if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.LoginRefresh) {
        window.opener.TYPO3.LoginRefresh.startTask();
        window.close();
      }
    }
  }

  /**
   * Checks browser's cookie support
   * see http://stackoverflow.com/questions/8112634/jquery-detecting-cookies-enabled
   */
  private checkCookieSupport(): void {
    const cookieEnabled = navigator.cookieEnabled;

    // when cookieEnabled flag is present and false then cookies are disabled.
    if (cookieEnabled === false) {
      this.showCookieWarning();
    } else {
      // try to set a test cookie if we can't see any cookies and we're using
      // either a browser that doesn't support navigator.cookieEnabled
      // or IE (which always returns true for navigator.cookieEnabled)
      if (!document.cookie && (cookieEnabled === null || /*@cc_on!@*/false)) {
        document.cookie = 'typo3-login-cookiecheck=1';

        if (!document.cookie) {
          this.showCookieWarning();
        } else {
          // unset the cookie again
          document.cookie = 'typo3-login-cookiecheck=; expires=' + new Date(0).toUTCString();
        }
      }
    }
  }

  /**
   * Registers listeners for the Login Interface
   */
  private initializeEvents(): void {
    new RegularEvent('submit', this.handleSubmit.bind(this)).bindTo(document.querySelector(this.options.loginForm));

    (<NodeListOf<HTMLInputElement>>document.querySelectorAll('.t3js-clearable')).forEach(
      (clearableField: HTMLInputElement) => clearableField.clearable(),
    );

    this.registerNewsCarouselEvents();
  }

  private registerNewsCarouselEvents(): void {
    const newsCarouselInner: HTMLElement = document.querySelector('.t3js-login-news-carousel .carousel-inner');
    if (newsCarouselInner !== null) {
      const newsCarousel = newsCarouselInner.closest('.t3js-login-news-carousel');

      this.calculateScrollbarWidth(newsCarouselInner);

      new RegularEvent('scroll', (e: Event): void => {
        const el = e.currentTarget as HTMLElement;
        this.setOverflowClasses(el);
      }).bindTo(newsCarouselInner);

      new RegularEvent('slid.bs.carousel', (): void => {
        newsCarouselInner.scrollTop = 0;
        this.setOverflowClasses(newsCarouselInner);
        this.calculateScrollbarWidth(newsCarouselInner);
      }).bindTo(newsCarousel);

      this.setOverflowClasses(newsCarouselInner);
    }
  }

  private calculateScrollbarWidth(newsCarouselInner: HTMLElement): void {
    const scrollbarWidth = newsCarouselInner.offsetWidth - newsCarouselInner.clientWidth;
    newsCarouselInner.setAttribute('style', '--scrollbar-width: ' + scrollbarWidth + 'px');
  }

  private setOverflowClasses(el: HTMLElement): void {
    enum OverflowClasses {
      top = 'carousel-inner--overflowing-top',
      bottom = 'carousel-inner--overflowing-bottom',
    }
    const isScrollable = el.scrollHeight > el.clientHeight;
    if (!isScrollable) {
      el.classList.remove(OverflowClasses.bottom, OverflowClasses.top);
      return;
    }

    const isScrolledToBottom = el.scrollHeight <= el.clientHeight + el.scrollTop;
    const isScrolledToTop = el.scrollTop === 0;
    el.classList.toggle(OverflowClasses.bottom, !isScrolledToBottom);
    el.classList.toggle(OverflowClasses.top, !isScrolledToTop);
  }
}

export default new BackendLogin();
