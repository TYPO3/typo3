.. include:: /Includes.rst.txt

.. _examples:

========
Examples
========

In this section some common situations are described:

..  contents::
    :local:

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
the felogin plugin, but requires a custom page-not-found handler. In this sense,
we refer to :ref:`felogin-how-to-implement-403redirect-error-handler`.


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
   [loginUser('*')]
       10.value = Logout
       10.typolink.additionalParams = &logintype=logout
   [end]

Of course there can be solutions with :typoscript:`HMENU` items, etc.

.. _felogin-how-to-implement-403redirect-error-handler:

Custom error handler implementation for 403 redirects
=====================================================

This section explains how to utilize a custom error handler
to catch 403 restricted page errors and allow to forward
to a login form, and then redirect back to the originating
page after successful login.

..  rst-class:: bignums

#.  You need the following site settings in the error handling

    ..  figure:: ../Images/felogin_site_settings_error_handling.png
        :caption: Error Handling tab of site configuration module
        :class: with-shadow

        :guilabel:`Error Handling` tab of Site Configuration module

    There you add the custom 403 error handler and configure
    the error handler, you create in the following steps.

    ..  todo:: Future TYPO3 versions may do this automatically
        see https://review.typo3.org/c/Packages/TYPO3.CMS/+/81945

    ..  seealso::
        :ref:`Error handling in site configuration <t3coreapi:sitehandling-errorHandling>`

#.  Look up the page ID where a login form (like with EXT:felogin) is placed

    This page ID is needed in the following step, so that the error
    handler will know, where to forward an unauthenticated user to, so
    that a login can be performed.

    Ideally, this should be done by configuring a page ID via the
    site settings, and referring back to a named ID. See
    :ref:`PHP API: accessing site configuration <t3coreapi:sitehandling-php-api>`
    for more information. For reduced complexity, this example uses
    a hard-coded page ID.

#.  Create a new error handler :file:`RedirectLoginErrorHandler.php`

    Create a PHP error handler class like the following in a custom
    extension, like your own :ref:`sitepackage <t3sitepackage:start>`:

    .. literalinclude:: _RedirectLoginErrorHandler.php
       :caption: EXT:my_sitepackage/Classes/Error/PageErrorHandler/RedirectLoginErrorHandler.php
       :language: php

    Adapt the constant :php:`PAGE_ID_LOGIN_FORM` to match the
    page ID from the previous step.
    Since there is no proper way how to do it otherwise, we put in the page ID
    of the login form hard-coded into the file :file:`RedirectLoginErrorHandler.php`
    and define a constant :php:`PAGE_ID_LOGIN_FORM` for it. In the example
    above, this is set to `656`.

    ..  hint::

        This example code uses PHP 8.1 syntax. Depending on the PHP version you use in
        your project, you may need to adapt language features like `readonly` to match your
        used PHP version.

#.  In your EXT:felogin plugin, make sure you selected "Defined by GET/POST
    Parameters" as first redirect mode

    ..  figure:: ../Images/SettingsRedirectCustomErrorHandler.png
        :caption: Plugin > Redirects tab of Login Form content element
        :class: with-shadow

        :guilabel:`Plugin > Redirects` tab of :guilabel:`Login Form` content element

    You need to configure the login form that receives your redirect in a
    way, that allows to evaluate submitted URL parameters. In `EXT:felogin`,
    this is achieved via this :guilabel:`Redirect Mode` (which can also be set
    through TypoScript configuration, see :confval:`redirectMode <typoscript-redirectmode>`.

    Your login form will probably also need to define a specific target page
    for normal logins (independent from the error handler redirect), so you
    should also add a `redirectMode` like `login` to your list, and set
    a target page in :confval:`redirectPageLogin <typoscript-redirectpagelogin>`.

#.  Testing the custom error handler

    Clear the caches, for example via the backend module
    :guilabel:`Admin Tools > Maintenance`.

    Then open any access-restricted page
    in an incognito browser window to be sure that
    you are not logged in yet. Here we will use the example
    URL :samp:`https://example.org/restricted/page`.

    When everything is configured correctly and if you are not logged in
    yet, then you should be redirected to your login page like
    :samp:`https://example.org/login` (example page ID `656`).

    After entering proper frontend user credentials, you should be redirected
    back to :samp:`https://example.org/restricted/page`, the page where you
    wanted to get to initially.

    ..  hint::

        When you have multiple site configurations, be sure to access
        the correct one. This means where both the login form is located,
        and the custom error handler is configured for.

    ..  hint::

        Do not copy the generated link from the address URL after you clicked
        :guilabel:`View webpage` from the backend, and then just paste it into
        the URL bar of the incognito window. The reason is that when
        being logged in to the backend, a possibly simulated frontend user
        login can affect your tests.

    ..  hint::

        Do not get confused when the URL
        :samp:`https://example.org/restricted/page` will be forwarded to a URL
        like

        :samp:`https://example.org/login?return_url=https%3A%2F%2Fexample.org%3A8443%2Frestricted%2Fpage&cHash=d0e92f9f9f7b3ca98a2e5e688ad22de9`

        when you want to access the restricted page in the first place.
        These are the `getpost` redirect parameters that are evaluated by
        `EXT:felogin`. Now type in the user credentials of the already created
        frontend user and you should get redirected to the desired
        page :samp:`https://example.org/restricted/page`.

This example was taken from
`[FEATURE] Introduce ErrorHandler for 403 errors with redirect option <https://review.typo3.org/c/Packages/TYPO3.CMS/+/81945>`__
which works in TYPO3 v11 and v12, and has been integrated to TYPO3 v13, where it can be used
without a custom implementation.
