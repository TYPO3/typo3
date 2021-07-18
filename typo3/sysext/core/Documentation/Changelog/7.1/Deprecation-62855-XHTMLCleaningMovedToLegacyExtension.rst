
.. include:: ../../Includes.txt

==============================================================================
Deprecation: #62855 - "XHTML cleaning" functionality moved to legacy extension
==============================================================================

See :issue:`62855`

Description
===========

XHTML cleaning is not necessary anymore, since modern technology bases completely on HTML5. Therefore the
according functionality has been moved to the legacy extension EXT:compatibility6.

The following TypoScript option has been marked for deprecation:

.. code-block:: typoscript

   config.xhtml_cleaning

The following PHP method has been marked for deprecation:

.. code-block:: php

   TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::doXHTML_cleaning()

Impact
======

The content output of the TYPO3 frontend is not cleaned anymore unless EXT:compatibility6 is loaded.


Affected installations
======================

Any installation having the TypoScript option :typoscript:`config.xhtml_cleaning`
set will have different behaviour in the
frontend rendering.

Migration
=========

For TYPO3 CMS 7, installing EXT:compatibility6 brings back the existing functionality.


.. index:: TypoScript, Frontend
