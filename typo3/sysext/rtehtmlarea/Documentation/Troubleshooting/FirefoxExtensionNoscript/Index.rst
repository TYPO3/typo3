.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _firefox-extension-noscript:

Issue with Firefox extension NoScript
-------------------------------------


.. _firefox-extension-noscript-problem:

Problem:
""""""""

When the Firefox extension NoScript is installed, images served by a
server on localhost may not be displayed in the RTE.


.. _firefox-extension-noscript-solution:

Solution:
"""""""""

The problem may be solved by modifying the ABE configuration of the
NoScript extension.

Go to NoScript Options -> Advanced -> ABE -> SYSTEM .

Modify the existing ruleset so that it looks as follows:

Site LOCAL

Accept from LOCAL about:blank

Deny


