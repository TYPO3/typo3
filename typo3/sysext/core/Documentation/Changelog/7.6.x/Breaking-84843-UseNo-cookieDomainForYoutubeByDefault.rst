.. include:: /Includes.rst.txt

==============================================================
Breaking: #84843 - Use no-cookie domain for youtube by default
==============================================================

See :issue:`84843`

Description
===========

To improve the privacy of users the renderer for YouTube videos has been changed to use
the no-cookie domain `www.youtube-nocookie.com` by default. The regular domain `www.youtube.com`
is used if explicitly set by the following TypoScript configuration:

.. code-block:: typoscript

    lib.contentElement {
        settings {
            media {
                additionalConfig {
                    no-cookie = 0
                }
            }
        }
    }


Impact
======

The TypoScript configuration :typoscript:`lib.contentElement.settings.media.additionalConfig` is used
as attribute :php:`additionalConfig` of the ViewHelper :php:`\TYPO3\CMS\Fluid\ViewHelpers\MediaViewHelper`.

If no configuration is provided, the domain `www.youtube-nocookie.com` is used.


Affected Installations
======================

Installations which require the usage of the domain `www.youtube.com` or setting cookies by YouTube.


Migration
=========

Use the TypoScript configuration :typoscript:`lib.contentElement.settings.media.additionalConfig.no-cookie = 0`

.. index:: TypoScript, ext:fluid_styled_content
