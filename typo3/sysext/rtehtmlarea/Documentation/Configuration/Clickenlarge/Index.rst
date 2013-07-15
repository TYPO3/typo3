.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _clickenlarge-rendering:

Static Template: Clickenlarge Rendering
---------------------------------------

In order for the click-enlarge property of images inserted in the RTE
to be rendered on the frontend, static template «Clickenlarge
Rendering (rtehtmlarea)» must be included in the TypoScript template.
This static template must be included after static template CSS Styled
Content (css\_styled\_content).

Note that stdWrap property may be applied to the generated link tag by
configuring the property in TS template setup:

lib.parseFunc\_RTE.tags.img.postUserFunc.stdWrap.

This may be used, for examble, to add additional attributes to the
generated link tag.


