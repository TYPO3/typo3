:navigation-title: Custom link handler

..  include:: /Includes.rst.txt
..  _linktype-custom-linkhandler:

====================================================
Validate links of a custom link handler (`t3://...`)
====================================================

The link validator does not scan the content itself. It evaluates the result of
the soft reference parsers configured in the TCA of a field, for example
:php:`typolink_tag` for :sql:`tt_content.bodytext` and :php:`typolink` for link
fields like :sql:`tt_content.header_link`.

Both parsers know the core link types such as pages, files, URLs and records.
For all other link types, for example a custom link handler providing links like
`t3://shop?identifier=123`, they dispatch the PSR-14 event
:php:`\TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent`.

To validate such links, two parts are needed:

1.  An event listener which turns the link into an entry for the link validator.
2.  A :ref:`linktype <linktype-implementation>` which checks the entry.

No custom soft reference parser and no change of the TCA are required, as long
as the field already uses :php:`typolink_tag` or :php:`typolink`.

..  _linktype-custom-linkhandler-listener:

Event listener
==============

The listener returns an element whose :php:`subst` array describes the link. The
:php:`type` is matched by the linktype, the :php:`tokenValue` is passed to
:php:`LinktypeInterface->checkLink()` as the URL to check.

..  literalinclude:: _CustomLinkHandlerListener.php.inc
    :language: php
    :caption: packages/my_extension/Classes/EventListener/AppendShopLinkElements.php

Calling :php:`addElements()` marks the event as resolved. Links without a
resolved event are skipped by :php:`typolink_tag` and reported as
"Couldn't decide typolink mode." by :php:`typolink`.

..  _linktype-custom-linkhandler-linktype:

Linktype
========

..  literalinclude:: _CustomLinkHandlerLinktype.php.inc
    :language: php
    :caption: packages/my_extension/Classes/Linktype/ShopLinktype.php

The default :php:`AbstractLinktype->fetchType()` selects the linktype if the
:php:`type` of the entry equals the identifier of the linktype, here `shop`.
Register and activate the linktype as described in
:ref:`linktype-implementation`:

..  code-block:: typoscript
    :caption: packages/my_extension/Configuration/page.tsconfig

    mod.linkvalidator.linktypes = db,file,external,shop

..  _linktype-custom-linkhandler-other-fields:

Fields without the typolink soft reference parsers
==================================================

If a field does not use :php:`typolink_tag` or :php:`typolink`, add one of them
to the :php:`softref` option of the field in the TCA, for example:

..  code-block:: php
    :caption: packages/my_extension/Configuration/TCA/Overrides/tt_content.php

    $GLOBALS['TCA']['tt_content']['columns']['my_field']['config']['softref'] = 'typolink';
