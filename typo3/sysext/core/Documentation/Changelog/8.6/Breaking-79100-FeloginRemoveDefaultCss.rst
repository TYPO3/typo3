.. include:: ../../Includes.txt

==================================================
Breaking: #79100 - ext:felogin: Remove default CSS
==================================================

See :issue:`79100`

Description
===========

The applied default CSS styles delivered by EXT:felogin may break the frontend output, for example
if CSS frameworks are used. The default styles need to get overridden in such case.


Impact
======

EXT:felogin doesn't add default CSS styles anymore.


Affected Installations
======================

All installations using EXT:felogin with default/non-overwritten :ts:`plugin.tx_felogin_pi1._CSS_DEFAULT_STYLE`
TypoScript setup are affected.


Migration
=========

If your frontend relies on the default EXT:felogin CSS styles, make sure to add following CSS on
your own:

.. code-block:: css

   .tx-felogin-pi1 label {
      display: block;
   }



.. index:: Frontend, ext:felogin
