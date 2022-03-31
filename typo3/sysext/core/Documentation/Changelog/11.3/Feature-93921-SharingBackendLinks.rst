.. include:: /Includes.rst.txt

=======================================
Feature: #93921 - Sharing backend links
=======================================

See :issue:`93921`

Description
===========

With the introduction of backend URL rewrites in :issue:`93048` and
the backend module web component router in :issue:`93988`, it's finally
possible to share backend URLs between each other.

To ease the use of this, the
:php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton` is
extended for
a new option :php:`$copyUrlToClipboard`, which defaults to :php:`true`.
This option extends the shortcut button in the module header of a backend
module. Therefore, the button's icon is also changed. On click, a dropdown
opens, including the additional possibility to copy the current backend URL
directly to the operating system's clipboard, next to the already existing
bookmark option.

For the dropdown button, a new icon :php:`share-alt` is registered, which can
be used through the :php:`IconFactory`.

In case you are using the shortcut API in your custom backend module and
don't want to use this additional option, you can disabled it by setting
:php:`$shortcutButton->setCopyUrlToClipboard(false)`. If disabled, the
shortcut button is rendered with the same behaviour as before.

.. note::

   Since both ViewHelpers, :html:`<be:moduleLayout.button.shortcutButton>`
   as well as :html:`<f:be.buttons.shortcut>` are deprecated, the new option
   is not available for those. In case you are currently using one of those
   ViewHelpers, but still want to profit from the new option in your custom
   backend modules, you have to create the shortcut button in the corresponding
   controller using the shortcut API. This will anyways be required in TYPO3 v12.

Besides the new option for the :php:`ShortcutButton`, a new constant
:php:`SHAREABLE_URL` is available in the :php:`UriBuilder`. It can be
used as value for the :php:`$referenceType` parameter, which is available
for most of the "buildUri" methods, for example :php:`UriBuilder->buildUriFromRoute()`.

.. code-block:: php

   $uri = $uriBuilder->buildUriFromRoute($routeName, $arguments, UriBuilder::SHAREABLE_URL);

The above example will return an absolute URL without the automatically
created token parameter.

Impact
======

If the new option is enabled, instead of the shortcut button, a dropdown
menu is displayed in the module header, including two options:

*  Option to add a shortcut to the current page
*  Option to copy the URL of the current page to the operating system's clipboard

When setting :php:`UriBuilder::SHAREABLE_URL` as :php:`$referenceType` in
one of the "buildUri" methods supporting this parameter, a shareable URL
will be returned.

.. index:: Backend, ext:backend
