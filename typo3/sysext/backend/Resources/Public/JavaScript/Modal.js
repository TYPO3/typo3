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

/**
 * Module: TYPO3/CMS/Backend/Modal
 * API for modal windows powered by Twitter Bootstrap.
 */
define(['jquery',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/Icons',
  'bootstrap'
], function($, Severity, Icons) {
  'use strict';

  try {
    // fetch from parent
    if (parent && parent.window.TYPO3 && parent.window.TYPO3.Modal) {
      // we need to trigger the event capturing again, in order to make sure this works inside iframes
      parent.window.TYPO3.Modal.initializeMarkupTrigger(document);
      return parent.window.TYPO3.Modal;
    }

    // fetch object from outer frame
    if (top && top.TYPO3.Modal) {
      // we need to trigger the event capturing again, in order to make sure this works inside iframes
      top.TYPO3.Modal.initializeMarkupTrigger(document);
      return top.TYPO3.Modal;
    }
  } catch (e) {
    // This only happens if the opener, parent or top is some other url (eg a local file)
    // which loaded the current window. Then the browser's cross domain policy jumps in
    // and raises an exception.
    // For this case we are safe and we can create our global object below.
  }

  /**
   * The main object of the modal API
   *
   * @type {{instances: Array, currentModal: null, template: (*), identifiers: {modal: string, content: string, title: string, close: string, body: string, footer: string, iframe: string, iconPlaceholder: string}, sizes: {small: string, default: string, large: string, full: string}, styles: {default: string, light: string, dark: string}, types: {default: string, ajax: string, iframe: string}, defaultConfiguration: {type: string, title: string, content: string, severity: number, buttons: Array, style: string, size: string, additionalCssClasses: Array, callback: Modal.defaultConfiguration.callback, ajaxCallback: Modal.defaultConfiguration.ajaxCallback, ajaxTarget: null}}}
   * @exports TYPO3/CMS/Backend/Modal
   */
  var Modal = {
    instances: [],
    currentModal: null,
    template: $(
      '<div class="t3js-modal modal fade">' +
      '<div class="modal-dialog">' +
      '<div class="t3js-modal-content modal-content">' +
      '<div class="modal-header">' +
      '<button class="t3js-modal-close close">' +
      '<span aria-hidden="true">' +
      '<span class="t3js-modal-icon-placeholder" data-icon="actions-close"></span>' +
      '</span>' +
      '<span class="sr-only"></span>' +
      '</button>' +
      '<h4 class="t3js-modal-title modal-title"></h4>' +
      '</div>' +
      '<div class="t3js-modal-body modal-body"></div>' +
      '<div class="t3js-modal-footer modal-footer"></div>' +
      '</div>' +
      '</div>' +
      '</div>'
    ),
    identifiers: {
      modal: '.t3js-modal',
      content: '.t3js-modal-content',
      title: '.t3js-modal-title',
      close: '.t3js-modal-close',
      body: '.t3js-modal-body',
      footer: '.t3js-modal-footer',
      iframe: '.t3js-modal-iframe',
      iconPlaceholder: '.t3js-modal-icon-placeholder'
    },
    sizes: {
      small: 'small',
      default: 'default',
      large: 'large',
      full: 'full'
    },
    styles: {
      default: 'light',
      light: 'light',
      dark: 'dark'
    },
    types: {
      default: 'default',
      ajax: 'ajax',
      iframe: 'iframe'
    },
    defaultConfiguration: {
      type: 'default',
      title: 'Information',
      content: 'No content provided, please check your <code>Modal</code> configuration.',
      severity: Severity.notice,
      buttons: [],
      style: 'default',
      size: 'default',
      additionalCssClasses: [],
      callback: function() {
      },
      ajaxCallback: function() {
      },
      ajaxTarget: null
    }
  };

  /**
   * Get the correct css class for given severity
   *
   * @param {int} severity use constants from Severity.*
   * @returns {String}
   * @private
   * @deprecated
   */
  Modal.getSeverityClass = function(severity) {
    if (console) {
      console.warn('Modal.getSeverityClass() is deprecated and will be removed with TYPO3 v9, please use Severity.getCssClass()');
    }
    return Severity.getCssClass(severity);
  };

  /**
   * Shows a confirmation dialog
   * Events:
   * - button.clicked
   * - confirm.button.cancel
   * - confirm.button.ok
   *
   * @param {String} title the title for the confirm modal
   * @param {*} content the content for the conform modal, e.g. the main question
   * @param {int} [severity=Severity.warning] severity default Severity.warning
   * @param {array} [buttons] an array with buttons, default no buttons
   * @param {array} [additionalCssClasses=''] additional css classes to add to the modal
   */
  Modal.confirm = function(title, content, severity, buttons, additionalCssClasses) {
    severity = typeof severity !== 'undefined' ? severity : Severity.warning;

    return Modal.advanced(
      {
        title: title,
        content: content,
        severity: severity,
        buttons: buttons || [
          {
            text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel'
          },
          {
            text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
            btnClass: 'btn-' + Severity.getCssClass(severity),
            name: 'ok'
          }
        ],
        additionalCssClasses: additionalCssClasses || [],
        callback: function(currentModal) {
          currentModal.on('button.clicked', function(e) {
            if (e.target.name === 'cancel') {
              $(this).trigger('confirm.button.cancel');
            } else if (e.target.name === 'ok') {
              $(this).trigger('confirm.button.ok');
            }
          });
        }
      }
    );
  };

  /**
   * load URL with AJAX, append the content to the modal-body
   * and trigger the callback
   *
   * @param {String} title
   * @param {int} severity
   * @param {array} buttons
   * @param {String} url
   * @param {function} callback
   * @param {String} target
   */
  Modal.loadUrl = function(title, severity, buttons, url, callback, target) {
    return Modal.advanced({
      type: Modal.types.ajax,
      title: title,
      content: url,
      severity: typeof severity !== 'undefined' ? severity : Severity.info,
      buttons: buttons,
      ajaxCallback: callback,
      ajaxTarget: target
    });
  };

  /**
   * Shows a dialog
   *
   * @param {String} title the title for the modal
   * @param {*} content the content for the modal, e.g. the main question
   * @param {int} severity default Severity.info
   * @param {array} buttons an array with buttons, default no buttons
   * @param {array} additionalCssClasses additional css classes to add to the modal
   */
  Modal.show = function(title, content, severity, buttons, additionalCssClasses) {
    return Modal.advanced({
      type: Modal.types.default,
      title: title,
      content: content,
      severity: typeof severity !== 'undefined' ? severity : Severity.info,
      buttons: buttons,
      additionalCssClasses: additionalCssClasses
    });
  };

  /**
   * Loads modal by configuration
   *
   * @param {object} configuration configuration for the modal
   */
  Modal.advanced = function(configuration) {
    if (typeof configuration !== 'object') {
      configuration = {};
    }

    // Validation of configuration
    configuration.type = typeof configuration.type === 'string' && configuration.type in Modal.types ? configuration.type : Modal.defaultConfiguration.type;
    configuration.title = typeof configuration.title === 'string' ? configuration.title : Modal.defaultConfiguration.title;
    configuration.content = typeof configuration.content === 'string' || typeof configuration.content === 'object' ? configuration.content : Modal.defaultConfiguration.content;
    configuration.severity = typeof configuration.severity !== 'undefined' ? configuration.severity : Modal.defaultConfiguration.severity;
    configuration.buttons = configuration.buttons || Modal.defaultConfiguration.buttons;
    configuration.size = typeof configuration.size === 'string' && configuration.size in Modal.sizes ? configuration.size : Modal.defaultConfiguration.size;
    configuration.style = typeof configuration.style === 'string' && configuration.style in Modal.styles ? configuration.style : Modal.defaultConfiguration.style;
    configuration.additionalCssClasses = configuration.additionalCssClasses || Modal.defaultConfiguration.additionalCssClasses;
    configuration.callback = typeof configuration.callback === 'function' ? configuration.callback : Modal.defaultConfiguration.callback;
    configuration.ajaxCallback = typeof configuration.ajaxCallback === 'function' ? configuration.ajaxCallback : Modal.defaultConfiguration.ajaxCallback;
    configuration.ajaxTarget = typeof configuration.ajaxTarget === 'string' ? configuration.ajaxTarget : Modal.defaultConfiguration.ajaxTarget;

    return Modal._generate(
      configuration.type,
      configuration.title,
      configuration.content,
      configuration.severity,
      configuration.buttons,
      configuration.style,
      configuration.size,
      configuration.additionalCssClasses,
      configuration.callback,
      configuration.ajaxCallback,
      configuration.ajaxTarget
    );
  };

  /**
   * Generate the modal window
   * Events:
   * - button.clicked
   *
   * @param {String} type the type of the modal
   * @param {String} title the title for the modal
   * @param {*} content the content for the modal, e.g. the main question
   * @param {int} severity default Severity.info
   * @param {array} buttons an array with buttons, default no buttons
   * @param {String} style the style of the modal window
   * @param {String} size the size of the modal window
   * @param {array} additionalCssClasses additional css classes to add to the modal
   * @param {function} callback
   * @param {function} ajaxCallback
   * @param {String} ajaxTarget
   * @private
   */
  Modal._generate = function(type, title, content, severity, buttons, style, size, additionalCssClasses, callback, ajaxCallback, ajaxTarget) {
    var currentModal = Modal.template.clone();
    if (additionalCssClasses.length) {
      for (var i = 0; i < additionalCssClasses.length; i++) {
        currentModal.addClass(additionalCssClasses[i]);
      }
    }
    currentModal.addClass('modal-type-' + Modal.types[type]);
    currentModal.addClass('modal-severity-' + Severity.getCssClass(severity));
    currentModal.addClass('modal-style-' + Modal.styles[style]);
    currentModal.addClass('modal-size-' + Modal.sizes[size]);
    currentModal.attr('tabindex', '-1');
    currentModal.find(Modal.identifiers.title).text(title);
    currentModal.find(Modal.identifiers.close).on('click', function() {
      currentModal.modal('hide');
    });

    // Add content
    if (type === 'ajax') {
      Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done(function(icon) {
        currentModal.find(Modal.identifiers.body).html('<div class="modal-loading">' + icon + '</div>');
        $.get(content, function(response) {
          Modal.currentModal.find(ajaxTarget ? ajaxTarget : Modal.identifiers.body).empty().append(response);
          if (ajaxCallback) {
            ajaxCallback();
          }
          Modal.currentModal.trigger('modal-loaded');
        }, 'html');
      });
    } else if (type === 'iframe') {
      currentModal.find(Modal.identifiers.body).append(
        $('<iframe />', {src: content, 'class': 'modal-iframe t3js-modal-iframe'})
      );
      currentModal.find(Modal.identifiers.iframe).on('load', function() {
        currentModal.find(Modal.identifiers.title).text(
          currentModal.find(Modal.identifiers.iframe).get(0).contentDocument.title
        );
      });
    } else {
      if (typeof content === 'object') {
        currentModal.find(Modal.identifiers.body).append(content);
      } else {
        // we need html, check if we have to wrap content in <p>
        if (!/^<[a-z][\s\S]*>/i.test(content)) {
          content = $('<p />').html(content);
        }
        currentModal.find(Modal.identifiers.body).html(content);
      }
    }

    // Add buttons
    if (buttons.length > 0) {
      for (i = 0; i < buttons.length; i++) {
        var button = buttons[i];
        var $button = $('<button />', {class: 'btn'});
        $button.html('<span>' + button.text + '</span>');
        if (button.active) {
          $button.addClass('t3js-active');
        }
        if (button.btnClass) {
          $button.addClass(button.btnClass);
        }
        if (button.name) {
          $button.attr('name', button.name);
        }
        if (button.trigger) {
          $button.on('click', button.trigger);
        }
        if (button.dataAttributes) {
          if (Object.keys(button.dataAttributes).length > 0) {
            Object.keys(button.dataAttributes).map(function(key, index) {
              $button.attr('data-' + key, button.dataAttributes[key]);
            });
          }
        }
        if (button.icon) {
          $button.prepend('<span class="t3js-modal-icon-placeholder" data-icon="' + button.icon + '"></span>');
        }
        currentModal.find(Modal.identifiers.footer).append($button);
      }
      currentModal
        .find(Modal.identifiers.footer).find('button')
        .on('click', function() {
          $(this).trigger('button.clicked');
        });

    } else {
      currentModal.find(Modal.identifiers.footer).remove();
    }

    currentModal.on('shown.bs.modal', function() {
      // focus the button which was configured as active button
      $(this).find(Modal.identifiers.footer).find('.t3js-active').first().focus();
      // Get Icons
      $(this).find(Modal.identifiers.iconPlaceholder).each(function() {
        Icons.getIcon($(this).data('icon'), Icons.sizes.small, null, null, Icons.markupIdentifiers.inline).done(function(icon) {
          Modal.currentModal.find(Modal.identifiers.iconPlaceholder + '[data-icon=' + $(icon).data('identifier') + ']').replaceWith(icon);
        });
      });
    });

    // Remove modal from Modal.instances when hidden
    currentModal.on('hidden.bs.modal', function() {
      if (Modal.instances.length > 0) {
        var lastIndex = Modal.instances.length - 1;
        Modal.instances.splice(lastIndex, 1);
        Modal.currentModal = Modal.instances[lastIndex - 1];
      }
      currentModal.trigger('modal-destroyed');
      $(this).remove();
      // Keep class modal-open on body tag as long as open modals exist
      if (Modal.instances.length > 0) {
        $('body').addClass('modal-open');
      }
    });

    // When modal is opened/shown add it to Modal.instances and make it Modal.currentModal
    currentModal.on('show.bs.modal', function() {
      Modal.currentModal = $(this);
      Modal.instances.push(Modal.currentModal);
    });
    currentModal.on('modal-dismiss', function() {
      // Hide modal, the bs.modal events will clean up Modal.instances
      $(this).modal('hide');
    });

    if (callback) {
      callback(currentModal);
    }

    return currentModal.modal();
  };

  /**
   * Close the current open modal
   */
  Modal.dismiss = function() {
    if (Modal.currentModal) {
      Modal.currentModal.modal('hide');
    }
  };

  /**
   * Center the modal windows
   */
  Modal.center = function() {
    if (console) {
      console.warn('Modal.center() is deprecated and will be removed with TYPO3 v9, please remove the call. Modals are now automatically centered.');
    }
  };

  /**
   * Initialize markup with data attributes
   *
   * @param {object} theDocument
   */
  Modal.initializeMarkupTrigger = function(theDocument) {
    $(theDocument).on('click', '.t3js-modal-trigger', function(evt) {
      evt.preventDefault();
      var $element = $(this);
      var url = $element.data('url') || null;
      var content = $element.data('content') || 'Are you sure?';
      var severity = typeof Severity[$element.data('severity')] !== 'undefined' ? Severity[$element.data('severity')] : Severity.info;
      if (url !== null) {
        var separator = (url.indexOf('?') > -1) ? '&' : '?';
        var params = $.param({data: $element.data()});
        url = url + separator + params;
      }
      Modal.advanced({
        type: url !== null ? Modal.types.ajax : Modal.types.default,
        title: $element.data('title') || 'Alert',
        content: url !== null ? url : content,
        severity: severity,
        buttons: [
          {
            text: $element.data('button-close-text') || 'Close',
            active: true,
            btnClass: 'btn-default',
            trigger: function() {
              Modal.currentModal.trigger('modal-dismiss');
            }
          },
          {
            text: $element.data('button-ok-text') || 'OK',
            btnClass: 'btn-' + Severity.getCssClass(severity),
            trigger: function() {
              Modal.currentModal.trigger('modal-dismiss');
              evt.target.ownerDocument.location.href = $element.data('href') || $element.attr('href');
            }
          }
        ]
      });
    });
  };

  /**
   * Custom event, fired if modal gets closed
   */
  $(document).on('modal-dismiss', Modal.dismiss);

  Modal.initializeMarkupTrigger(document);

  // expose as global object
  TYPO3.Modal = Modal;

  return Modal;
});
