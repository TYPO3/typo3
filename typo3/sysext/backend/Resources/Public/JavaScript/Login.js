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
define(["require", "exports", "jquery", "bootstrap", "TYPO3/CMS/Backend/jquery.clearable"], function (require, exports, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Login
     * JavaScript module for the backend login form
     * @exports TYPO3/CMS/Backend/Login
     *
     * Class and file name do not match as the class was renamed, but to keep overrides in place, the filename has to stay!
     */
    var BackendLogin = (function () {
        function BackendLogin() {
            var _this = this;
            /**
             * Hide all form fields and show a progress message and icon
             */
            this.showLoginProcess = function () {
                _this.showLoadingIndicator();
                $(_this.options.error).addClass('hidden');
                $(_this.options.errorNoCookies).addClass('hidden');
            };
            /**
             * Show the loading spinner in the submit button
             */
            this.showLoadingIndicator = function () {
                $(_this.options.submitButton).button('loading');
            };
            /**
             * Pass on to registered submit handler
             *
             * @param {Event} event
             */
            this.handleSubmit = function (event) {
                _this.showLoginProcess();
                if (typeof _this.options.submitHandler === 'function') {
                    _this.options.submitHandler(event);
                }
            };
            /**
             * Store the new selected Interface in a cookie to save it for future visits
             */
            this.interfaceSelectorChanged = function () {
                var now = new Date();
                // cookie expires in one year
                var expires = new Date(now.getTime() + 1000 * 60 * 60 * 24 * 365);
                document.cookie = 'typo3-login-interface='
                    + $(_this.options.interfaceField).val()
                    + '; expires=' + expires.toUTCString() + ';';
            };
            /**
             * Check if an interface was stored in a cookie and preselect it in the select box
             */
            this.checkForInterfaceCookie = function () {
                if ($(_this.options.interfaceField).length) {
                    var posStart = document.cookie.indexOf('typo3-login-interface=');
                    if (posStart !== -1) {
                        var selectedInterface = document.cookie.substr(posStart + 22);
                        selectedInterface = selectedInterface.substr(0, selectedInterface.indexOf(';'));
                        $(_this.options.interfaceField).val(selectedInterface);
                    }
                }
            };
            /**
             * Hides input fields and shows cookie warning
             */
            this.showCookieWarning = function () {
                $(_this.options.formFields).addClass('hidden');
                $(_this.options.errorNoCookies).removeClass('hidden');
            };
            /**
             * Hides cookie warning and shows input fields
             */
            this.hideCookieWarning = function () {
                $(_this.options.formFields).removeClass('hidden');
                $(_this.options.errorNoCookies).addClass('hidden');
            };
            /**
             * Checks browser's cookie support
             * see http://stackoverflow.com/questions/8112634/jquery-detecting-cookies-enabled
             */
            this.checkCookieSupport = function () {
                var cookieEnabled = navigator.cookieEnabled;
                // when cookieEnabled flag is present and false then cookies are disabled.
                if (cookieEnabled === false) {
                    _this.showCookieWarning();
                }
                else {
                    // try to set a test cookie if we can't see any cookies and we're using
                    // either a browser that doesn't support navigator.cookieEnabled
                    // or IE (which always returns true for navigator.cookieEnabled)
                    if (!document.cookie && (cookieEnabled === null || false)) {
                        document.cookie = 'typo3-login-cookiecheck=1';
                        if (!document.cookie) {
                            _this.showCookieWarning();
                        }
                        else {
                            // unset the cookie again
                            document.cookie = 'typo3-login-cookiecheck=; expires=' + new Date(0).toUTCString();
                        }
                    }
                }
            };
            /**
             * Registers listeners for the Login Interface
             */
            this.initializeEvents = function () {
                $(document).ajaxStart(_this.showLoadingIndicator);
                $(_this.options.loginForm).on('submit', _this.handleSubmit);
                // the Interface selector is not always present, so this check is needed
                if ($(_this.options.interfaceField).length > 0) {
                    $(document).on('change blur', _this.options.interfaceField, _this.interfaceSelectorChanged);
                }
                $('.t3js-clearable').clearable();
                // carousel news height transition
                $('.t3js-login-news-carousel').on('slide.bs.carousel', function (e) {
                    var nextH = $(e.relatedTarget).height();
                    var $element = $(e.target);
                    $element.find('div.active').parent().animate({ height: nextH }, 500);
                });
            };
            this.options = {
                error: '.t3js-login-error',
                errorNoCookies: '.t3js-login-error-nocookies',
                formFields: '.t3js-login-formfields',
                interfaceField: '.t3js-login-interface-field',
                loginForm: '#typo3-login-form',
                submitButton: '.t3js-login-submit',
                submitHandler: null,
                useridentField: '.t3js-login-userident-field',
            };
            this.checkCookieSupport();
            this.checkForInterfaceCookie();
            this.initializeEvents();
            // prevent opening the login form in the backend frameset
            if (top.location.href !== location.href) {
                top.location.href = location.href;
            }
        }
        return BackendLogin;
    }());
    return new BackendLogin();
});
