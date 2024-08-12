.. include:: /Includes.rst.txt

.. _site-sets:

=========
Site sets
=========

..  versionadded:: 13.1
    Site sets have been added to the extension :composer:`typo3/cms-fluid-styled-content`.
    See :ref:`include-site-set` on how to use them.

The extension :composer:`typo3/cms-fluid-styled-content` offers two site sets
that can be included via the Site module or required by your
:ref:`site package's site set <t3sitepackage:site_set>`.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`"Fluid Styled Content" <site-set-fluid-styled-content>`

        The site set "Fluid Styled Content" includes all TypoScript required to
        display the content elements provided by `fluid_styled_content`.

    ..  card:: :ref:`"Fluid Styled Content CSS" <site-set-fluid-styled-content-css>`

        This site set extends the :ref:`"Fluid Styled Content" <site-set-fluid-styled-content>`
        site set with default CSS.

..  toctree::
    :glob:
    :hidden:

    *
