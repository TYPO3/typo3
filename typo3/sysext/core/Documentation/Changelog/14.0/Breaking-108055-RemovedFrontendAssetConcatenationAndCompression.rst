..  include:: /Includes.rst.txt

..  _breaking-108055-1762346705:

========================================================================
Breaking: #108055 - Removed frontend asset concatenation and compression
========================================================================

See :issue:`108055`

Description
===========

Introduction
------------

The implementation of CSS and JavaScript asset concatenation and
pre-compression has been removed from the TYPO3 Core in v14.

The following TypoScript options are now obsolete:

* Config property :typoscript:`config.compressCss`
* Config property :typoscript:`config.compressJs`
* Config property :typoscript:`config.concatenateCss`
* Config property :typoscript:`config.concatenateJs`
* The :typoscript:`resource` properties
  :typoscript:`disableCompression` and
  :typoscript:`excludeFromConcatenation` in the
  :typoscript:`PAGE` properties :typoscript:`includeCSS`,
  :typoscript:`includeCSSLibs`, :typoscript:`includeJS`,
  :typoscript:`includeJSFooter`, :typoscript:`includeJSFooterlibs`
  and :typoscript:`includeJSLibs`.

  Example:

  .. code-block:: typoscript

      page = PAGE
      page.includeCSS {
          main = EXT:site_package/Resources/Public/Css/main.css
          # obsolete
          main.disableCompression = 1
          # obsolete
          main.excludeFromConcatenation = 1
      }

The configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` is obsolete
in combination with :ref:`breaking-107943-1761860828`. Existing settings
in :file:`settings.php` configuration files are automatically removed
when the install tool is first used after upgrading to TYPO3 v14.

The PHP class :php:`\TYPO3\CMS\Core\Resource\ResourceCompressor` has
been removed.

Feature rundown
---------------

In TYPO3 versions prior to v14, the system included a built-in mechanism
to compile multiple registered CSS and JavaScript files into single files
and to prepare compressed versions of those concatenated files for
direct delivery by the web server.

This functionality had to be explicitly enabled by setting the global
configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` and the
TypoScript options :php:`config.concatenate*` and optionally
:php:`config.compress*`. CSS and JavaScript files registered in the
frontend using the :typoscript:`PAGE.include*` options were then
concatenated and optionally compressed into a single file.

The implementation also concatenated external asset resources referenced
via :code:`http` and :code:`https`, which were fetched server-side using
:php:`GeneralUtility::getUrl()` and merged with local resources.

Concatenation was designed to be as transparent as possible: hashes were
created from all referenced file names and their contents (including
external content) for each TYPO3 frontend page request, generating
unique file names that changed whenever an included file or its content
was modified. The resulting concatenated files were then referenced in
the generated HTML page instead of the original resource links.

Pre-compression operated on top of concatenation: if enabled, a gzip-
compressed :file:`.gz` file was created and referenced in the HTML page
response.

Configuration was handled via TypoScript, while asset registration could
be performed using TypoScript or PHP with the :php:`PageRenderer`. The
Fluid asset ViewHelpers :html:`f:asset.css` and :html:`f:asset.script`
register assets using the :php:`AssetCollector`, introduced in TYPO3
v10. This implementation never supported concatenation or compression,
as it was developed independently from the PageRenderer's asset handling
and did not use the :php:`ResourceCompressor`.

Concatenation and compression removal reasoning
-----------------------------------------------

A closer look at the concatenation and compression functionality reveals
several reasons for its removal:

* **HTTP/2 and HTTP/3**: Modern HTTP versions allow multiple resource
  requests in parallel ("multiplexing"), making server-side asset
  concatenation obsolete. These versions also provide significant
  performance improvements on both the server and client sides compared
  to HTTP/1.1 with concatenation. HTTP/2 and HTTP/3 are only available
  via SSL (HTTPS), which is now the standard for all serious websites.
  All major web servers and browsers have supported at least HTTP/2 for
  years.

* **Fragile implementation**: Concatenating multiple CSS files within
  the application caused path and encoding issues. The resulting files
  had to be stored in writable public directories, and relative paths in
  CSS files (such as those in :css:`@import` rules) had to be parsed and
  adjusted. Additionally, the CSS statement :css:`@charset` had to be
  parsed since it must appear only once per CSS file, leading to
  potential collisions that have never been resolved.

* **Parallel systems**: Concatenation and compression were supported
  only for assets registered via TypoScript :typoscript:`page.include*`
  or the :php:`PageRenderer` in PHP. The Fluid ViewHelpers
  :html:`f:asset.css` and :html:`f:asset.script` operated independently
  and never supported these features. Removing concatenation and
  compression simplifies future unification of both asset systems.

* **Performance issues with external assets**: To create as few asset
  resources as possible, external assets (example:
  :code:`https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js`)
  were by default (if :typoscript:`excludeFromConcatenation = 1` was not
  explicitly set) fetched by TYPO3 when creating the page. The hashed
  resource path and the file content were part of the created
  concatenation filename. This ensured that the server-side created file
  was always current. TYPO3 therefore fetched external resources for
  *each* uncached page request. Worse, it fetched those external
  resources for *each request* that contained a non-cached content
  element ("INT" elements), and also for *each request* when an instance
  enabled CSP handling. This could easily lead to severe performance
  degradation.

* **Compression dependency**: Pre-compression (gzip) was only available
  when concatenation was enabled, adding further complexity and
  limitations.

* **HTTP violations with caching**: When TYPO3 cached a page that
  referenced pre-compressed assets, it stored the version based on the
  client’s :code:`Accept-Encoding` header. Subsequent requests from
  clients not supporting gzip would still receive references to
  compressed assets, violating HTTP standards.

* **Double compression risk**: Web servers might automatically re-
  compress already compressed files. TYPO3 tried to prevent this with an
  Apache-specific :file:`.htaccess` configuration, but other servers
  like nginx required custom setups, often confusing integrators.

* **Modern compression standards**: Modern web servers and browsers
  support more efficient algorithms such as Brotli and Zstandard, which
  TYPO3 never implemented due to the complexity of its existing system.

* **Minimal performance gain**: The benefit of pre-compression was
  minor. Modern web servers can compress small assets (like typical CSS
  or JS files) on the fly with negligible overhead. As a rough ballpark
  estimation, a single CPU core can compress hundreds of MB per second,
  while a large JavaScript library like jQuery is usually below 100 KB.
  CPU time is rarely the bottleneck.

In summary, asset concatenation and pre-compression are no longer needed
and should not be handled at the application level. The implementation
was riddled with issues over the years and integrators struggled to
reach working solutions. The Core team closed issues in this area for
years with "Won't fix, use a different solution."

Alternatives
------------

Most TYPO3 instances can operate perfectly well without the removed
asset concatenation and compression features. Modern browsers, web
servers, and HTTP protocol versions provide efficient alternatives that
make the previous TYPO3-internal implementation unnecessary.

If your instance still relies on concatenated or pre-built asset bundles
for specific use cases, consider the following alternatives:

* **Use a modern bundler**: Tools such as `Vite <https://vitejs.dev/>`_
  or Webpack can handle asset concatenation, minification, and
  optimization during the build process. A TYPO3-specific integration is
  available as an extension:
  `Vite AssetCollector <https://extensions.typo3.org/extension/
  vite_asset_collector>`_.

* **Consider the `sgalinski/scriptmerger` extension**: This community
  extension provides an alternative approach to script and stylesheet
  merging and compression. It may be useful for projects that cannot yet
  switch to build-time bundling.

* **Enable HTTP/2 or HTTP/3**: Modern HTTP versions support
  multiplexing, allowing browsers to download multiple assets
  simultaneously from a single connection, eliminating the need for
  server-side concatenation.

  Example **Apache** configuration:

  .. code-block:: plaintext

      <IfModule http2_module>
          Protocols h2 http/1.1
      </IfModule>

      # Optional: enable pre-compressed asset delivery
      AddEncoding gzip .gz
      AddType "text/javascript" .js.gz
      AddType "text/css" .css.gz
      <FilesMatch "\.(js|css)\.gz$">
          ForceType text/plain
          Header set Content-Encoding gzip
      </FilesMatch>

  Example **nginx** configuration:

  .. code-block:: plaintext

      # Enable HTTP/2 on your SSL virtual host
      server {
          listen 443 ssl http2;
          server_name example.com;

          ssl_certificate /etc/ssl/certs/example.crt;
          ssl_certificate_key /etc/ssl/private/example.key;

          # Serve pre-compressed assets if available
          gzip_static on;
          # Optionally also enable on-the-fly compression
          gzip on;
          gzip_types text/css application/javascript;

          [...]
      }

  Both configurations ensure that compressed versions of static assets
  (e.g., :file:`.js.gz` or :file:`.css.gz`) are automatically delivered
  to clients that support gzip encoding.

  Both Apache and nginx can also cache the compressed output in memory
  or on disk to avoid runtime overhead. The keywords to look for are
  :code:`mod_deflate` with :code:`mod_cache` for Apache, and
  :code:`proxy_cache` for nginx.

* **Use a Content Delivery Network (CDN)**: For TYPO3 instances
  experiencing heavy frontend traffic or high asset load, a CDN can
  serve static resources such as CSS, JavaScript, and images directly
  from distributed edge servers. This offloads delivery from the main
  web server, reduces latency, and improves caching efficiency.

In summary, most TYPO3 setups can safely rely on HTTP/2, modern build
pipelines, and proper web server or CDN configuration to achieve optimal
frontend performance without any TYPO3-internal concatenation or
compression.

Impact
======

The configuration toggles mentioned above are now obsolete, and TYPO3
will no longer concatenate or compress included assets.

Affected installations
======================

Instances that configured
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']` to non-zero
values and enabled the TypoScript settings described above for asset
concatenation or compression are affected.

Migration
=========

Consider one or more of the alternatives outlined above.

..  index:: Frontend, LocalConfiguration, TypoScript, NotScanned, ext:frontend
