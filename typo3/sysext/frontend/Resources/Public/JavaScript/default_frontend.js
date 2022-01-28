(function() {
  /**
   * @param {Function} callback
   */
  function ready(callback) {
    if (document.readyState === 'complete') {
      callback.call(null);
      return;
    }
    var clearListeners = function() {
      window.removeEventListener('load', delegate);
      document.removeEventListener('DOMContentLoaded', delegate);
    };
    var delegate = function() {
      clearListeners();
      callback.call(null);
    };
    window.addEventListener('load', delegate);
    document.addEventListener('DOMContentLoaded', delegate);
  }
  /**
   * Decoding helper function
   *
   * @param {number} charCode
   * @param {number} start
   * @param {number} end
   * @param {number} offset
   * @return {string}
   */
  function decryptCharcode(charCode, start, end, offset) {
    charCode = charCode + offset;
    if (offset > 0 && charCode > end) {
      charCode = start + (charCode - end - 1);
    } else if (offset < 0 && charCode < start) {
      charCode = end - (start - charCode - 1);
    }
    return String.fromCharCode(charCode);
  }
  /**
   * Decodes string
   *
   * @param {string} value
   * @param {number} offset
   * @return {string}
   */
  function decryptString(value, offset) {
    var result = '';
    for (var i=0; i < value.length; i++) {
      var charCode = value.charCodeAt(i);
      if (charCode >= 0x2B && charCode <= 0x3A) {
        result += decryptCharcode(charCode,0x2B,0x3A,offset);	/* 0-9 . , - + / : */
      } else if (charCode >= 0x40 && charCode <= 0x5A) {
        result += decryptCharcode(charCode,0x40,0x5A,offset);	/* A-Z @ */
      } else if (charCode >= 0x61 && charCode <= 0x7A) {
        result += decryptCharcode(charCode,0x61,0x7A,offset);	/* a-z */
      } else {
        result += value.charAt(i);
      }
    }
    return result;
  }

  /**
   * Opens URL in new window.
   *
   * @param {string} url
   * @param {string|null} target
   * @param {string|null} features
   * @return {Window}
   */
  function windowOpen(url, target, features) {
    var windowRef = window.open(url, target, features);
    if (windowRef) {
      windowRef.focus();
    }
    return windowRef;
  }

  // @deprecated Will be removed in TYPO3 v12.0
  if (typeof window['linkTo_UnCryptMailto'] === 'undefined') {
    window['linkTo_UnCryptMailto'] = function(value, offset) {
      console.warn('Function linkTo_UnCryptMailto() is deprecated and will be remove in TYPO3 v12.0');
      if (value && offset) {
        document.location.href = decryptString(value, offset);
      }
    };
  }

  ready(function() {
    var mailtoElements = document.querySelectorAll('a[data-mailto-token][data-mailto-vector]');
    // `Array.from` for IE compatibility
    Array.from(mailtoElements).forEach(function(element) {
        element.addEventListener('click', function(evt) {
          evt.preventDefault();
          var dataset = evt.currentTarget.dataset;
          var value = dataset.mailtoToken;
          var offset = parseInt(dataset.mailtoVector, 10) * -1;
          document.location.href = decryptString(value, offset);
        });
      });
    var openElements = document.querySelectorAll('a[data-window-url]');
    // `Array.from` for IE compatibility
    Array.from(openElements).forEach(function(element) {
        element.addEventListener('click', function(evt) {
          evt.preventDefault();
          var dataset = evt.currentTarget.dataset;
          var url = dataset.windowUrl;
          var target = dataset.windowTarget || null;
          var features = dataset.windowFeatures || null;
          windowOpen(url, target, features);
        });
      });
  });
})();
