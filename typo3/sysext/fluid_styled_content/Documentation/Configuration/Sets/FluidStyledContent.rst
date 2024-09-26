:navigation-title: Fluid Styled Content

.. include:: /Includes.rst.txt

.. _site-set-fluid-styled-content:

===============================
Site set "Fluid Styled Content"
===============================

..  versionadded:: 13.1
    :ref:`Site sets <t3coreapi:site-sets>` have been added to the extension :composer:`typo3/cms-fluid-styled-content`.
    See :ref:`include-site-set` on how to use them.

The site set "Fluid Styled Content" includes all TypoScript required to
display the :ref:`content elements <content-elements>` provided by
:composer:`typo3/cms-fluid-styled-content`.

Some settings in these content elements like image positions or space settings
require certain CSS styles. If you want to use the default CSS styles provided
by TYPO3, include :ref:`site-set-fluid-styled-content-css` in addition to this
site set.

If you depend on this site set directly instead of :ref:`site-set-fluid-styled-content-css`
you should provide the missing CSS yourself or disable all `tt_content` fields
via page TSconfig that lose their function due to missing CSS.

..  _site-set-fluid-styled-content-settings:

Settings provided by site set "Fluid Styled Content"
====================================================

These settings can be adjusted in the :ref:`settings-editor`.

..  typo3:site-set-settings:: PROJECT:/Configuration/Sets/FluidStyledContent/settings.definitions.yaml
    :name: fluid-styled-content
    :type:
    :Label: Settings of "Fluid Styled Content"
