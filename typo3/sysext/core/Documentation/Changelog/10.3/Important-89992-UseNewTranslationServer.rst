.. include:: /Includes.rst.txt

==============================================
Important: #89992 - Use new Translation Server
==============================================

See :issue:`89992`

Description
===========

The work on the new translation server has been finalized so that it is used by default.

The SaaS solution Crowdin is being used to make it as simple as possible for everyone to improve the
localization of TYPO3 core and all extensions which are taking part.

If you are interested in improving the localization, register at https://crowdin.com/ and suggest translations at
the official TYPO3 Project, which can be found at https://crowdin.com/project/typo3-cms.

The documentation about the integration is part of the official TYPO3 documentation and
is available at https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Internationalization/TranslationServer/Crowdin.html.
It also covers how to make your extension as extension developer available at Crowdin.

Impact
======

The feature switch :php:`betaTranslationServer`, introduced with :issue:`89526`,
has been removed and is not evaluated anymore.

.. index:: Backend, Frontend, ext:core
