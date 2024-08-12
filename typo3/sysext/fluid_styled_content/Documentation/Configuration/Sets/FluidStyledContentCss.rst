:navigation-title: Fluid Styled Content CSS

.. include:: /Includes.rst.txt

.. _site-set-fluid-styled-content-css:

===================================
Site set "Fluid Styled Content CSS"
===================================

..  versionadded:: 13.1
    Site sets have been added to the extension :composer:`typo3/cms-fluid-styled-content`.
    See :ref:`include-site-set` on how to use them.

This site set depends on the :ref:`site-set-fluid-styled-content`. Additionally it
includes the :confval:`_CSS_DEFAULT_STYLE <t3tsref:plugin-css-default-style>`.

The styles provided by this site set enable the display of settings in content
elements, including frame styles like `.frame-space-before-large`, styles
for headlines like `.ce-headline-center` and styles for images like
`.ce-intext.ce-left`.

If you do not depend on this site set you should provide the according CSS yourself
or disable all fields in the TCA table that lose their function due to missing
CSS styles.
