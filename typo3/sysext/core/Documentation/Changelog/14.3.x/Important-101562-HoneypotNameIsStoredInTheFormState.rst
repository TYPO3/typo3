..  include:: /Includes.rst.txt

..  _important-101562-1792100428:

================================================================
Important: #101562 - Honeypot name is stored in the form state
================================================================

See :issue:`101562`

Description
===========

The honeypot of EXT:form adds an invisible field with a random name to every
form step. Until now this name was stored in the frontend session. Without a
session - a bot sending no session cookie, or a form step delivered from the
page cache - the honeypot was silently not validated.

The name is now stored per form step in the HMAC-signed form state, which is
rendered with the form as the hidden :html:`__state` field, and is read from
there when the form is submitted. If the honeypot is enabled and the submitted
form state carries no honeypot name, the submission is rejected and the form
step is shown again, as if the honeypot had been filled in.

As a consequence:

*   Rendering a form no longer stores data in the frontend session. The
    session keys prefixed with :php:`tx_form_honeypot_name_` are no longer
    read or written.
*   The honeypot also works on cached pages and with static file caching, for
    example with EXT:staticfilecache. On a statically cached page all visitors
    get the same honeypot name. The recommendation to disable the honeypot in
    such setups has been removed from the documentation.

Affected installations
======================

Installations using EXT:form with the honeypot enabled, which is the default.

Forms rendered before the update - in an open browser tab, in the page cache or
in a static file cache - carry no honeypot name in their form state. Submitting
such a form shows the form step again instead of accepting the submission.

Migration
=========

Flush all page caches after the update, including static file caches such as
the one of EXT:staticfilecache.

Installations that disabled the honeypot only because of static file caching
can enable it again:

..  code-block:: yaml

    prototypes:
      standard:
        formElementsDefinition:
          Form:
            renderingOptions:
              honeypot:
                enable: true

..  index:: Frontend, NotScanned, ext:form
