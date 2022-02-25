.. include:: /Includes.rst.txt


.. _apireference-formeditor:

Form editor
===========


.. _apireference-formeditor-hooks:

Hooks
-----

EXT:form implements various hooks so that forms can be manipulated while being
created or saved.


.. _apireference-formeditor-hooks-beforeformcreate:

beforeFormCreate
^^^^^^^^^^^^^^^^

The form manager calls the 'beforeFormCreate' hook.


.. _apireference-formeditor-hooks-beforeformcreate-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformcreate-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormCreate(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }


.. _apireference-formeditor-hooks-beforeformduplicate:

beforeFormDuplicate
^^^^^^^^^^^^^^^^^^^

The form manager call the 'beforeFormDuplicate' hook.


.. _apireference-formeditor-hooks-beforeformduplicate-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformduplicate-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormDuplicate(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }


.. _apireference-formeditor-hooks-beforeformdelete:

beforeFormDelete
^^^^^^^^^^^^^^^^

The form manager call the 'beforeFormDelete' hook.


.. _apireference-formeditor-hooks-beforeformdelete-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformdelete-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @return void
    */
   public function beforeFormDelete(string $formPersistenceIdentifier)
   {
   }


.. _apireference-formeditor-hooks-beforeformsave:

beforeFormSave
^^^^^^^^^^^^^^

The form editor call the 'beforeFormSave' hook.


.. _apireference-formeditor-hooks-beforeformsave-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormSave'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformsave-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormSave(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }



.. _apireference-formeditor-stage:

Stage
-----


.. _apireference-formeditor-stage-commonabstractformelementtemplates:

Common abstract view form element templates
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The basic idea of the ``abstract view`` is to give a quick overview of the
configuration of form elements, without having to click them in order to view
the detailed configuration in the ``Inspector``. The ``form editor`` requires
for each form element an inline HTML template and the corresponding JavaScript
code. Information matching inline HTML templates to the appropriate form
elements must be configured within :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
At this point, the key identifying the form element follows a convention:
``FormElement-<formElementTypeIdentifier>``. The value for the key tells the
``form editor`` which inline HTML template should be loaded for the respective
form element. This template is then cloned via JavaScript, brought to life
using the form element configuration and shown in the ``Stage`` component.

You can read about how particular form elements are mapped to inline HTML
templates and how the corresponding JavaScript code are executed :ref:`here <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`.

The form element inline HTML templates and the corresponding JavaScript code
are configured for reuse. In this way, most form elements you create should be
able to access the components delivered in EXT:form, without requiring separate
implementations (at least we hope so). For your own implementations, study
EXT:form stage templates, which is found under ``Resources/Private/Backend/Partials/FormEditor/Stage/*``.
The corresponding JavaScript code is found under ``Resources/Public/JavaScript/Backend/FormEditor/StageComponent.js``.
The method ``_renderTemplateDispatcher()`` shows, which methods will be used to
render the respective form elements.

Essentially, two different inline HTML templates exists that can be rendered
with two different JavaScript methods, which are described below. The other
inline HTML templates are almost all versions of these two basic variants and
show extra/ other form-element information. The same applies to the
corresponding JavaScript codes.


.. _apireference-formeditor-stage-commonabstractformelementtemplates-simpletemplate:

Stage/SimpleTemplate
++++++++++++++++++++

This template displays the ``label`` property of the form element. Depending on
the JavaScript rendering method used, a validator icon will be shown on the
right as soon as a validator is added to the form element. In this case, the
used validator labels are likewise displayed, if the form element is selected
and/ or the cursor hovers over the form element. This template should generally
be enough for all possible, self-defined form elements.

The ``Stage/SimpleTemplate`` can then :ref:`be rendered <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`
with the method ``getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators()``.


.. _apireference-formeditor-stage-commonabstractformelementtemplates-selecttemplate:

Stage/SelectTemplate
++++++++++++++++++++

This template behaves like the ``Stage/SimpleTemplate`` except that it also
shows the chosen options labels of the form elements. This is naturally only
possible for form elements that have ``properties.options.*`` values, e.g.
``MultiCheckbox``:

.. code-block:: yaml

       type: MultiCheckbox
       identifier: multicheckbox-1
       label: 'Multi checkbox'
       properties:
         options:
           value1: label1
           value2: label2

The template will now list 'label1' and 'label2'.

You can copy this template variant for your own form element, if that form-
element template also lists array values, which, however, are not found under
``properties.options.*``. For this purpose, the 'Stage/FileUploadTemplate' is
an example. It is basically the 'Stage/SelectTemplate' template, with one
altered property.

In the ``FileUpload`` form element, multiple property values are available
under ``properties.allowedMimeTypes.*`` as an array.

.. code-block:: yaml

       type: FileUpload
       identifier: fileupload-1
       label: 'File upload'
       properties:
         saveToFileMount: '1:/user_upload/'
         allowedMimeTypes:
           - application/msexcel
           - application/pdf

Stage/SelectTemplate

.. code-block:: html

   <div data-identifier="multiValueContainer" data-template-property="properties.options">

Stage/FileUploadTemplate

.. code-block:: html

   <div data-identifier="multiValueContainer" data-template-property="properties.allowedMimeTypes">

``data-template-property`` contains the path to the property, which is to be
read out of the form element and then shown in the template.

The ``Stage/SelectTemplate`` can then :ref:`be rendered <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`
with the method ``getFormEditorApp().getViewModel().getStage().renderSelectTemplates()``.


.. _apireference-formeditor-basicjavascriptconcepts:

Basic JavaScript Concepts
-------------------------


.. _apireference-formeditor-basicjavascriptconcepts-events:

Events
^^^^^^

EXT:form implements the ``publish/subscribe pattern`` to put the event handling
into effect. To learn more about this pattern, you should read
https://addyosmani.com/resources/essentialjsdesignpatterns/book/.
Note that the order of the subscriber is not manipulable and that information
flow between the subscribers does not exist. All events must be asynchronously
designed.

Publish an event:

.. code-block:: javascript

   getPublisherSubscriber().publish('eventname', [argumentToPublish1, argumentToPublish2, ...]);

Subscribe to an event:

.. code-block:: javascript

   var subscriberToken = getPublisherSubscriber().subscribe('eventname', function(topic, args) {
       // args[0] = argumentToPublish1
       // args[1] = argumentToPublish2
       // ...
   });

Unsubscribe an event subscriber:

.. code-block:: javascript

   getPublisherSubscriber().unsubscribe(subscriberToken);

EXT:form itself publishes and subscribes to the following events:


.. _apireference-formeditor-basicjavascriptconcepts-events-ajax-beforesend:

ajax/beforeSend
+++++++++++++++

Each Ajax request is called before this event is sent. EXT:form uses this event
to display the spinner icon on the save button.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('ajax/beforeSend', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-ajax-complete:

ajax/complete
+++++++++++++

Each Ajax request is called after the end of this event. EXT:form uses this
event to remove the spinner icon on the save button.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('ajax/complete', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-error:

core/ajax/error
+++++++++++++++

This event is called if the Ajax request, which is used to save the form or to
render the current page of the form in the ``preview view``, fails. EXT:form
uses this event to show an error message as a flash message and to show the
received error text in the ``preview view``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = jqXHR
    *              args[1] = textStatus
    *              args[2] = errorThrown
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/error', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-renderformdefinitionpage-success:

core/ajax/renderFormDefinitionPage/success
++++++++++++++++++++++++++++++++++++++++++

This event is called if the Ajax request that is used to render the current
page of the form in the ``preview view`` was successful. EXT:form uses this
event to display the rendered form in the ``preview view``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = html
    *              args[1] = pageIndex
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/renderFormDefinitionPage/success', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-saveformdefinition-success:

core/ajax/saveFormDefinition/success
++++++++++++++++++++++++++++++++++++

This event is called if the Ajax request that is used to save the form was
successful. EXT:form uses this event to display a success message as a flash
message. The ``form editor`` is also informed that no unsaved content currently
exists.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = html
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/saveFormDefinition/success', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-applicationstate-add:

core/applicationState/add
+++++++++++++++++++++++++

The addition/ deletion and movement of form elements und property collection
elements (validators/ finishers) is saved in an internal stack so that the
undo/ redo function can be implemented. This event is called whenever the
current state is added to the stack. EXT:form uses this event to reset the
enabled/ disabled state of the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = applicationState
    *              args[1] = stackPointer
    *              args[2] = stackSize
    * @return void
    */
   getPublisherSubscriber().subscribe('core/applicationState/add', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-currentlyselectedformelementchanged:

core/currentlySelectedFormElementChanged
++++++++++++++++++++++++++++++++++++++++

The method ``getFormEditorApp().setCurrentlySelectedFormElement()`` tells the
``form editor`` which form element should currently be dealt with. This method
calls this event at the end.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('core/currentlySelectedFormElementChanged', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-formelement-somepropertychanged:

core/formElement/somePropertyChanged
++++++++++++++++++++++++++++++++++++

Each :ref:`FormElement model<apireference-formeditor-basicjavascriptconcepts-formelementmodel>`
can write properties into the ``FormElement model`` through the methods ``get``
and ``set``. Each property path can register an event name for the publisher
through the method ``on``. This event is then always called when a property
path is written via ``set``. Read :ref:`FormElement model<concepts-formeditor-basicjavascriptconcepts-formelementmodel>`
for more information. EXT:form automatically registers for all known property
paths of a form element the event ``core/formElement/somePropertyChanged``.
This means that every property written via ``set`` calls this event. Among
other things, EXT:form uses this event for, for example, updating the label of
a form element in other components (e.g. ``Tree`` component ) when this label
is changed. Furthermore, any validation errors from form element properties
are indicated by this event in the ``Tree`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = propertyPath
    *              args[1] = value
    *              args[2] = oldValue
    *              args[3] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('core/formElement/somePropertyChanged', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-moved:

view/collectionElement/moved
++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().movePropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is moved.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedCollectionElementIdentifier
    *              args[1] = previousCollectionElementIdentifier
    *              args[2] = nextCollectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/moved', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-new-added:

view/collectionElement/new/added
++++++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().createAndAddPropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is created and added.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    *              args[3] = collectionElementConfiguration
    *              args[4] = referenceCollectionElementIdentifier
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/new/added', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-removed:

view/collectionElement/removed
++++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().removePropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is removed.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/removed', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-inserted:

view/formElement/inserted
+++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().createAndAddFormElement()`` and
the event :ref:`view/insertElements/perform/after<apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-after>`
call this event at the end. EXT:form uses this event to set the current
to-be-processed form element (``getFormEditorApp().setCurrentlySelectedFormElement()``)
and to re-render the ``Tree``, ``Stage`` and ``Inspector`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = newFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/inserted', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-moved:

view/formElement/moved
++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().moveFormElement()`` calls this
event at the end.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/moved', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-removed:

view/formElement/removed
++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().removeFormElement()`` calls this
event at the end. EXT:form uses this event to set the current to-be-processed
form element (``getFormEditorApp().setCurrentlySelectedFormElement()``) and to
re-render the ``Tree``, ``Stage`` and ``Inspector`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = parentFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/removed', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-close-clicked:

view/header/button/close/clicked
++++++++++++++++++++++++++++++++

The onClick event of the "Close" button in the ``form editor's`` header section
calls this event. EXT:form uses this event to display a warning message in case
there are unsaved changes.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/close/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-newpage-clicked:

view/header/button/newPage/clicked
++++++++++++++++++++++++++++++++++

The onClick event of the "new page" button in the ``form editor's`` header
section calls this event. EXT:form uses this event to display the "new page"
dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/newPage/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-save-clicked:

view/header/button/save/clicked
+++++++++++++++++++++++++++++++

The onClick event of the "save" button in the ``form editor's`` header section
calls this event. EXT:form uses this event either to display a dialog box with
the element in question (if there are validation errors) or to save the ``form
definition`` (if there are no validation errors).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/save/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-formsettings-clicked:

view/header/formSettings/clicked
++++++++++++++++++++++++++++++++

The onClick event of the "settings"  button in the ``form editor's`` header
section calls this event. EXT:form uses this event to select the root form
element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/formSettings/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-after:

view/insertElements/perform/after
+++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if "After" in the "Create new element" split button in the form-element toolbar for composite elements (e.g. fieldset) is clicked.
- if the "Create new element" button in the form-element toolbar for non-composite elements is clicked.

EXT:form uses this event to create a new form element (``getFormEditorApp().getViewModel().createAndAddFormElement()``)
and then move (``getFormEditorApp().getViewModel().moveFormElement()``) it
below the currently selected element (sibling). At the end of this event, the
event :ref:`view/formElement/inserted<apireference-formeditor-basicjavascriptconcepts-events-view-formelement-inserted>`
is called. The event ``view/formElement/inserted`` in ``getFormEditorApp().getViewModel().createAndAddFormElement()``
was previously deactivated.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/after', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-bottom:

view/insertElements/perform/bottom
++++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if, in the ``abstract view`` mode, the "Create new element" button at the end of the ``Stage`` component is clicked.

EXT:form uses this event to create a new form element (``getFormEditorApp().getViewModel().createAndAddFormElement()``).
This element is always created as the last element of the currently selected
page.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/bottom', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-inside:

view/insertElements/perform/inside
++++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if "Inside" in the "Create new element" split button in the form-element toolbar for composite elements (e.g. fieldset) is clicked.

EXT:form uses this event to create a new form element as a child element of the
currently selected element (``getFormEditorApp().getViewModel().createAndAddFormElement()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/inside', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertpages-perform:

view/insertPages/perform
++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a page
element:

- if the "Create new page" icon in the header section is clicked.
- if the "Create new page" button in the ``Tree`` component is clicked.

EXT:form uses this event to create a new page after the currently selected page
(``getFormEditorApp().getViewModel().createAndAddFormElement()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertPages/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-existing-selected:

view/inspector/collectionElement/existing/selected
++++++++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editors`` :ref:`ValidatorsEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.finisherseditor>`
and :ref:`FinishersEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.validatorseditor>`
are used to display the available validators/ finishers for a form element as a
select box. Furthermore, these ``inspector editors`` indicate that in the
``form definition``, validators/ finishers for the currently selected element
already exist. This occurs through the event ``view/inspector/collectionElement/existing/selected``.
EXT:form uses this event to render these validators/ finishers and their
tentatively configured ``inspector editors`` (``getFormEditorApp().getViewModel().renderInspectorCollectionElementEditors()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElement/existing/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-new-selected:

view/inspector/collectionElement/new/selected
+++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editors`` :ref:`ValidatorsEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.finisherseditor>`
and :ref:`FinishersEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.validatorseditor>`
are used to display the available validators/ finishers for a form element as a
select box. The onChange event of the select box then calls this event. In
addition, the ``inspector editor`` :ref:`RequiredValidatorEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor>`
calls this event when a checkbox is chosen. EXT:form uses this event to add and
render the validator/ finisher of the ``form definition`` via ``getFormEditorApp().getViewModel().createAndAddPropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElement/new/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-dnd-update:

view/inspector/collectionElements/dnd/update
++++++++++++++++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/inspector/collectionElements/dnd/update`` event if a property
collection element in the ``Inspector`` component is sorted. EXT:form uses this
event to move the validator/ finisher in the ``form definition`` via the method
``getFormEditorApp().getViewModel().movePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedCollectionElementIdentifier
    *              args[1] = previousCollectionElementIdentifier
    *              args[2] = nextCollectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElements/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform:

view/inspector/editor/insert/perform
++++++++++++++++++++++++++++++++++++

The methods ``getFormEditorApp().getViewModel().renderInspectorEditors()`` (to
render all ``inspector editors`` for a form element) and ``getFormEditorApp().getViewModel().renderInspectorCollectionElementEditors()``
(to render the ``inspector editors`` for a validator/ finisher) call this event
at the end. Strictly speaking, the ``Inspector`` component in the method
``_renderEditorDispatcher()`` calls this event.
Each ``inspector editor`` has the property :ref:`templateName <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.templatename>`,
which gives the ``form editor`` two pieces of information. On the one hand the
``templateName`` must match with a key within the :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
The ``form editor`` can consequently load a corresponding inline HTML template
for the ``inspector editor``. On the other hand, the ``Inspector`` component
must be told which JavaScript code should be executed for the
``inspector editor``. For the ``inspector editors`` delivered with EXT:form,
this occurs within the method ``_renderEditorDispatcher()``.
An existing hard-coded list of known ``inspector editors`` determines, by means
of the property ``templateName``, which corresponding JavaScript method should
be executed for the ``inspector editor``. At the end, the event
``view/inspector/editor/insert/perform`` is called. If you wish to implement
your own ``inspector editor``, you can use this event to execute in
:ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`.
the corresponding JavaScript code, with the help of the property
``templateName``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = editorConfiguration
    *              args[1] = editorHtml
    *              args[2] = collectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
   });

A simple example that registers a custom ``inspector editor`` called 'Inspector-MyCustomInspectorEditor' and adds it to text form elements:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formEditor:
               dynamicJavaScriptModules:
                 additionalViewModelModules:
                   10: 'TYPO3/CMS/MySitePackage/Backend/FormEditor/ViewModel'
               formEditorFluidConfiguration:
                 partialRootPaths:
                   100: 'EXT:my_site_package/Resources/Private/Backend/Partials/FormEditor/'
               formEditorPartials:
                 Inspector-MyCustomInspectorEditor: 'Inspector/MyCustomInspectorEditor'
             formElementsDefinition:
               Text:
                 formEditor:
                   editors:
                     600:
                       templateName: 'Inspector-MyCustomInspectorEditor'
                       ...

.. code-block:: javascript
   :emphasize-lines: 107-116

   /**
    * Module: @typo3/my-site-package/backend/form-editor/view-model
    */
   define(['jquery',
           'TYPO3/CMS/Form/Backend/FormEditor/Helper'
           ], function($, Helper) {
           'use strict';

       return (function($, Helper) {

           /**
            * @private
            *
            * @var object
            */
           var _formEditorApp = null;

           /**
            * @private
            *
            * @return object
            */
           function getFormEditorApp() {
               return _formEditorApp;
           };

           /**
            * @private
            *
            * @return object
            */
           function getPublisherSubscriber() {
               return getFormEditorApp().getPublisherSubscriber();
           };

           /**
            * @private
            *
            * @return object
            */
           function getUtility() {
               return getFormEditorApp().getUtility();
           };

           /**
            * @private
            *
            * @param object
            * @return object
            */
           function getHelper() {
               return Helper;
           };

           /**
            * @private
            *
            * @return object
            */
           function getCurrentlySelectedFormElement() {
               return getFormEditorApp().getCurrentlySelectedFormElement();
           };

           /**
            * @private
            *
            * @param mixed test
            * @param string message
            * @param int messageCode
            * @return void
            */
           function assert(test, message, messageCode) {
               return getFormEditorApp().assert(test, message, messageCode);
           };

           /**
            * @private
            *
            * @return void
            * @throws 1491643380
            */
           function _helperSetup() {
               assert('function' === $.type(Helper.bootstrap),
                   'The view model helper does not implement the method "bootstrap"',
                   1491643380
               );
               Helper.bootstrap(getFormEditorApp());
           };

           /**
            * @private
            *
            * @return void
            */
           function _subscribeEvents() {
               /**
                * @private
                *
                * @param string
                * @param array
                *              args[0] = editorConfiguration
                *              args[1] = editorHtml
                *              args[2] = collectionElementIdentifier
                *              args[3] = collectionName
                * @return void
                */
               getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
                   if (args[0]['templateName'] === 'Inspector-MyCustomInspectorEditor') {
                       renderMyCustomInspectorEditor(
                           args[0],
                           args[1],
                           args[2],
                           args[3]
                       );
                   }
               });
           };

           /**
            * @private
            *
            * @param object editorConfiguration
            * @param object editorHtml
            * @param string collectionElementIdentifier
            * @param string collectionName
            * @return void
            */
           function renderMyCustomInspectorEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
               // do cool stuff
           });

           /**
            * @public
            *
            * @param object formEditorApp
            * @return void
            */
           function bootstrap(formEditorApp) {
               _formEditorApp = formEditorApp;
               _helperSetup();
               _subscribeEvents();
           };

           /**
            * Publish the public methods.
            * Implements the "Revealing Module Pattern".
            */
           return {
               bootstrap: bootstrap
           };
       })($, Helper);
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-removecollectionelement-perform:

view/inspector/removeCollectionElement/perform
++++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editor`` :ref:`RequiredValidatorEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor>`
calls this event, if the checkbox is deselected. EXT:form uses this event to
remove the configured required validator ('NotEmpty') from the ``form
definition`` through the method ``getFormEditorApp().getViewModel().removePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/removeCollectionElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-close-perform:

view/modal/close/perform
++++++++++++++++++++++++

If you try to close the ``form editor`` with unsaved content, a dialog box
appears, asking whether you really wish to close it. If you confirm it, this
event is called in the ``check box`` component. EXT:form uses this event to
close the ``form editor`` and return to the ``form manager``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/close/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removecollectionelement-perform:

view/modal/removeCollectionElement/perform
++++++++++++++++++++++++++++++++++++++++++

If you try to remove a validator/ finisher by clicking the remove icon, a
dialog box appears, asking you to confirm this action. If confirmed, this event
is called in the ``check box`` component. EXT:form uses this event to remove
the validator/ finisher from the ``form definition`` through the method
``getFormEditorApp().getViewModel().removePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/removeCollectionElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removeformelement-perform:

view/modal/removeFormElement/perform
++++++++++++++++++++++++++++++++++++

If you try to remove a form element by clicking the remove icon, a dialog box
appears, asking you to confirm this action. If confirmed, this event is called
in the ``check box`` component. EXT:form uses this event to remove the form
element from the ``form definition`` via the method ``getFormEditorApp().getViewModel().removeFormElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/removeFormElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-validationerrors-element-clicked:

view/modal/validationErrors/element/clicked
+++++++++++++++++++++++++++++++++++++++++++

If a form element contains a validation error and you try to save the form, a
dialog box appears, listing all form elements with validation errors. One such
form element can be clicked in this dialog box. This event is called by
clicking a form element in the dialog box. EXT:form uses this event to select
and show this form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/validationErrors/element/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-paginationnext-clicked:

view/paginationNext/clicked
+++++++++++++++++++++++++++

This event is called if the 'pagination next' button in the ``Stage``
component's header section is clicked. EXT:form uses this event to render the
next page of the form.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/paginationNext/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-paginationprevious-clicked:

view/paginationPrevious/clicked
+++++++++++++++++++++++++++++++

This event is called, if the 'pagination previous' button in the ``Stage``
component's header section is clicked. EXT:form uses this event to render the
previous page of the form.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/paginationPrevious/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-ready:

view/ready
++++++++++

EXT:form makes it possible to load :ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`.
If all modules are loaded, the view-model method ``_loadAdditionalModules``
calls this event. EXT:form uses this event to remove the preloader icon and
finally initialize the ``form editor``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/ready', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-redobutton-clicked:

view/redoButton/clicked
+++++++++++++++++++++++

This event is called if the redo button in the ``form editor`` header is
clicked. The addition/ deletion and movement of form elements and property
collection elements (validators/ finishers) is saved in an internal stack in
order to reset the undo/ redo functionality. EXT:form uses this event to reset
this stack to the previous state.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/redoButton/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-button-newelement-clicked:

view/stage/abstract/button/newElement/clicked
+++++++++++++++++++++++++++++++++++++++++++++

This event is called if the "Create new element" button at the end of the
``Stage`` component in the ``abstract view`` mode is clicked. EXT:form uses
this event to display the "new element" dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    *              args[1] = configuration
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/button/newElement/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-change:

view/stage/abstract/dnd/change
++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'change' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/change`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
set various CSS classes during the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = placeholderDomElement
    *              args[1] = parentFormElementIdentifierPath
    *              args[2] = enclosingCompositeFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/change', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-start:

view/stage/abstract/dnd/start
+++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'start' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/start`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
set various CSS classes at the start of the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementDomElement
    *              args[1] = draggedFormPlaceholderDomElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/start', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-stop:

view/stage/abstract/dnd/stop
++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'stop' event from 'jquery.mjs.nestedSortable' calls the
``view/stage/abstract/dnd/stop`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
to re-render the ``Tree``, ``Stage`` and ``Inspector`` components at the end of
the drag-and-drop process and to select the moved form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/stop', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-update:

view/stage/abstract/dnd/update
++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/update`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event
to move the form element in the ``form definition`` accordingly at the end of
the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedDomElement
    *              args[1] = movedFormElementIdentifierPath
    *              args[2] = previousFormElementIdentifierPath
    *              args[3] = nextFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-elementtoolbar-button-newelement-clicked:

view/stage/abstract/elementToolbar/button/newElement/clicked
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

This event is called if the "Create new element" button in the form-element
toolbar or "Inside" or "After" in the split button is clicked. EXT:form uses
this event to display the "New element" dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    *              args[1] = configuration
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/elementToolbar/button/newElement/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-postprocess:

view/stage/abstract/render/postProcess
++++++++++++++++++++++++++++++++++++++

This event is called after the ``abstract view`` of the ``Stage`` component has
been rendered. EXT:form uses this event to render the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-preprocess:

view/stage/abstract/render/preProcess
+++++++++++++++++++++++++++++++++++++

This event is called before the ``abstract view`` of the ``Stage`` component is
rendered.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/preProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform:

view/stage/abstract/render/template/perform
+++++++++++++++++++++++++++++++++++++++++++

The methods ``getFormEditorApp().getViewModel().renderAbstractStageArea()``
call this event. Strictly speaking, the ``Stage`` component in the method
``_renderTemplateDispatcher()`` calls this event. The ``form editor`` requires
for each form element an inline HTML template the corresponding JavaScript
code. Information matching inline HTML templates to the appropriate form
elements must be configured within :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
At this point, the key identifying the form element follows a convention:
``FormElement-<formElementTypeIdentifier>``. The value for the key tells the
``form editor`` which inline HTML template should be loaded for the respective
form element. The ``_renderTemplateDispatcher()`` method then identifies, by
means of the form element's ``<formElementTypeIdentifier>``, the corresponding
JavaScript code to fill the inline HTML template with life.
``_renderTemplateDispatcher()`` contains a hard-coded list with the
``<formElementTypeIdentifier>`` that is brought in with the EXT:form, and it
renders the inline HTML templates accordingly. At the end, the
``view/stage/abstract/render/template/perform`` event is called. If you wish to
implement your own form element and show it in the ``form editor``, this event
can be used to execute in :ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`
the corresponding JavaScript code, with the help of the ``<formElementTypeIdentifier>``.
This is generally enough to allow the ``Stage/SimpleTemplate`` and/ or
``Stage/SelectTemplate`` inline HTML template to be rendered for your own form
element and, in the JavaScript code, to access the ``getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators()``
and/ or ``getFormEditorApp().getViewModel().getStage().renderSelectTemplates()``
method delivered with EXT:form. An overview over the functionality of the
formEditorPartials for the ``<formElementTypeIdentifier>`` and its JavaScript
code is found :ref:`here <apireference-formeditor-stage-commonabstractformelementtemplates>`.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    *              args[1] = template
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function(topic, args) {
   });

A simple example reusing the EXT:form inline HTML template ``Stage/SelectTemplate`` and the EXT:form JavaScript code ``renderSelectTemplates()``
for a custom form element with ``<formElementTypeIdentifier>`` = 'GenderSelect'.
In this example, 'GenderSelect' is basically a radio button form element with some predefined options.

.. code-block:: yaml
   :emphasize-lines: 11

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formEditor:
               dynamicJavaScriptModules:
                 additionalViewModelModules:
                   10: '@typo3/my-site-package/backend/form-editor/view-model.js'
               formEditorPartials:
                 FormElement-GenderSelect: 'Stage/SelectTemplate'
             formElementsDefinition:
               GenderSelect:
                 __inheritances:
                   10: 'TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.RadioButton'
                 renderingOptions:
                   templateName: 'RadioButton'
                 properties:
                   options:
                     f: 'Female'
                     m: 'Male'
                     u: 'Unicorn'
                     a: 'Alien'
                 formEditor:
                   label: 'Gender Select'
                   group: select
                   groupSorting: 9000
                   predefinedDefaults:
                     properties:
                       options:
                         f: 'Female'
                         m: 'Male'
                         u: 'Unicorn'
                         a: 'Alien'
                   editors:
                     300: null

.. code-block:: javascript
   :emphasize-lines: 105-109

   /**
    * Module: @typo3/my-site-package/backend/form-editor/view-model
    */
   define(['jquery',
           'TYPO3/CMS/Form/Backend/FormEditor/Helper'
           ], function($, Helper) {
           'use strict';

       return (function($, Helper) {

           /**
            * @private
            *
            * @var object
            */
           var _formEditorApp = null;

           /**
            * @private
            *
            * @return object
            */
           function getFormEditorApp() {
               return _formEditorApp;
           };

           /**
            * @private
            *
            * @return object
            */
           function getPublisherSubscriber() {
               return getFormEditorApp().getPublisherSubscriber();
           };

           /**
            * @private
            *
            * @return object
            */
           function getUtility() {
               return getFormEditorApp().getUtility();
           };

           /**
            * @private
            *
            * @param object
            * @return object
            */
           function getHelper() {
               return Helper;
           };

           /**
            * @private
            *
            * @return object
            */
           function getCurrentlySelectedFormElement() {
               return getFormEditorApp().getCurrentlySelectedFormElement();
           };

           /**
            * @private
            *
            * @param mixed test
            * @param string message
            * @param int messageCode
            * @return void
            */
           function assert(test, message, messageCode) {
               return getFormEditorApp().assert(test, message, messageCode);
           };

           /**
            * @private
            *
            * @return void
            * @throws 1491643380
            */
           function _helperSetup() {
               assert('function' === $.type(Helper.bootstrap),
                   'The view model helper does not implement the method "bootstrap"',
                   1491643380
               );
               Helper.bootstrap(getFormEditorApp());
           };

           /**
            * @private
            *
            * @return void
            */
           function _subscribeEvents() {
               /**
                * @private
                *
                * @param string
                * @param array
                *              args[0] = formElement
                *              args[1] = template
                * @return void
                */
               getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function(topic, args) {
                   if (args[0].get('type') === 'GenderSelect') {
                       getFormEditorApp().getViewModel().getStage().renderSelectTemplates(args[0], args[1]);
                   }
               });
           };

           /**
            * @public
            *
            * @param object formEditorApp
            * @return void
            */
           function bootstrap(formEditorApp) {
               _formEditorApp = formEditorApp;
               _helperSetup();
               _subscribeEvents();
           };

           /**
            * Publish the public methods.
            * Implements the "Revealing Module Pattern".
            */
           return {
               bootstrap: bootstrap
           };
       })($, Helper);
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-element-clicked:

view/stage/element/clicked
++++++++++++++++++++++++++

This event is called from the ``Stage`` component when a form element is
clicked. EXT:form uses this event to select this element and to display the
form-element toolbar. In addition, the ``Tree`` and ``Inspector`` components
are re-rendered.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/element/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-panel-clicked:

view/stage/panel/clicked
++++++++++++++++++++++++

This event is called if the header section of the ``Stage`` component is
clicked.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/panel/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-preview-render-postprocess:

view/stage/preview/render/postProcess
+++++++++++++++++++++++++++++++++++++

This event is called after the ``preview view`` of the ``Stage`` component has
been rendered. EXT:form uses this event to render the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/preview/render/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-button-newpage-clicked:

view/structure/button/newPage/clicked
+++++++++++++++++++++++++++++++++++++

This event is called from the onClick event of the ``Tree`` component's "Create
new page" button. EXT:form uses this event to display the "new page" dialog
box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/button/newPage/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-renew-postprocess:

view/structure/renew/postProcess
++++++++++++++++++++++++++++++++

This event is called from the view-model after the ``Tree`` component has been
re-rendered. EXT:form uses this event to display potential validation errors
from form elements in the ``Tree`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/renew/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-root-selected:

view/structure/root/selected
++++++++++++++++++++++++++++

This event is called if the root form element in the ``Tree`` component is
clicked. EXT:form uses this event to re-render the ``Stage``, ``Inspector`` and
``Tree`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/root/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-change:

view/tree/dnd/change
++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'change' event from 'jquery.mjs.nestedSortable' calls
the ``view/tree/dnd/change`` event in der ``Tree`` component if form elements
are sorted. EXT:form uses this event to set various CSS classes during the drag
-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = placeholderDomElement
    *              args[1] = parentFormElementIdentifierPath
    *              args[2] = enclosingCompositeFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/change', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-stop:

view/tree/dnd/stop
++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'stop' event from 'jquery.mjs.nestedSortable' calls the
``view/tree/dnd/stop`` event in the ``Tree`` component if form elements are
sorted. EXT:form uses this event to re-render ``Tree``, ``Stage`` and
``Inspector`` components at the end of the drag-and-drop process and to select
the moved form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/stop', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-update:

view/tree/dnd/update
++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/tree/dnd/update`` event in der ``Tree`` component if form elements
are sorted. EXT:form uses this event to move the form element in the ``form
definition`` accordingly at the end of the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedDomElement
    *              args[1] = movedFormElementIdentifierPath
    *              args[2] = previousFormElementIdentifierPath
    *              args[3] = nextFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-node-clicked:

view/tree/node/clicked
++++++++++++++++++++++

This event is called from the ``Tree`` component if a form element is clicked.
EXT:form uses this event to re-render the ``Stage`` and ``Inspector``
components and select the form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/node/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-render-listitemadded:

view/tree/render/listItemAdded
++++++++++++++++++++++++++++++

This event is called by the ``Tree`` component for each form element as soon as
it is added to the tree.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = listItem
    *              args[1] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/render/listItemAdded', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-undobutton-clicked:

view/undoButton/clicked
+++++++++++++++++++++++

This event is called when the undo button is clicked in the ``form editor``
header. The history of adding / deleting and moving form elements and property
collection elements (validators/ finishers) is stored in an internal stack to
implement the undo / redo functionality. EXT:form uses this event to set this
stack to the next state.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/undoButton/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-abstract-clicked:

view/viewModeButton/abstract/clicked
++++++++++++++++++++++++++++++++++++

This event is called when the abstract view button is clicked in the header
area of the ``Stage`` component. EXT:form uses this event to render the
``abstract view`` in the ``Stage`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/viewModeButton/abstract/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-preview-clicked:

view/viewModeButton/preview/clicked
+++++++++++++++++++++++++++++++++++

This event is called when the preview button is clicked in the header area of
the ``Stage`` component. EXT:form uses this event to render the ``preview
view`` in the ``Stage`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/viewModeButton/preview/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel:

FormElement model
^^^^^^^^^^^^^^^^^


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-parentrenderable:

Property: __parentRenderable
++++++++++++++++++++++++++++

__parentRenderable includes the parent element as ``FormElement model``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-identifierpath:

Property: __identifierPath
++++++++++++++++++++++++++

Internally, all form elements are identified by their 'identifier' property,
which must be unique for each form. The ``__identifierPath`` property contains
the path to the element (as seen from the first element), separated by a ``/``.
Using this path, you can access the element directly through an API method.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get:

Method: get()
+++++++++++++

Each property of the ``FormElement model`` can be accessed by the ``get()``
method through the property path (separated by ``.``). Prerequisite for this
is that all levels up to the target property are objects.

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Access to ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   // value = 'Name'
   var value = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').get('properties.fluidAdditionalAttributes.placeholder');

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties:

Accessing property collection properties
''''''''''''''''''''''''''''''''''''''''

Property collection are identified as form elements through the property
``identifier``. Because property collection properties are in an array and
their positions in the array are potentially unknown, the ``getFormEditorApp().buildPropertyPath()``
method exists. This can be used to access a property of a property collection
item via its ``identifier``.

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "StringLength"
         "options": {
           "minimum": "1",
           "maximum": "2"
         }
       }
     ]
   }

Access to ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   // value = 1
   var value = formElement.get(propertyPath);


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-renderables:

Accessing renderables
'''''''''''''''''''''

Like ``property collections``, ``renderables`` (the child elements) are also in
an array and their position in the array is potentially unknown. Direct access
to child elements through the  ``get()`` method is impossible.
``formElement.get('renderables')`` supplies an array with the ``FormElement
models`` of the child elements. You must then loop over this array. Access to a
specific child element should be done using ``getFormEditorApp().getFormElementByIdentifierPath()``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set:

Method: set()
+++++++++++++

Any property of the ``FormElement model`` can be written using the ``set()``
method by means of the property path (separated by ``.``).

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Set the property ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');

Example of the ``FormElement model`` after the ``set()`` operation:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "New Placeholder"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-propertycollectionproperties:

Setting property collection properties
''''''''''''''''''''''''''''''''''''''

In principle, the same applies here as for :ref:`get property collection properties<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties>`.

Set the property ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   formElement.set(propertyPath, '2');


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-renderables:

Setting renderables
'''''''''''''''''''

To add child form elements to a ``FormElement model``, the appropriate API
methods should be used:

- getFormEditorApp().createAndAddFormElement()
- getFormEditorApp().addFormElement()
- getFormEditorApp().moveFormElement()
- getFormEditorApp().removeFormElement()


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset:

Method: unset()
+++++++++++++++

Any property of the ``FormElement model`` can be deleted using the method
``unset()`` by means of the property path (separated by ``.``).

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Delete the property ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   // value = 'Name'
   var value = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').unset('properties.fluidAdditionalAttributes.placeholder');

Example of the ``FormElement model`` after the ``unset()`` operation:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {}
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-propertycollectionproperties:

Remove property collection properties
'''''''''''''''''''''''''''''''''''''

In principle, the same applies here as for :ref:`get property collection properties<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties>`.

Delete the property ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   formElement.unset(propertyPath);


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-renderables:

Remove renderables
''''''''''''''''''

To delete a ``FormElement model``, the corresponding API method
``getFormEditorApp().removeFormElement()`` should be used.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-on:

Method: on()
++++++++++++

Any number of :ref:`Publisher/Subscriber<concepts-formeditor-basicjavascriptconcepts-events>`
events can be assigned to any property path of a ``FormElement model``. Each
``set()`` operation on this property path will then call these events. By
default, EXT:form registers the event :ref:`core/formElement/somePropertyChanged<apireference-formeditor-basicjavascriptconcepts-events-core-formelement-somepropertychanged>`
for each property path.

Example:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').on('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');
   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');
   // now, the event 'my/custom/event' will be published


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-off:

Method: off()
+++++++++++++

Any event registered via :ref:`on()<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-on>`
can be removed with off().

Example:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').off('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-getobjectdata:

Method: getObjectData()
+++++++++++++++++++++++

All ``FormElement model`` properties are private and cannot be manipulated
directly from the outside. They can only be accessed via ``set()`` or
``get()``. This method is used internally to obtain all data of a ``FormElement
model`` in object form so that they can be used in, for example, Ajax requests.
``getObjectData()`` returns a dereferenced object of the ``FormElement model``
with all internal data, thus allowing read access to all data set via
``set()``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-tostring:

Method: toString()
++++++++++++++++++

A method that was implemented for debugging purposes. Returns the object data
supplied by ``getObjectData()`` in string form.

.. code-block:: javascript

   console.log(formElement.toString());


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-clone:

Method: clone()
+++++++++++++++

If necessary, a form element can be cloned. Returns a dereferenced clone of the
original ``FormElement model``.


.. code-block:: javascript

   var dolly = formElement.clone();
