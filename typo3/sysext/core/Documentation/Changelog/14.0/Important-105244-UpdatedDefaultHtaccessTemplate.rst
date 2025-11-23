..  include:: /Includes.rst.txt

..  _important-105244-1742993558:

=======================================================
Important: #105244 - Updated default .htaccess template
=======================================================

See :issue:`105244`

Description
===========

When installing TYPO3 for the first time, a :file:`.htaccess` file is added to
the :path:`htdocs` or :path:`public` directory when running TYPO3 via an Apache
web server.

In addition to several TYPO3-specific optimizations, this file mainly contains
rules (using the "mod_rewrite" Apache module) that redirect all URL requests
for non-existent files within a TYPO3 project to the main :file:`index.php`
entry point file.

For new installations, this file now contains updated configuration that can
also be applied to existing TYPO3 setups to reflect the current default
behavior.

**Key changes:**

*   URL requests within :path:`/_assets/` and :path:`/fileadmin/` are no longer
    redirected, as these directories contain resources either managed by TYPO3
    or by editors.
*   The directory :path:`/_assets/` has been included since TYPO3 v12 in
    Composer-based installations and is now officially added.
*   The folder :path:`/uploads/` is no longer maintained by TYPO3 since v11 and
    is now removed from the default :file:`.htaccess` configuration. This means
    that TYPO3 pages can now officially use the URL path `/uploads`.

It is recommended to apply these adjustments in existing TYPO3 installations as
well, even for other web servers such as nginx or IIS, provided there is no
custom usage of :path:`/_assets/` or :path:`/uploads/` (for example through a
PSR-15 middleware, custom extension, or custom routing).

**Apache example:**

In Apache-based setups, look for this line:

..  code-block:: text

    RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|uploads/) - [L]

and replace it with:

..  code-block:: text

    RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|_assets/) - [L]

..  index:: ext:frontend
