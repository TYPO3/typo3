.. include:: /Includes.rst.txt

==============================================================
Important: #76166 - X-UA-Compatible not set in backend anymore
==============================================================

See :issue:`76166`

Description
===========

As the official recommendation of Microsoft regarding the X-UA-Compatible tag is to not set this in the HTML code but
instead via server configuration, the tag is not rendered by default in the backend anymore.

The TYPO3 default :file:`.htaccess` and :file:`web.config` files already contain the corresponding settings.
If you are using Internet Explorer configured to use compat mode by default, you may need to set these settings to ensure `edge` mode.

.. index:: Backend, ext:backend
