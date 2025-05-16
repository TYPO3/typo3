..  include:: /Includes.rst.txt

..  _important-106715-1747646438:

==================================================================================
Important: #106715 - Apply CSP sandbox mode to fileadmin's .htaccess configuration
==================================================================================

See :issue:`106715`

Description
===========

The directive `Content-Security-Policy: sandbox;` restricts
several client-side actions for files that may contain markup
(e.g., HTML, SVG):

* Disallows downloads
* Disallows form submissions
* Disallows modals and popups
* Disallows orientation and pointer lock
* Disallows presentation sessions
* Disallows navigation of the top-level browsing context

This applies only to resources located in the default file storage
location (e.g., `/fileadmin/`). Rendering Fluid templates from a
different location within the CMS application uses TYPO3’s dynamic
CSP feature instead.

Since the file :file:`/fileadmin/.htaccess` is not automatically updated
once it has been created in a TYPO3 installation, maintainers must manually
adjust the web server configuration.

Below are the required changes to introduce the `sandbox` directive:

..  code-block:: diff

     <IfModule mod_headers.c>
         # matching requested *.pdf files only (strict rules block Safari showing PDF documents)
         <FilesMatch "\.pdf$">
             Header set Content-Security-Policy "default-src 'self' 'unsafe-inline'; script-src 'none'; object-src 'self'; plugin-types application/pdf;"
         </FilesMatch>
         # matching requested *.svg files only (allows using inline styles when serving SVG files)
         <FilesMatch "\.svg">
    -        Header set Content-Security-Policy "default-src 'self'; script-src 'none'; style-src 'unsafe-inline'; object-src 'none';"
    +        Header set Content-Security-Policy "default-src 'self'; script-src 'none'; style-src 'unsafe-inline'; object-src 'none'; sandbox;"
         </FilesMatch>
         # matching anything else, using negative lookbehind pattern
         <FilesMatch "(?<!\.(?:pdf|svg))$">
    -        Header set Content-Security-Policy "default-src 'self'; script-src 'none'; style-src 'none'; object-src 'none';"
    +        Header set Content-Security-Policy "default-src 'self'; script-src 'none'; style-src 'none'; object-src 'none'; sandbox;"
         </FilesMatch>

..  index:: ext:install
