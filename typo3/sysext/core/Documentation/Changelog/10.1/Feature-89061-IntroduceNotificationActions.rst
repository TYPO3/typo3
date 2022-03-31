.. include:: /Includes.rst.txt

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
   them. If a user is supposed to take immediate actions consider using modals instead.


Example:

.. code-block:: js

   require([
     'TYPO3/CMS/Backend/ActionButton/ImmediateAction',
     'TYPO3/CMS/Backend/ActionButton/DeferredAction',
     'TYPO3/CMS/Backend/Notification'
   ], function(ImmediateAction, DeferredAction, Notification) {
     const immediateActionCallback = new ImmediateAction(function () { /* your action code */ });
     Notification.info(
       'Great! We are almost done here...',
       'Common default settings have been applied based on your previous input.',
       0,
       [
         {label: 'Show settings', action: immediateActionCallback}
       ]
     );
   });


ImmediateAction
---------------

An action of type :js:`ImmediateAction` (:js:`TYPO3/CMS/Backend/ActionButtons/ImmediateAction`) is executed directly on
click and closes the notification. This action type is suitable for e.g. linking to a backend module.

The class accepts a callback method executing very simple logic.

Example:

.. code-block:: js

   const immediateActionCallback = new ImmediateAction(function () {
     require(['TYPO3/CMS/Backend/ModuleMenu'], function (ModuleMenu) {
       ModuleMenu.showModule('web_layout');
     });
   });


DeferredAction
--------------

An action of type :js:`DeferredAction` (:js:`TYPO3/CMS/Backend/ActionButtons/DeferredAction`) is recommended when a
long-lasting task is executed, e.g. an AJAX request.

This class accepts a callback method which must return either a resolved or rejected promise.

The :js:`DeferredAction` replaces the action button with a spinner icon to indicate a task will take some time. It's
still possible to dismiss a notification, which will **not** stop the execution.

Example:

.. code-block:: js

   const deferredActionCallback = new DeferredAction(function () {
     const myAction = async function() {
       return await 'something';
     }

     return myAction();
   });

   const anotherDeferredActionCallback = new DeferredAction(function () {
     // do some old-fashioned jQuery stuff
     return Promise.resolve($.ajax(/* AJAX configuration */));
   });


.. index:: Backend, JavaScript, ext:backend
