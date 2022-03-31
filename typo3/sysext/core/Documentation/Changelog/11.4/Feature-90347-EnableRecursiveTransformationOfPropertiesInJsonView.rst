.. include:: /Includes.rst.txt

===========================================================================
Feature: #90347 - Enable recursive transformation of properties in JsonView
===========================================================================

See :issue:`90347`

Description
===========

The Extbase :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView` is now able to resolve
recursive properties of objects, e.g. directories containing directories or
comments containing comments as replies.

Examples:

1. This is for 1:1 relations, where a comment has at most 1 comment.

.. code-block:: php

   $configuration = [
       'comment' => [
           '_recursive' => ['comment']
        ]
   ];


2. This is for the more common 1:n relation in which you have lists of sub objects.

.. code-block:: php

   $configuration = [
       'directories' => [
           '_descendAll' => [
               '_recursive' => ['directories']
           ],
       ]
   ];

You can put all the other configuration like :php:`_only` or :php:`_exclude` at the same
level as :php:`_recursive` and the view will apply this for all levels.

Impact
======

Developers can now use the :php:`_recursive` property in the :php:`JsonView`
configuration in order to resolve recursive properties instead of defining each
level manually.

.. index:: ext:extbase
