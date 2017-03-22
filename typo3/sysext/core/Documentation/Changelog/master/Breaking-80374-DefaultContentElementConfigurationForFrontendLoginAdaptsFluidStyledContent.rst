.. include:: ../../Includes.txt

=======================================================================================================
Breaking: #80374 - Default content element configuration for frontend login adapts fluid styled content
=======================================================================================================

See :issue:`80374`

Description
===========

Default configuration for the frontend login conent element was adapted to match
fluid styled content instead of css styled content by default. Css styled content
was adapted and works there as before.

Redering for css styled content
-------------------------------

.. code-block:: typoscript

   tt_content.login = COA
   tt_content.login {
      10 =< lib.stdheader
      20 =< plugin.tx_felogin_pi1
   }

Redering for fluid styled content
-------------------------------

   tt_content.login =< lib.contentElement
   tt_content.login {
      templateName = Generic
      variables {
         content =< plugin.tx_felogin_pi1
      }
   }

Impact
======

Adjustmens made manually to the typoscript rendering definition of
`tt_content.login` might not work in fluid styled content as expected.

Affected Installations
======================

Installations that are using fluid styled content and directly modify
configuration of `tt_content.login`.

Migration
=========

Manual adaption is nessesary.

.. index:: TypoScript
