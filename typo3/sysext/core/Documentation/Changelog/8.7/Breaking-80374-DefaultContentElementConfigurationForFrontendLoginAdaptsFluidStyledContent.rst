.. include:: ../../Includes.txt

=======================================================================================================
Breaking: #80374 - Default content element configuration for frontend login adapts fluid styled content
=======================================================================================================

See :issue:`80374`

Description
===========

Default configuration for the frontend login content element was adapted to match
fluid styled content instead of css styled content by default. Css styled content
was adapted and works there as before.

Rendering for css styled content
--------------------------------

.. code-block:: typoscript

   tt_content.login = COA
   tt_content.login {
      10 =< lib.stdheader
      20 =< plugin.tx_felogin_pi1
   }

Rendering for fluid styled content
----------------------------------

.. code-block:: typoscript

   tt_content.login =< lib.contentElement
   tt_content.login {
      templateName = Generic
      variables {
         content =< plugin.tx_felogin_pi1
      }
   }

Impact
======

Adjustments made manually to the TypoScript rendering definition of
:typoscript:`tt_content.login` might not work in fluid styled content as expected.

Affected Installations
======================

Installations that are using fluid styled content and directly modify
configuration of :typoscript:`tt_content.login`.

Migration
=========

Manual adaption is necessary.

.. index:: TypoScript, Frontend, ext:fluid_styled_content
