(function() {
  'use strict';

  /**
   * Prevent calling the function multiple times
   */
  var documentReadyFunctionCalled = false;

  var rsaEncryption = function(form) {

    /**
     * Submitted form element
     */
    this.form = form;

    /**
     * Store found fields in an array
     */
    this.fields = [];

    /**
     * XMLHttpRequest
     */
    this.xhr = null;

    /**
     * Remember if we fetched the RSA key already
     */
    this.fetchedRsaKey = false;

    /**
     * Fetches a new public key by Ajax and encrypts the password for transmission
     */
    this.handleFormSubmitRequest = function(event) {
      if (event.cancelBubble) {
        return;
      }

      var rsaEncryption = this.rsaEncryption || event.srcElement.rsaEncryption;
      if (!rsaEncryption) {
        return;
      }
      if (rsaEncryption.fields.length && !rsaEncryption.fetchedRsaKey) {
        rsaEncryption.fetchedRsaKey = true;
        rsaEncryption.ajaxCall(
          TYPO3RsaEncryptionPublicKeyUrl, // defined in PHP
          rsaEncryption,
          function(response) {
            var keyData = null;

            try {
              keyData = JSON.parse(response.responseText);
            } catch (e) {
              // Nothing to do here, error will be handled by callback
            }

            rsaEncryption.handlePublicKeyResponse(keyData, rsaEncryption);
          }
        );

        if (event.preventDefault) {
          event.preventDefault();
        } else if (window.event) {
          window.event.returnValue = false;
        }
      }
    };

    this.ajaxCall = function(url, rsaEncryption, callback) {
      // Abort previous request, only last request/generated key pair can be used
      if (rsaEncryption.xhr) {
        rsaEncryption.xhr.abort();
      }

      if (typeof XMLHttpRequest !== 'undefined') {
        rsaEncryption.xhr = new XMLHttpRequest();
      } else {
        var versions = [
          'MSXML2.XmlHttp.5.0',
          'MSXML2.XmlHttp.4.0',
          'MSXML2.XmlHttp.3.0',
          'MSXML2.XmlHttp.2.0',
          'Microsoft.XmlHttp'
        ];
        for (var i = 0, count = versions.length; i < count; i++) {
          try {
            rsaEncryption.xhr = new ActiveXObject(versions[i]);
            break;
          } catch (e) {
          }
        }
      }

      rsaEncryption.xhr.onreadystatechange = function() {
        // Only process requests that are ready and have a status (not aborted)
        if (rsaEncryption.xhr.readyState === 4 && rsaEncryption.xhr.status > 0) {
          callback(rsaEncryption.xhr);
        }
      };

      rsaEncryption.xhr.open('GET', url, true);
      rsaEncryption.xhr.setRequestHeader('Content-Type', 'application/json');
      rsaEncryption.xhr.send('');
    };

    this.handlePublicKeyResponse = function(keyData, rsaEncryption) {
      if (!keyData) {
        alert('No public key could be generated. Please inform your TYPO3 administrator to check the OpenSSL settings.');
        return false;
      }

      var rsa = new RSAKey();
      rsa.setPublic(keyData.publicKeyModulus, keyData.exponent);

      for (var i = rsaEncryption.fields.length; i--;) {
        var field = rsaEncryption.fields[i];
        var encryptedValue = rsa.encrypt(field.value);
        // Replace value with encrypted value
        field.value = 'rsa:' + hex2b64(encryptedValue);
      }

      // Submit the form again but now with encrypted values
      var form = document.createElement('form');
      if (form.submit.call) {
        form.submit.call(rsaEncryption.form);
      } else {
        for (var j = rsaEncryption.form.elements.length; j--;) {
          var submitField = rsaEncryption.form.elements[j];
          if (submitField.nodeName.toLowerCase() === 'input' && submitField.type === "submit") {
            submitField.click();
          }
        }
      }
    };
  };

  /**
   * Bind submit handler to all forms with input:data-rsa-encryption fields
   */
  function ready() {
    if (documentReadyFunctionCalled) {
      return;
    }

    documentReadyFunctionCalled = true;
    rng_seed_time();
    for (var i = document.forms.length; i--;) {
      var form = document.forms[i];
      for (var j = form.elements.length; j--;) {
        var field = form.elements[j];
        if (field.nodeName.toLowerCase() === 'input') {
          var dataAttribute = field.getAttribute('data-rsa-encryption');
          if (dataAttribute || dataAttribute === '' && field.outerHTML.match(/ data-rsa-encryption=""/)) {
            if (!form.rsaEncryption) {
              form.rsaEncryption = new rsaEncryption(form);
              if (form.addEventListener) {
                form.addEventListener('submit', form.rsaEncryption.handleFormSubmitRequest, false);
              } else if (form.attachEvent) {
                form.attachEvent('onsubmit', form.rsaEncryption.handleFormSubmitRequest);
              }
            }
            form.rsaEncryption.fields.push(field);
          }
        }
      }
    }
  }

  // If the document is ready, callback function can be called
  if (document.readyState === 'complete') {
    setTimeout(ready, 1);
  } else {
    // Install event handlers for older browsers
    if (document.addEventListener) {
      // First register DOMContentLoaded event
      document.addEventListener('DOMContentLoaded', ready, false);
      // Register backup on windows object
      window.addEventListener('load', ready, false);
    } else {
      // Fallback for Internet Explorer
      document.attachEvent('onreadystatechange', function() {
        if (document.readyState === 'complete') {
          ready();
        }
      });
      window.attachEvent('onload', ready);
    }
  }

})();
