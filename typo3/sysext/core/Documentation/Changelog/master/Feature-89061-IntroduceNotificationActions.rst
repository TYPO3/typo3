.. include:: ../../Includes.txt

================================================
Feature: #89061 - Introduce Notification Actions
================================================

See :issue:`89061`

Description
===========

Notifications rendered by the :js:`TYPO3/CMS/Backend/Notification` module are now able to render action buttons. Each
notification method (:js:`info()`, :js:`success()` etc) accepts an array of actions, each action is described by a
:js:`label` and a pre-defined action type, containing a callback.

However, notifications flagged with a duration will still disappear, unless an action is taken.

.. important::

   Such tasks must **never** be mandatory to be executed. This API is meant to suggest certain actions without enforcing
   them. If a user must take action immediately, consider using modals instead.

.. important::

   Due to how passed callbacks are handled, using arrow functions is **mandatory**!


Example:

.. code-block:: js

   require(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ActionEnum'], function (Notification, ActionEnum) {
     Notification.warning(
       'Beware',
       'We did some stuff that might take your intervention',
       0,
       [
         {
           label: 'Apply suggestion',
           action: {
             type: ActionEnum.IMMEDIATE,
             callback: () => {
               /* your action code */
             }
           }
         }
       ]
     );
   });


Immediate Action
----------------

An action of type :js:`immediate` is executed directly on click and closes the notification. This action type is
suitable for e.g. linking to a backend module.

Example:

.. code-block:: js

   action: {
     type: ActionEnum.IMMEDIATE,
     callback: () => {
       require(['TYPO3/CMS/Backend/ModuleMenu'], function (ModuleMenu) {
         ModuleMenu.showModule('web_layout');
       });
     }
   }


Deferred Action
---------------

An action of type :js:`deferred` is recommended when a long-lasting task is executed, e.g. an AJAX request.

The callback function must return either a resolved or rejected promise.

The :js:`deferred` action replaces the action button with a spinner icon to indicate a task will take some time. It's
still possible to dismiss a notification, which will **not** stop the execution.

Example:

.. code-block:: js

   action: {
     type: ActionEnum.DEFERRED,
     callback: () => {
       const myAction = async function() {
         return await 'something';
       }

       return myAction();
     }
   }

   action: {
     type: ActionEnum.DEFERRED,
     // do some old-fashioned jQuery stuff
     callback: () => {
       return $.ajax(/* AJAX configuration */);
     }
   }


.. index:: Backend, JavaScript, ext:backend
