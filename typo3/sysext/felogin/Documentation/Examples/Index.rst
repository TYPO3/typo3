.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _examples:

Examples
--------

In this section some common situations are described.


.. _login-and-back-to-original-page:

Send visitors to login page and redirect to original page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A common situation is that visitors who go to a page with access
restrictions should go to a login page first and after logging in
should be send back to the page they originally requested.

Assume we have a login page with id 2.

Using TypoScript we can still display links to access restricted pages
and send visitors to the login page:

::

   config {
           typolinkLinkAccessRestrictedPages = 2
           typolinkLinkAccessRestrictedPages_addParams = &return_url=###RETURN_URL###
   }

On the login page the login form must be configured to redirect to the
original page:

::

   plugin.tx_felogin_pi1.redirectMode = referer

(This option can also be set in the flexform configuration of the
felogin content element)

If visitors will directly enter the URL of an access restricted page
they will be sent to the first page in the rootline to which they have
access. Sending those direct visits to a login page is not a job of
the felogin plugin, but requires a custom page-not-found handler.


.. _login-link-visibility:

Login link visible when not logged in and logout link visible when logged in
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Again TypoScript will help you out. The page with the login form has
id=2:

::

   10 = TEXT
   10 {
           value = Login
           typolink.parameter = 2
   }
   [loginUser = *]
   10.value = Logout
   10.typolink.additionalParams = &logintype=logout
   [end]

Of course there can be solutions with HMENU items, etc.


.. _access-restrictions:

Access restrictions on the felogin plugin
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A very common issue is that the felogin plugin is set to Access: Hide
at login. After the core has processed the login request the page will
be rendered without the felogin plugin. If there are redirect options
active they will NOT be executed, simply because the felogin plugin is
hidden.

One solution is to insert felogin with TypoScript in the page. The
redirect options must be set in the TypoScript configuration. Any
output of this plugin can be hidden with CSS. Redirect options will be
executed by this invisible felogin. If there are two instances of
felogin present on a page (one as a content element, the other via
TypoScript) this can easily lead to problems, just as with any
pi\_base plugin.

Of course setting the felogin plugin to Hide at login and having
redirect options together doesn't really makes sense if a redirect
will happen in all cases.

