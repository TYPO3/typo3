
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/* eslint-disable @typescript-eslint/member-ordering */

import { LitElement, PropertyDeclaration, ReactiveElement } from 'lit';
import { defaultConverter } from '@lit/reactive-element';
import { property } from 'lit/decorators';

export const internals = Symbol('internals');
const privateInternals = Symbol('privateInternals');
export const getFormValue = Symbol('getFormValue');
export const getFormState = Symbol('getFormState');

/**
 * Base element class for settings type to act as
 * a form associated custom element.
 *
 * See https://web.dev/articles/more-capable-form-controls#defining_a_form-associated_custom_element
 */
export abstract class BaseElement<T = string> extends LitElement {

  @property({ type: String }) key: string;
  @property({ type: String }) formid: string;

  static readonly formAssociated = true;

  /* @property annotation needs to be provided by extending class */
  value: T;

  [privateInternals]?: ElementInternals;

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected get [internals]() {
    // Create internals in getter so that it can be used in methods called on
    // construction in `ReactiveElement`, such as `requestUpdate()`.
    if (!this[privateInternals]) {
      this[privateInternals] = this.attachInternals();
    }

    return this[privateInternals];
  }

  public get form() {
    return this[internals].form;
  }

  protected get labels() {
    return this[internals].labels;
  }

  // Use @property for the `name` and `disabled` properties to add them to the
  // `observedAttributes` array and trigger `attributeChangedCallback()`.
  //
  // We don't use Lit's default getter/setter (`noAccessor: true`) because
  // the attributes need to be updated synchronously to work with synchronous
  // form APIs, and Lit updates attributes async by default.
  @property({ noAccessor: true })
  public get name() {
    return this.getAttribute('name') ?? '';
  }
  public set name(name: string) {
    // Note: setting name to null or empty does not remove the attribute.
    this.setAttribute('name', name);
    // We don't need to call `requestUpdate()` since it's called synchronously
    // in `attributeChangedCallback()`.
  }

  @property({ type: Boolean, noAccessor: true })
  public get disabled() {
    return this.hasAttribute('disabled');
  }

  public set disabled(disabled: boolean) {
    this.toggleAttribute('disabled', disabled);
    // We don't need to call `requestUpdate()` since it's called synchronously
    // in `attributeChangedCallback()`.
  }

  public override attributeChangedCallback(
    name: string,
    old: string | null,
    value: string | null,
  ) {
    // Manually `requestUpdate()` for `name` and `disabled` when their
    // attribute or property changes.
    // The properties update their attributes, so this callback is invoked
    // immediately when the properties are set. We call `requestUpdate()` here
    // instead of letting Lit set the properties from the attribute change.
    // That would cause the properties to re-set the attribute and invoke this
    // callback again in a loop. This leads to stale state when Lit tries to
    // determine if a property changed or not.
    if (name === 'name' || name === 'disabled') {
      // Disabled's value is only false if the attribute is missing and null.
      const oldValue = name === 'disabled' ? old !== null : old;
      // Trigger a lit update when the attribute changes.
      this.requestUpdate(name, oldValue);
      return;
    }

    super.attributeChangedCallback(name, old, value);
  }

  public override requestUpdate(
    name?: PropertyKey,
    oldValue?: unknown,
    options?: PropertyDeclaration,
  ) {
    super.requestUpdate(name, oldValue, options);
    if (name === 'value') {
      this.dispatchEvent(new CustomEvent('typo3:setting:changed', { detail: { value: this.value } }));
      // Update the form value synchronously in `requestUpdate()` rather than
      // `update()` or `updated()`, which are async. This is necessary to ensure
      // that form data is updated in time for synchronous event listeners.
      this[internals].setFormValue(this[getFormValue](), this[getFormState]());
    }
  }

  public formDisabledCallback(disabled: boolean) {
    this.disabled = disabled;
  }

  /**
   * Callback triggered when <button type=reset> or form.reset() is triggered.
   */
  public formResetCallback() {
    const oldValue = this.value;
    const defaultValue = this.getAttribute('value');

    // Workaround to trigger string to property conversion
    this.attributeChangedCallback('value', this.valueToString(oldValue), null);
    this.attributeChangedCallback('value', null, defaultValue);
  }

  /**
   * Callback triggered when form is (re-)loaded by browser-back button.
   */
  public formStateRestoreCallback(state: FormValue): void {
    if (typeof state === 'string') {
      this.attributeChangedCallback('value', this.valueToString(this.value), null);
      this.attributeChangedCallback('value', null, state);
    } else {
      throw new Error(`formStateRestoreCallback() needs to be implemented for <${this.localName}> for state type "${typeof state}"`);
    }
  }

  protected [getFormState](): FormValue | null {
    return this[getFormValue]();
  }

  protected [getFormValue](): string {
    return this.valueToString(this.value);
  }

  protected valueToString(value: T): string {
    const ctor = this.constructor as typeof ReactiveElement;
    const options = ctor.getPropertyOptions('value');
    const converter = typeof options.converter === 'object' && typeof options.converter?.toAttribute === 'function' ?
      options.converter.toAttribute : defaultConverter.toAttribute;
    return converter(value, options.type) as string;
  }
}

export type FormValue = File | string | FormData;

/**
 * A value to be restored for a component's form value. If a component's form
 * state is a `FormData` object, its entry list of name and values will be
 * provided.
 */
export type FormRestoreState =
  | File
  | string
  | Array<[string, FormDataEntryValue]>;

/**
 * The reason a form component is being restored for, either `'restore'` for
 * browser restoration or `'autocomplete'` for restoring user values.
 */
export type FormRestoreReason = 'restore' | 'autocomplete';
