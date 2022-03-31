.. include:: /Includes.rst.txt

===========================================================================
Important: #91132 - Avoid JavaScript in User Settings Configuration options
===========================================================================

See :issue:`91132`

Description
===========

User Settings Configuration options for buttons `onClick` and `onClickLabels`
(used to generate inline JavaScript `onclick` event) and `confirmData.jsCodeAfterOk`
(used to execute a JavaScript callback in modal confirmations) should be omitted.

New options `clickData.eventName` and `conformationData.eventName` should be used
containing an individual event name that has to be handled individually using a
static JavaScript module.

This step is advised to reduce the amount of inline JavaScript code towards
better support for Content-Security-Policy headers.

Applications having custom changes in :php:`$GLOBALS['TYPO3_USER_SETTINGS']`
and using mentioned options `onClick*` or `confirmData.jsCodeAfterOk`.

The following example show a potential migration path to avoid inline JavaScript.

.. code-block:: php

   $GLOBALS['TYPO3_USER_SETTINGS'] = [
       'columns' => [
           'customButton' => [
               'type' => 'button',
               'onClick' => 'alert("clicked the button")',
               'confirm' => true,
               'confirmData' => [
                   'message' => 'Please confirm...',
                   'jsCodeAfterOk' => 'alert("confirmed the modal dialog")',
               ]
            ],
            // ...

The above configuration can be replace by the the following.

.. code-block:: php

   $GLOBALS['TYPO3_USER_SETTINGS'] = [
       'columns' => [
           'customButton' => [
               'type' => 'button',
               'clickData' => [
                   'eventName' => 'setup:customButton:clicked',
               ],
               'confirm' => true,
               'confirmData' => [
                   'message' => 'Please confirm...',
                   'eventName' => 'setup:customButton:confirmed',
               ]
            ],
            // ...

Events declared in corresponding `eventName` options have to be handled by
a custom static JavaScript module. Following snippets show the relevant parts:

.. code-block:: javascript

   document.querySelectorAll('[data-event-name]')
       .forEach((element: HTMLElement) => {
           element.addEventListener('setup:customButton:clicked', (evt: Event) => {
               alert('clicked the button');
           });
       });
   document.querySelectorAll('[data-event-name]')
       .forEach((element: HTMLElement) => {
           element.addEventListener('setup:customButton:confirmed', (evt: Event) => {
               evt.detail.result && alert('confirmed the modal dialog');
           });
       });

PSR-14 event :php:`\TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent` can be used
to inject a JavaScript module to handle those custom JavaScript events.


.. index:: Backend, NotScanned, ext:setup
