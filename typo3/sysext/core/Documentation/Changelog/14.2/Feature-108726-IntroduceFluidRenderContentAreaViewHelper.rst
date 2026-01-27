..  include:: /Includes.rst.txt

..  _feature-108726-1769071158:

==================================================================
Feature: #108726 - Introduce Fluid f:render.contentArea ViewHelper
==================================================================

See :issue:`108726`

Description
===========

Instead of using the :html:`<f:cObject>` and :html:`<f:for>` ViewHelpers to render content areas,
the new :html:`<f:render.contentArea>` ViewHelper can be used.

It allows rendering content areas while enabling other extensions to modify the output via PSR-14 EventListeners.


This is especially useful for adding debugging wrappers or additional HTML structure
around content areas.

By default, the ViewHelper renders the content area as-is, but EventListeners
can listen to the :php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedContentAreaEvent` and modify the output.

You need to use the `PAGEVIEW` config like this:
..  code-block:: typoscript

    page = PAGE
    page.10 = PAGEVIEW
    page.10.paths.10 = EXT:my_site_package/Resources/Private/Templates/

..  code-block:: html
    :caption: MyPage.fluid.html

    <f:render.contentArea contentArea="{content.left}"/>
    or
    {content.left -> f:render.contentArea()}


Impact
======

Theme creators are encouraged to use the :html:`<f:render.contentArea>` ViewHelper
to allow other extensions to modify the output via EventListeners.


..  index:: Frontend, ext:fluid
