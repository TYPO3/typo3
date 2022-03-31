.. include:: /Includes.rst.txt

.. highlight:: php

.. _adding-buttons:

=======================
Adding button to Widget
=======================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

In order to add a button to a widget, a new dependency to an :php:class:`ButtonProviderInterface` can be added.

Template
--------

The output itself is done inside of the Fluid template, for example :file:`Resources/Private/Templates/Widget/RssWidget.html`:

.. code-block:: html

   <f:if condition="{button}">
      <a href="{button.link}" target="{button.target}" class="widget-cta">
         {f:translate(id: button.title, default: button.title)}
      </a>
   </f:if>

Configuration
-------------

The configuration is done through an configured Instance of the dependency, for example :file:`Services.yaml`:

.. code-block:: yaml

   services:
     # …

     dashboard.buttons.t3news:
       class: 'TYPO3\CMS\Dashboard\Widgets\Provider\ButtonProvider'
       arguments:
         $title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems'
         $link: 'https://typo3.org/project/news'
         $target: '_blank'

     dashboard.widget.t3news:
       class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
       arguments:
         # …
         $buttonProvider: '@dashboard.buttons.t3news'
         # …

.. program:: TYPO3\CMS\Dashboard\Widgets\Provider\ButtonProvider

.. option:: $title

   The title used for the button. E.g. an ``LLL:EXT:`` reference like
   ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems``.

.. option:: $link

   The link to use for the button. Clicking the button will open the link.

.. option:: $target

   The target of the link, e.g. ``_blank``.
   ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems``.

Implementation
--------------

An example implementation could look like this:

:file:`Classes/Widgets/RssWidget.php`::

   class RssWidget implements WidgetInterface
   {
       /**
        * @var ButtonProviderInterface|null
        */
       private $buttonProvider;

       public function __construct(
           // …
           ButtonProviderInterface $buttonProvider = null,
           // …
       ) {
           $this->buttonProvider = $buttonProvider;
       }

       public function renderWidgetContent(): string
       {
           $this->view->setTemplate('Widget/RssWidget');
           $this->view->assignMultiple([
               // …
               'button' => $this->buttonProvider,
               // …
           ]);
           return $this->view->render();
       }
   }
