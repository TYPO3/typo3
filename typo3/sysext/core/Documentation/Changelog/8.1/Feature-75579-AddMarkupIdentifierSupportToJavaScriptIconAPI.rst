
.. include:: /Includes.rst.txt

====================================================================
Feature: #75579 - Add markupIdentifier support to JavaScript IconAPI
====================================================================

See :issue:`75579`

Description
===========

It is now possible to request alternative rendering methods also through the
JavaScript IconAPI for the backend. A new parameter has been added to the `getIcon`
function that now accepts the `markupIdentifier` for alternative rendering output,
as it's also possible within PHP.

Currently this is only used by the `SvgIconProvider` to deliver inline-SVGs
instead of linking them in an `img` tag.

Example 1: default, without alternativeMarkup

.. code-block:: javascript

   require(['TYPO3/CMS/Backend/Icons'], function(Icons) {
      var iconName = 'actions-view-list-collapse';
      Icons.getIcon(iconName, Icons.sizes.small).done(function(icon) {
         console.log(icon);
      });
   });

Example 2: with alternativeMarkup = inline

.. code-block:: javascript

   require(['TYPO3/CMS/Backend/Icons'], function(Icons) {
      var iconName = 'actions-view-list-collapse';
      Icons.getIcon(iconName, Icons.sizes.small, null, null, 'inline').done(function(icon) {
         console.log(icon);
      });
   });

.. index:: JavaScript, Backend
