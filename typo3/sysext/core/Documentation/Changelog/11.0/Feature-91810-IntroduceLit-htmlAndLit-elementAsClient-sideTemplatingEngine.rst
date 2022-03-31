.. include:: /Includes.rst.txt

=====================================================================================
Feature: #91810 - Introduce lit-html and lit-element as client-side templating engine
=====================================================================================

See :issue:`91810`

Description
===========

To avoid custom jQuery template building a new slim client-side templating
engine lit-html_ together with lit-element_ is introduced. The modules
are avilable via the umbrella javascript module `lit`.

This templating engine supports conditions, iterations, events, virtual DOM,
data-binding and mutation/change detections in templates.

.. _lit-html: https://lit-html.polymer-project.org/
.. _lit-element: https://lit-element.polymer-project.org/


Impact
======

Individual client-side templates can be processed in JavaScript directly
using modern web technologies like template-strings_ and template-elements_.

.. _template-strings: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Template_literals
.. _template-elements: https://developer.mozilla.org/de/docs/Web/HTML/Element/template

Rendering is handled by the AMD-modules `lit-html` and `lit-element`.
Please consult the `lit-html` template-reference_ and lit-element-guide_ for more
information.

.. _template-reference: https://lit-html.polymer-project.org/guide/template-reference
.. _lit-element-guide: https://lit-element.polymer-project.org/guide

Examples
========

Variable assignment
-------------------

.. code-block:: ts

   import {html, render} from 'lit';

   const value = 'World';
   const target = document.getElementById('target');
   render(html`<div>Hello ${value}!</div>`, target);

.. code-block:: html

   <div>Hello World!</div>

Unsafe tags would have been encoded (e.g. :html:`<b>World</b>`
as :html:`&lt;b&gt;World&lt;/b&gt;`).


Condition and iteration
-----------------------

.. code-block:: ts

   import {html, render} from 'lit';
   import {classMap} from 'lit/directives/class-map.js';

   const items = ['a', 'b', 'c']
   const classes = { list: true };
   const target = document.getElementById('target');
   const template = html`
      <ul class=${classMap(classes)}">
      ${items.map((item: string, index: number): string => {
         return html`<li>#${index+1}: ${item}</li>`
      })}
      </ul>
   `;
   render(template, target);

.. code-block:: html

   <ul class="list">
      <li>#1: a</li>
      <li>#2: b</li>
      <li>#3: c</li>
   </ul>

The :js:`${...}` literal used in template tags can basically contain any
JavaScript instruction - as long as their result can be casted to `string`
again or is of type `lit.TemplateResult`. This allows to
make use of custom conditions as well as iterations:

* condition: :js:`${condition ? thenReturn : elseReturn}`
* iteration: :js:`${array.map((item) => { return item; })}`


Events
------

Events can be bound using the `@` attribute prefix.

.. code-block:: ts

   import {html, render} from 'lit';

   const value = 'World';
   const target = document.getElementById('target');
   const template = html`
      <div @click="${(evt: Event): void => { console.log(value); })}">
         Hello ${value}!
      </div>
   `;
   render(template, target);

The result won't look much different from the first example - however the
custom attribute :html:`@click` will be transformed into an according event
listener bound to the element where it has been declared.

Custom HTML elements
--------------------

A web component based on the W3C custom elements (web-components_) specification
can be implemented using the `LitElement` base class.

.. code-block:: ts

   import {LitElement, html} from 'lit';
   import {customElement, property} from 'lit/decorators';

   @customElement('my-element')
   class MyElement extends LitElement {

    // Declare observed properties
    @property()
    value: string = 'awesome';

    // Avoid Shadow DOM so global styles apply to the element contents
    createRenderRoot(): Element|ShadowRoot {
      return this;
    }

    // Define the element's template
    render() {
      return html`<p>Hello ${this.value}!</p>`;
    }
   }

.. code-block:: html

   <my-element value="World"></my-element>

This is rendered as:

.. code-block:: html

   <my-element value="World">
     <p>Hello world!</p>
   </my-element>

.. _web-components: https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements


.. index:: Backend, JavaScript, ext:backend
