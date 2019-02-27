(function(req) {
  /**
   * Determines whether moduleName is configured in requirejs paths
   * (this code was taken from RequireJS context.nameToUrl).
   *
   * @see context.nameToUrl
   * @see https://github.com/requirejs/requirejs/blob/2.3.3/require.js#L1650-L1670
   *
   * @param {Object} config the require context to find state.
   * @param {String} moduleName the name of the module.
   * @return {boolean}
   */
  var inPath = function(config, moduleName) {
    var i, parentModule, parentPath;
    var paths = config.paths;
    var syms = moduleName.split('/');
    //For each module name segment, see if there is a path
    //registered for it. Start with most specific name
    //and work up from it.
    for (i = syms.length; i > 0; i -= 1) {
      parentModule = syms.slice(0, i).join('/');
      parentPath = paths[parentModule];
      if (parentPath) {
        return true;
      }
    }
    return false;
  };

  /**
   * @return {XMLHttpRequest}
   */
  var createXhr = function() {
    if (typeof XMLHttpRequest !== 'undefined') {
      return new XMLHttpRequest();
    } else {
      return new ActiveXObject('Microsoft.XMLHTTP');
    }
  };

  /**
   * Fetches RequireJS configuration from server via XHR call.
   *
   * @param {object} config
   * @param {string} name
   * @param {function} success
   * @param {function} error
   */
  var fetchConfiguration = function(config, name, success, error) {
    // cannot use jQuery here which would be loaded via RequireJS...
    var xhr = createXhr();
    xhr.onreadystatechange = function() {
      if (this.readyState !== 4) {
        return;
      }
      try {
        if (this.status === 200) {
          success(JSON.parse(xhr.responseText));
        } else {
          error(this.status, xhr.statusText);
        }
      } catch (error) {
        error(this.status, error);
      }
    };
    xhr.open('GET', config.typo3BaseUrl + '&name=' + encodeURIComponent(name));
    xhr.send();
  };

  /**
   * Adds aspects to RequireJS configuration keys paths and packages.
   *
   * @param {object} config
   * @param {string} data
   * @param {object} context
   */
  var addToConfiguration = function(config, data, context) {
    if (data.shim && data.shim instanceof Object) {
      if (typeof config.shim === 'undefined') {
        config.shim = {};
      }
      Object.keys(data.shim).forEach(function(moduleName) {
        config.shim[moduleName] = data.shim[moduleName];
      });
    }
    if (data.paths && data.paths instanceof Object) {
      if (typeof config.paths === 'undefined') {
        config.paths = {};
      }
      Object.keys(data.paths).forEach(function(moduleName) {
        config.paths[moduleName] = data.paths[moduleName];
      });
    }
    if (data.packages && data.packages instanceof Array) {
      if (typeof config.packages === 'undefined') {
        config.packages = [];
      }
      data.packages.forEach(function (packageName) {
        config.packages.push(packageName);
      });
    }
    context.configure(config);
  };

  // keep reference to RequireJS default loader
  var originalLoad = req.load;

  /**
   * Does the request to load a module for the browser case.
   * Make this a separate function to allow other environments
   * to override it.
   *
   * @param {Object} context the require context to find state.
   * @param {String} name the name of the module.
   * @param {Object} url the URL to the module.
   */
  req.load = function(context, name, url) {
    if (inPath(context.config, name) || url.charAt(0) === '/') {
      return originalLoad.call(req, context, name, url);
    }

    fetchConfiguration(
      context.config,
      name,
      function(data) {
        addToConfiguration(context.config, data, context);
        url = context.nameToUrl(name);
        // result cannot be returned since nested in two asynchronous calls
        originalLoad.call(req, context, name, url);
      },
      function() {}
    );
  };
})(requirejs);
