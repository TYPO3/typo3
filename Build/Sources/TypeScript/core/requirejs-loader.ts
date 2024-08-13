interface RequireConfig {
  // Custom property by TYPO3
  typo3BaseUrl?: string;
}

interface InternalRequireContext {
  config: RequireConfig;
  contextName: string;
  completeLoad(moduleName: string): void;
  configure(config: RequireConfig): void;
  nameToUrl(moduleName: string, ext?: string, skipExt?: boolean): string;
  onError(err: RequireError, errback?: (err: RequireError) => void): void;
}

interface Require {
  load(context: InternalRequireContext, name: string, url: string): void;
}

(function(req: Require) {
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
  const inPath = function(config: RequireConfig, moduleName: string): boolean {
    let i, parentModule, parentPath;
    const paths = config.paths;
    const syms = moduleName.split('/');
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
  const createXhr = function(): XMLHttpRequest {
    if (typeof XMLHttpRequest !== 'undefined') {
      return new XMLHttpRequest();
    } else {
      return new ActiveXObject('Microsoft.XMLHTTP') as XMLHttpRequest;
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
  const fetchConfiguration = function(
    config: RequireConfig,
    name: string,
    success: (responseData: unknown) => void,
    error: (status: number, error: Error) => void
  ) {
    // cannot use jQuery here which would be loaded via RequireJS...
    const xhr = createXhr();
    xhr.onreadystatechange = function() {
      if (this.readyState !== 4) {
        return;
      }
      try {
        if (this.status === 200) {
          success(JSON.parse(xhr.responseText));
        } else {
          error(this.status, new Error(xhr.statusText));
        }
      } catch (err) {
        error(this.status, err);
      }
    };
    xhr.open('GET', config.typo3BaseUrl + (config.typo3BaseUrl.indexOf('?') === -1 ? '?' : '&' ) + 'name=' + encodeURIComponent(name));
    xhr.send();
  };

  /**
   * Adds aspects to RequireJS configuration keys paths and packages.
   */
  const addToConfiguration = function(config: RequireConfig, data: Partial<RequireConfig>, context: InternalRequireContext) {
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
        // Note: config.packages is defined as {} in
        // node_modules/@types/requirejs/index.d.ts
        // but it is an array
        (config.packages as Array<string>).push(packageName);
      });
    }
    context.configure(config);
  };

  // keep reference to RequireJS default loader
  const originalLoad = req.load;

  /**
   * Fallback to importShim() after import()
   * failed the first time (considering
   * importmaps are not supported by the browser).
   */
  let useShim = false;

  const moduleImporter = (moduleName: string): Promise<unknown> => {
    if (useShim) {
      return window.importShim(moduleName)
    } else {
      return import(moduleName).catch(() => {
        // Consider that import-maps are not available and use shim from now on
        useShim = true;
        return moduleImporter(moduleName)
      })
    }
  };

  const importMap: Record<string, unknown> = (() => {
    try {
      return JSON.parse(document.querySelector('script[type="importmap"]').innerHTML).imports || {};
    } catch {
      return {}
    }
  })();

  const isDefinedInImportMap = (moduleName: string): boolean => {
    if (moduleName in importMap) {
      return true
    }

    const moduleParts = moduleName.split('/');
    for (let i = 1; i < moduleParts.length; ++i) {
      const prefix = moduleParts.slice(0, i).join('/') + '/';
      if (prefix in importMap) {
        return true
      }
    }

    return false;
  }

  /**
   * Does the request to load a module for the browser case.
   * Make this a separate function to allow other environments
   * to override it.
   *
   * @param {Object} context the require context to find state.
   * @param {String} name the name of the module.
   * @param {Object} url the URL to the module.
   */
  req.load = function(context: InternalRequireContext, name: string, url: string): void {

    /* Shim to load module via ES6 if available, fallback to original loading otherwise */
    const esmName = name in importMap ? name : name.replace(/^TYPO3\/CMS\//, '@typo3/').replace(/[A-Z]+/g, str => '-' + str.toLowerCase()).replace(/(\/|^)-/g, '$1') + '.js';
    if (isDefinedInImportMap(esmName)) {
      const importPromise = moduleImporter(esmName);
      importPromise.catch(function(e) {
        const error = new Error('Failed to load ES6 module ' + esmName) as RequireError;
        (error as any).contextName = context.contextName;
        error.requireModules = [name];
        error.originalError = e;
        context.onError(error);
      });
      importPromise.then(function(module) {
        define(name, function() {
          return typeof module === 'object' && 'default' in module ? module.default : module;
        });
        context.completeLoad(name);
      });
      return;
    }

    if (
      inPath(context.config, name) ||
      url.startsWith('/') ||
      (context.config.typo3BaseUrl as unknown as boolean) === false
    ) {
      originalLoad.call(req, context, name, url);
      return;
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
      function(status, err) {
        const error = new Error('requirejs fetchConfiguration for ' + name + ' failed [' + status + ']') as RequireError;
        (error as any).contextName = context.contextName;
        error.requireModules = [name];
        error.originalError = err;
        context.onError(error);
      }
    );
  };
})(window.requirejs);
