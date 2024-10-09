..  include:: /Includes.rst.txt

..  _important-105244-1742993558:

=======================================================
Important: #105244 - Updated default .htaccess template
=======================================================

See :issue:`105244`

Description
===========

When installing TYPO3 for the first time, a .htaccess file is added to the
:path:`htdocs` / :path:`public` path, when running TYPO3 via Apache webserver.

Next to some TYPO3 optimizations, this file mainly contains rules (via the
"mod_rewrite" Apache2 module) for pointing all URL requests to non-existent
files within a TYPO3 project to the main :file:`index.php` entry point file.

For new installations, this file has some changed configuration, which can be
adapted to existing TYPO3 installations reflecting a default setup:

URL requests within :path:`/_assets/` and :path:`/fileadmin/` are not redirected,
as they contain resources either managed by editors or TYPO3 itself.
The directory :path:`/_assets/` is added now, as it has been in place since TYPO3 v12 for
Composer-based installations.

The folder :path:`/uploads/` is officially not needed anymore since TYPO3 v11, and
not maintained by TYPO3 anymore. This folder is now removed from the :file:`.htaccess`
configuration as well, so TYPO3 pages can officially have the URL path `/uploads`
now.

It is recommended to change this in existing TYPO3 installations as well - also
with other server configurations such as nginx or IIS - in case no
custom usage of :path:`/_assets/` or ":path:`/uploads/` is in effect, like via a PSR-15
middleware, custom extensions, or custom routing.

In Apache-based setups, look for this line:

..  code-block:: text

    RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|uploads/) - [L]

and replace it with

..  code-block:: text

    RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|_assets/) - [L]

..  index:: ext:frontend
