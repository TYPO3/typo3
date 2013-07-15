.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _anchor-accessibility:

Configuring the anchor accessibility feature
--------------------------------------------

The anchor accessibility feature allows to attach special
accessibility features to CSS classes when they are applied to links
with the TYPO3 element browser. For example, icons may be inserted in
front or at the end of links when configured classes are assigned to
the links.

The Extension Manager must be used to enable the feature.

The TYPO3 element browser must be enabled in the 'Insert/Modify link'
dialogue by setting property buttons.link.TYPO3Browser.disabled to 0
in Page TSconfig. The TYPO3 element browser is enabled by default in
the backend, but never available in the frontend.

The classes should first be defined in the CSS file specified by
RTE.default.contentCSS.

The classes should be part of the list specified by property
RTE.default.classesAnchor.

The accessibility features attached to the classes are specified by
property RTE.classesAnchor.


