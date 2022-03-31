.. include:: /Includes.rst.txt

=======================================================================
Feature: #89115 - Auto slug update and redirect creation on slug change
=======================================================================

See :issue:`89115`

Description
===========

If EXT:redirects is installed and a slug is updated by a backend user,
a redirect from the old URL to the new URL will be created.
All sub pages are checked too and the slugs will be updated.

After the creation of the redirects a notification will be shown to the user.

The notification contains two possible actions:

* revert the complete slug update and remove the redirects
* or only remove the redirects

This new behaviour can be configured by site configuration (Example for your :file:`config.yaml`):

.. code-block:: yaml

   settings:
      redirects:
        # Automatically update slugs of all sub pages
        # (default: true)
        autoUpdateSlugs: true
        # Automatically create redirects for pages with a new slug (works only in LIVE workspace)
        # (default: true)
        autoCreateRedirects: true
        # Time To Live in days for redirect records to be created - `0` disables TTL, no expiration
        # (default: 0)
        redirectTTL: 30
        # HTTP status code for the redirect, see
        # https://developer.mozilla.org/en-US/docs/Web/HTTP/Redirections#Temporary_redirections
        # (default: 307)
        httpStatusCode: 307

.. note::

   No redirects are generated for workspace versions in the TYPO3 backend.
   :yaml:`settings.redirect.autoCreateRedirects` is internally disabled in this case.

.. attention::

   This API is considered experimental and may change anytime until declared being stable.
   For example there exists plans for moving the settings out of the :file:`config.yaml` file.

.. index:: Backend, ext:redirects
