.. include:: ../../Includes.txt

===============================================
Feature: #80579 - Improved JavaScript Modal API
===============================================

See :issue:`80579`

Description
===========

To improve the usability and flexibility for a unified handling of overlays in
the backend we're opening the existing API for modals to be more flexible and
adjustable to your needs for advanced usage. With the introduction of the
advanded API it is now possible to pass configuration by json object. This
enables more easy configuation and better fallbacks if the modal is not
correctly configured.

For a unified experience all modals are now centered by default, will kept in
place automaticly and are availbale in different sized depending on your needs.
In addition to this there is also a new type for loading content into an
iframe. Buttons have now full support for the TYPO3 Icon API and data
attributes that also can set by configuration.

Advanced API
------------

Unlike the existing api functions like `Modal.confirm`, `Modal.loadUrl` or
`Modal.show`, `Modal.advanced` uses a JavaScript object instead of fixed
parameters.

.. code-block:: javascript

   require([
      'jquery',
      'TYPO3/CMS/Backend/Modal'
      ], function ($, Modal) {

         var configuration = {
            type: Modal.types.iframe,
            title: title,
            content: url,
            size: Modal.sizes.large,
            callback: function(currentModal) {
               currentModal.find('.t3js-modal-body')
                  .addClass('custom-css-class');
            }
         };
         Modal.advanced(configuration);

      });
   }


Configuration Options
---------------------

Type
^^^^

The `type` will define the behaviour of content loading, and only accepts
`Modal.types.ajax`, `Modal.types.iframe` and the default `Modal.types.default`.

.. code-block:: javascript

   var configuration = {
      type: Modal.types.iframe,
      content: url || content
   };

Modal.types.default
   Default will display static content set in the option `content`.

Modal.types.ajax
   Content will be grabbed from a url set in the option `content`

Modal.types.iframe
   Url provided in the option `content` will be loaded in a iframe in the modal.
   Also it will automaticly set the title from the contained document.


Title
^^^^^

The `title` will be display above the modal content. For the type
`Modal.types.iframe` this option will have no effect. As soon as the content
from the iframe is loaded, the title will be replaced with title of the
contained document. The default will set the title to "Information".

.. code-block:: javascript

   var configuration = {
      title: 'My Title'
   };
   Modal.advanced(configuration);


Content
^^^^^^^

The `content` accepts only strings that can be either a HTML or a url for types
`Modal.types.ajax` and `Modal.types.iframe`. The default will show a warning
that there is a possible missconfiguration of the modal.

.. code-block:: javascript

   var configurationStatic = {
      type: Modal.types.default,
      content: 'My Title'
   };
   Modal.advanced(configurationStatic);


.. code-block:: javascript

   var configurationAjax = {
      type: Modal.types.ajax,
      content: 'http://www.google.de/'
   };
   Modal.advanced(configurationAjax);


.. code-block:: javascript

   var configurationIframe = {
      type: Modal.types.iframe,
      content: 'http://www.google.de/'
   };
   Modal.advanced(configurationIframe);


Severity
^^^^^^^^

Severity is used to change the appearance of the modal window to represent a
contexual state like success, information, warning or danger. The default is
`Severity.notice`. Only options provided by the `Severity` object will be
accepted.

.. code-block:: javascript

   var configuration = {
      severity: Severity.info,
   };
   Modal.advanced(configuration);


Buttons
^^^^^^^

Defined buttons will be display displayed at the bottom of the modal window.
The configuration accepts an array of single button defintions.

.. code-block:: javascript

   var configuration = {
      buttons: [
         {
            text: 'Save changes',
            name: 'save',
            icon: 'actions-document-save'
            active: true,
            btnClass: 'btn-primary',
            dataAttributes: {
               action: 'save'
            },
            trigger: function() {
               Modal.currentModal.trigger('modal-dismiss');
            }
         }
      ]
   };
   Modal.advanced(configuration);


text
   Text that will be displayed in the button

name
   Value of the name attribute of the button

icon
   Name of the icon that will be displayed in front of the text

active
   Activated button after opening the modal window

btnClass
   Additional css class that will be added to the button

dataAttributes
   Object of data attributes that will be added to the button

trigger
   Callback function that will be triggered then the button is clicked


Style
^^^^^

The `style` option will change the appereance of the modal like the `severity`
buth without contextual meaning. The default option is `Modal.styles.light`.
The second available option is `Modal.styles.dark` will override all contextual
stylings.

.. code-block:: javascript

   var configuration = {
      style: Modal.styles.default
   }
   Modal.advanced(configuration);


Size
^^^^

While the modal itself adapts to the window, there are several options available
to limit the maximal size of the modal. The sizes `Modal.sizes.small` and the
default `Modal.sizes.default` will automaticly adapt to the content and are only
limited to the width of the modal. `Modal.sizes.large` and `Modal.sizes.full`
are designed to contain a undefined lenth of content in a fixed sized modal.
These are suited best for `Modal.types.ajax` or `Modal.types.iframe` content.

.. code-block:: javascript

   var configuration = {
      size: Modal.sizes.large
   }
   Modal.advanced(configuration);


Modal.sizes.small
   Limited to 400px width

Modal.sizes.default
   Limited to 600px width

Modal.sizes.large
   Limited to 800px width and 600px height

Modal.sizes.full
   Limited to 1800px width and 1200px height


Additional CSS Classes
^^^^^^^^^^^^^^^^^^^^^^

The option `additionalCssClasses` accepts an array of css classes that will be
added to the modal frame.

.. code-block:: javascript

   var configuration = {
      additionalCssClasses: [
         'class1',
         'class2'
      ]
   }
   Modal.advanced(configuration);


Callback
^^^^^^^^

Callback function that will be called after the modal is proccessed.

.. code-block:: javascript

   var configuration = {
      callback: function(currentModal) {
         currentModal.find('.t3js-modal-body')
            .addClass('custom-css-class');
      }
   }
   Modal.advanced(configuration);


Callback after ajax processing
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Callback function that will be called after the ajax call has been done and
the response added to the desired location. This option is only available for
type `Modal.types.ajax`.

.. code-block:: javascript

   var configuration = {
      type: Modal.types.ajax,
      ajaxCallback: function() {
         do();
      }
   }
   Modal.advanced(configuration);


Target for ajax response
^^^^^^^^^^^^^^^^^^^^^^^^

The ajax response will be added to the body of the particular modal window by
default but can be set to a different selector if nessesary. This option is only
available for type `Modal.types.ajax`.

.. code-block:: javascript

   var configuration = {
      type: Modal.types.ajax,
      ajaxTarget: '.t3js-modal-footer'
   }
   Modal.advanced(configuration);


.. index:: JavaScript
