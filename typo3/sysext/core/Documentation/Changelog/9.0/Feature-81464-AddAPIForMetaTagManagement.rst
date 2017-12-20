.. include:: ../../Includes.txt

=================================================
Feature: #81464 - Add API for meta tag management
=================================================

See :issue:`81464`

Description
===========

The page rendering class :php:`PageRenderer` now offers a concise, yet simple API to manage meta tags.

It consists of the three methods:


Setting meta tags
-----------------

:php:`PageRenderer->setMetaTag(string $type, string $name, string $content)`

Used to add or **overwrite** a given meta tag.

The parameter :php:`$type` accepts the values :php:`name`, :php:`property` or :php:`http-equiv`.
All other values result in an exception because those are not within the HTML specs.

The parameter :php:`$name` is the value of the attribute given by :php:`$type`.

For example:

.. code-block::php

    PageRenderer->setMetaTag('name', 'author', 'husel');

will result in

.. code-block::html

    <meta name="author" content="husel" />


Getting meta tags
-----------------

:php:`PageRenderer->getMetaTag(string $type, string $name)`

Used to get a given meta tag.
This is useful if your extension isn't the only player that handles meta tags. So instead of blindly overwriting other
peoples meta tags it is a good idea to check for their existence and provide a feature switch to decide which meta tag
should get rendered.


Removing meta tags
------------------

:php:`PageRenderer->removeMetaTag(string $type, string $name)`

Used to remove a meta tag from the stack.


Example
-------

.. code-block:: php

   $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
   // has meta tag been set already?
   $previouslySetMetaTag = $pageRenderer->getMetaTag('property', 'og:title');
   // take some decision here
   if (!is_array($previouslySetMetaTag) || $yourConfigSwitchToOverwriteMetaTags) {
       $pageRenderer->setMetaTag('property', 'og:title', 'My amazing title');
   }


Impact
======

Be happy with the new API.

.. index:: PHP-API, Frontend
