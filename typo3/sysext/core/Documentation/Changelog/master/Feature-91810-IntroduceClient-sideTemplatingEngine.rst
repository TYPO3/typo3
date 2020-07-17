.. include:: ../../Includes.txt

=========================================================
Feature: #91810 - Introduce client-side templating engine
=========================================================

See :issue:`91810`

Description
===========

To avoid custom jQuery template building a new slim client-side templating
engine is introduced. The functionality has been inspired by `lit-html`_ -
however it is actually not the same. As long as RequireJS and AMD-based
JavaScript modules are in place `lit-html` cannot be used directly, since
it requires native ES6-module support.

This templating engine is very simplistic and does not yet support virtual
DOM, any kind of data-binding or mutation/change detection mechanism. However
it does support conditions, iterations and simple default events in templates.

.. _lit-html: https://lit-html.polymer-project.org/


Impact
======

Individual client-side templates can be processed in JavaScript directly
using moder web technologies like template-strings_ and template-elements_.

.. _template-strings: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Template_literals
.. _template-elements: https://developer.mozilla.org/de/docs/Web/HTML/Element/template

Rendering is handled by AMD-module `TYPO3/CMS/Backend/Element/Template`:

* :js:`constructor(unsafe: boolean, strings: TemplateStringsArray, ...values: any[])`
  is most probably invoked by template tag functions `html` and `unsafe` only
* :js:`getHtml(parentScope: Template = null): string`
  renders and returns inner HTML
* :js:`getElement(): HTMLTemplateElement`
  renders and returns HTML `template` element
* :js:`mountTo(renderRoot: HTMLElement | ShadowRoot, clear: boolean = false): void`
  renders and mounts result to existing HTML element
  + :js:`renderRoot` can be a regular HTML element or root node of a Shadow DOM
  + :js:`clear` instructs to clear all exiting child elements in :js:`renderRoot`


Invocation usually happens using static template tag functions:

* :js:`html = (strings: TemplateStringsArray, ...values: any[]): Template`
  processes templates and ensures values are encoded for HTML
* :js:`unsafe = (strings: TemplateStringsArray, ...values: any[]): Template`
  processes templates and skips encoding values for HTML - when using this
  function, user submitted values need be encoded manually to avoid XSS

Examples
========

Variable assignment
-------------------

.. code-block:: ts

   import {Template, html, unsafe} from 'TYPO3/CMS/Backend/Element/Template';

   const value = 'World';
   const target = document.getElementById('target');
   const template = html`<div>Hello ${value}!</div>`;
   template.mountTo(target, true);

.. code-block:: html

   <div>Hello World!</div>

Unsafe tags would have been encoded (e.g. :html:`<b>World</b>`
as :html:`&lt;b&gt;World&lt;/b&gt;`).


Condition and iteration
-----------------------

.. code-block:: ts

   import {Template, html, unsafe} from 'TYPO3/CMS/Backend/Element/Template';

   const items = ['a', 'b', 'c']
   const addClass = true;
   const target = document.getElementById('target');
   const template = html`
      <ul ${addClass ? 'class="list"' : ''}>
      ${items.map((item: string, index: number): string => {
         return html`<li>#${index+1}: ${item}</li>`
      })}
      </ul>
   `;
   template.mountTo(target, true);

.. code-block:: html

   <ul class="list">
      <li>#1: a</li>
      <li>#2: b</li>
      <li>#3: c</li>
   </ul>

The :js:`${...}` literal used in template tags can basically contain any
JavaScript instruction - as long as their result can be casted to `string`
again or is of type `TYPO3/CMS/Backend/Element/Template`. This allows to
make use of custom conditions as well as iterations:

* condition: :js:`${condition ? thenReturn : elseReturn}`
* iteration: :js:`${array.map((item) => { return item; })}`


Events
------

Currently only `click` events are supported using :html:`@click="${handler}"`.

.. code-block:: ts

   import {Template, html, unsafe} from 'TYPO3/CMS/Backend/Element/Template';

   const value = 'World';
   const target = document.getElementById('target');
   const template = html`
      <div @click="${(evt: Event): void => { console.log(value); })}">
         Hello ${value}!
      </div>
   `;
   template.mountTo(target, true);

The result won't look much different than the first example - however the
custom attribute :html:`@click` will be transformed into an according event
listener bound to the element where it has been declared.


.. index:: Backend, JavaScript, ext:backend
