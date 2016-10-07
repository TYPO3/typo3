
.. include:: ../../Includes.txt

=====================================================
Breaking: #73698 - Streamline layout of FlashMessages
=====================================================

See :issue:`73698`

Description
===========

The layout and usage of FlashMessages has been streamlined in the TYPO3 backend.
All FlashMessages in the TYPO3 backend are now rendered as <div> markup and
contain an icon, the message and an optional title.

Example:

.. code-block:: html

   <div class="alert alert-danger">
      <div class="media">
         <div class="media-left">
            <span class="fa-stack fa-lg">
               <i class="fa fa-circle fa-stack-2x"></i>
               <i class="fa fa-times fa-stack-1x"></i>
            </span>
         </div>
         <div class="media-body">
            <h4 class="alert-title">The optional title</h4>
            <p class="alert-message">The message goes here</p>
         </div>
      </div>
   </div>


FlashMessages that are used as inline notification should be removed and replaced with custom HTML code.
For the core we have defined output and usage for messages:

1) FlashMessages
----------------

FlashMessages are designed to inform a user about success or failure of an action, which was **triggered** by the user.
Example: If the user deletes a record, a FlashMessage informs the user about success or failure.
This kind of information is not static, it is a temporary and volatile information and triggered by a user action.

Keep in mind that you **must not** use HTML markup here, since this information
might be shown in a context different from HTML, like processing it via Javascript or
showing the message on the command line.

2) Callouts (InfoBox-ViewHelper)
--------------------------------
Callouts are designed to display permanent information, a very good example is the usage in the Page-Module.
If a user opens a system folder with the page module, the callout explains: 'Hey, you try to use the page module on a sys folder, please switch to the list module'.
This ViewHelper can also be used to show some help or instruction how to use a backend module.


3) Any other information
------------------------
For any other information e.g. a list of files which has changed, must be handled in the action / view of the module or plugin. This is not a use case for a FlashMessage or Callout!
Example: Display a list of a hundred files within a FlashMessage or Callout is a bad idea, build custom markup in the view to handle this kind of message.


Impact
======

Extensions which use the FlashMessageViewHelper with the default rendering will now get a list of <div>-messages instead of a <ul>-list.


Migration
=========

No migration needed, the generated output should be as expected. If the rendering is broken please consider about the correct usage of FlashMessages and read the explanation about message types above.

.. index:: Backend, Fluid
