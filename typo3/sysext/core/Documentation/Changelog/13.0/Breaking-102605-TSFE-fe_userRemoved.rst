.. include:: /Includes.rst.txt

.. _breaking-102605-1701772495:

=========================================
Breaking: #102605 - TSFE->fe_user removed
=========================================

See :issue:`102605`

Description
===========

Frontend-related property :php:`TypoScriptFrontendController->fe_user` has been removed.

When looking at the TYPO3 frontend rendering chain, class :php:`TypoScriptFrontendController`
is by far the biggest technical debt: It mixes a lot of concerns and carries tons of state
and functionality that should be modeled differently, which leads to easier to understand
and more flexible code. The class is shrinking since various major versions already and will
ultimately dissolve entirely at some point. Changes in this area are becoming more aggressive
with TYPO3 v13. Any code using the class will need adaptions at some point, single patches
will continue to communicate alternatives.

In case of the :php:`fe_user` property, two alternatives exist: The frontend user
can be retrieved from the PSR-7 request attribute :php:`frontend.user`, and basic frontend user
information is available using the :php:`Context` aspect :php:`frontend.user`.

Note accessing TypoScript :typoscript:`TSFE:fe_user` details continues to work for now, using
for example :typoscript:`lib.foo.data = TSFE:fe_user|user|username` to retrieve the username of a
logged in user is still ok.


Impact
======

Using :php:`TypoScriptFrontendController->fe_user` (or
:php:`$GLOBALS['TSFE']->fe_user`) will raise a PHP fatal error.


Affected installations
======================

Instances with extensions dealing with frontend user details may be affected, typically
custom login extensions or extensions consuming detail data of logged in users.


Migration
=========

There are two possible migrations.

First, a limited information list of frontend user details can be retrieved using the :php:`Context`
aspect :php:`frontend.user` in frontend calls. See class :php:`\TYPO3\CMS\Core\Context\UserAspect` for a
full list. The current context can retrieved using dependency injection. Example:

.. code-block:: php

    use TYPO3\CMS\Core\Context\Context;

    final class MyExtensionController {
        public function __construct(
            private readonly Context $context,
        ) {}

        public function myAction() {
            $frontendUserUsername = $this->context->getPropertyFromAspect('frontend.user', 'username', ''));
        }
    }

Additionally, the full :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication` object is
available as request attribute :php:`frontend.user` in the frontend. Note some details of that object
are marked :php:`@internal`, using the context aspect is thus the preferred way. Example of an extension
using Extbase's :php:`ActionController`:

.. code-block:: php

    final class MyExtensionController extends ActionController {
        public function myAction() {
            // Note the 'user' property is marked @internal.
            $frontendUserUsername = $this->request->getAttribute('frontend.user')->user['username'];
        }
    }


.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
