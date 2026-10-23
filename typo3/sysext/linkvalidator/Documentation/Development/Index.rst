:navigation-title: Development

..  include:: /Includes.rst.txt
..  _development:

========================================
Linkvalidator information for developers
========================================

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Custom linktypes <linktype-implementation>`

        The LinkValidator uses so called `linktypes` to check for different
        types of links. Learn how to implement a custom linktype within
        an extension.

    ..  card:: :ref:`Custom link handler <linktype-custom-linkhandler>`

        Validate links of a custom link handler like `t3://shop?...` with a
        PSR-14 event listener and a linktype.

    ..  card:: :ref:`API <api>`

        Overview of important classes and interfaces in the public API.

..  toctree::
    :hidden:
    :titlesonly:

    LinkTypeImplementation
    CustomLinkHandler
    Api/Index
