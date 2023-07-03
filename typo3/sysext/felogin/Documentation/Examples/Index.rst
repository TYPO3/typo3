.. include:: /Includes.rst.txt

.. _examples:

========
Examples
========

In this section some common situations are described.


.. _login-and-back-to-original-page:

Send visitors to login page and redirect to original page
=========================================================

A common situation is that visitors who go to a page with access
restrictions should go to a login page first and after logging in
should be send back to the page they originally requested.

Assume we have a login page with id `2`.

Using TypoScript we can still display links to access restricted pages
and send visitors to the login page:

.. code-block:: typoscript

   config {
       typolinkLinkAccessRestrictedPages = 2
       typolinkLinkAccessRestrictedPages_addParams = &return_url=###RETURN_URL###
   }

On the login page the login form must be configured to redirect to the
original page:

.. code-block:: typoscript

   plugin.tx_felogin_login.settings.redirectMode = getpost

(This option can also be set in the flexform configuration of the
felogin content element)

If visitors will directly enter the URL of an access restricted page
they will be sent to the first page in the rootline to which they have
access. Sending those direct visits to a login page is not a job of
the felogin plugin, but requires a custom page-not-found handler.


.. _login-link-visibility:

Login link visible when not logged in and logout link visible when logged in
============================================================================

Again TypoScript will help you out. The page with the login form has
id=2:

.. code-block:: typoscript

   10 = TEXT
   10 {
       value = Login
       typolink.parameter = 2
   }
   [frontend.user.isLoggedIn]
       10.value = Logout
       10.typolink.additionalParams = &logintype=logout
   [end]

Of course there can be solutions with :typoscript:`HMENU` items, etc.
