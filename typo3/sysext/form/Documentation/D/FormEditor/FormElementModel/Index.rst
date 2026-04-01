..  include:: /Includes.rst.txt

..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel:
..  _apireference-formeditor-formelementmodel:

==================
FormElement model
==================

Every form element in the editor is represented by a **FormElement model**
object. This model is the single source of truth for all element properties
during an editing session; it is separate from the YAML form definition on
disk (which is only written on save).

..  contents::
    :depth: 1
    :local:


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-identifierpath:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-parentrenderable:
..  _apireference-formeditor-formelementmodel-structure:

Model structure
===============

A FormElement model carries all YAML properties of the element plus two
internal bookkeeping properties:

.. list-table::
   :header-rows: 1
   :widths: 30 70

   *  -  Property
      -  Description
   *  -  :js:`__identifierPath`
      -  Slash-separated path from the root element to this element
         (e.g. :js:`'example-form/page-1/name'`). Used as a unique key
         for API lookups.
   *  -  :js:`__parentRenderable`
      -  Reference to the parent FormElement model (filtered for display).

Example model in memory:

..  literalinclude:: _codesnippets/_model-structure.js
    :language: javascript


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-renderables:
..  _apireference-formeditor-formelementmodel-api-get:

get()
-----

Reads a property by its dot-separated path. All intermediate levels must
be objects.

..  literalinclude:: _codesnippets/_get-simple.js
    :language: javascript

For **property collections** (validators / finishers), whose position in
the array is unknown, use :js:`buildPropertyPath()` first:

..  literalinclude:: _codesnippets/_get-property-collection.js
    :language: javascript

For **renderables** (child elements), :js:`get('renderables')` returns a
plain array of FormElement models. To access a specific child, use
:js:`formEditorApp.getFormElementByIdentifierPath()` with the full path.


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-propertycollectionproperties:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-renderables:
..  _apireference-formeditor-formelementmodel-api-set:

set()
-----

Writes a property by its dot-separated path. Every :js:`set()` call
automatically publishes all events registered for that path via
:ref:`on() <apireference-formeditor-formelementmodel-api-on>`, including
the built-in
:ref:`core/formElement/somePropertyChanged <apireference-formeditor-jsevents-core-formelement-somepropertychanged>`.

..  literalinclude:: _codesnippets/_set.js
    :language: javascript

To modify property collection properties or add child renderables, use
the dedicated API methods on :js:`formEditorApp` / :js:`getViewModel()`
instead of setting array positions directly:

-  :js:`createAndAddFormElement()`
-  :js:`addFormElement()`
-  :js:`moveFormElement()`
-  :js:`removeFormElement()`


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-propertycollectionproperties:
..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-renderables:
..  _apireference-formeditor-formelementmodel-api-unset:

unset()
-------

Removes a property at the given dot-separated path.

..  literalinclude:: _codesnippets/_unset.js
    :language: javascript

For property collection properties, use :js:`buildPropertyPath()` in the
same way as for :ref:`get() <apireference-formeditor-formelementmodel-api-get>`.

To remove a child renderable, call
:js:`formEditorApp.removeFormElement()`.


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-on:
..  _apireference-formeditor-formelementmodel-api-on:

on()
----

Registers an additional publish/subscribe event name that is fired
whenever :js:`set()` is called for a given property path.

..  literalinclude:: _codesnippets/_on.js
    :language: javascript

By default EXT:form registers
:ref:`core/formElement/somePropertyChanged <apireference-formeditor-jsevents-core-formelement-somepropertychanged>`
for every known property path of every form element.


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-off:
..  _apireference-formeditor-formelementmodel-api-off:

off()
-----

Removes an event registration created with :js:`on()`.

..  literalinclude:: _codesnippets/_off.js
    :language: javascript


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-getobjectdata:
..  _apireference-formeditor-formelementmodel-api-getobjectdata:

getObjectData()
---------------

Returns a deep-cloned plain object of all properties. Used internally for
Ajax serialisation. Provides read access to data set via :js:`set()` from
outside the model without breaking encapsulation.


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-clone:
..  _apireference-formeditor-formelementmodel-api-clone:

clone()
-------

Returns a fully dereferenced clone of the FormElement model.

..  literalinclude:: _codesnippets/_clone.js
    :language: javascript


..  _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-tostring:
..  _apireference-formeditor-formelementmodel-api-tostring:

toString()
----------

Returns the model data as a JSON string. Intended for debugging.

..  literalinclude:: _codesnippets/_to-string.js
    :language: javascript
