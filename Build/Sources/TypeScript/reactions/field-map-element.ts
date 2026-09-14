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

/**
 * Module: @typo3/reactions/field-map-element
 *
 * One row of the reaction field map. It offers three ways to fill a single field of the
 * record the reaction creates: pick a value in the field's own control, enter a
 * placeholder that is resolved from the payload when the reaction runs, or leave the
 * field to the target table, which disables the control to show it is not written.
 *
 * The markup comes from FormEngine, this only drives it: it shows the pane the current
 * mode selects and copies that pane's control value into the hidden input named after the
 * field, which is the only control of the row that is submitted.
 *
 * @example
 * <typo3-reactions-field-map-row mode="fixed" payload-default="${hidden}">
 *   <input type="hidden" name="data[fields][hidden]" data-fieldmap-value>
 *   <div data-fieldmap-pane="fixed" data-fieldmap-source="fieldMapFixed[hidden]">…</div>
 *   <div data-fieldmap-pane="payload" data-fieldmap-source="fieldMapPayload[hidden]" hidden>…</div>
 *   <select data-fieldmap-mode>…</select>
 * </typo3-reactions-field-map-row>
 */
const fieldMapModes = ['unset', 'fixed', 'payload'] as const;

export type FieldMapMode = typeof fieldMapModes[number];

const isFieldMapMode = (mode: string | null): mode is FieldMapMode =>
  fieldMapModes.includes(mode as FieldMapMode);

export class FieldMapRowElement extends HTMLElement {
  public static readonly observedAttributes = ['mode'];

  /**
   * Everything a control of the target table announces a new value with.
   */
  private static readonly watchedEvents = ['change', 'input', 'blur'] as const;

  public get mode(): FieldMapMode {
    const mode = this.getAttribute('mode');
    return isFieldMapMode(mode) ? mode : 'unset';
  }

  public set mode(mode: FieldMapMode) {
    this.setAttribute('mode', mode);
  }

  public connectedCallback(): void {
    // Capture phase, because some of these events do not bubble: FormEngine reports a new
    // value with `new Event('change')` and the color picker with `blur`. A capturing
    // listener still sees them, since every event passes its ancestors on the way down.
    for (const eventName of FieldMapRowElement.watchedEvents) {
      this.addEventListener(eventName, this.handleChange, true);
    }
    this.applyMode();
    // Copy the control's value into the hidden input, so the row submits what it displays.
    // The two can disagree: a select given a value none of its options carries falls back
    // to the first option, a radio group to nothing checked. Without an event, so that
    // opening a reaction does not mark the form as changed.
    this.syncValue(false);
  }

  public disconnectedCallback(): void {
    for (const eventName of FieldMapRowElement.watchedEvents) {
      this.removeEventListener(eventName, this.handleChange, true);
    }
  }

  public attributeChangedCallback(): void {
    this.applyMode();
  }

  private readonly handleChange = (event: Event): void => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }
    if (target instanceof HTMLSelectElement && target.hasAttribute('data-fieldmap-mode')) {
      if (isFieldMapMode(target.value)) {
        this.mode = target.value;
        this.seedPayloadDefault();
        this.syncValue();
      }
      return;
    }
    const pane = target.closest<HTMLElement>('[data-fieldmap-pane]');
    if (pane !== null && pane.dataset.fieldmapPane === this.mode) {
      this.syncValue();
    }
  };

  /**
   * Fills an empty payload input with the field's own name as a path, so switching to it
   * offers a complete expression to edit instead of a blank box that would store nothing.
   * An input that already has a value is left alone.
   */
  private seedPayloadDefault(): void {
    if (this.mode !== 'payload') {
      return;
    }
    const input = this.querySelector<HTMLInputElement>('[data-fieldmap-pane="payload"] input');
    const fallback = this.getAttribute('payload-default') ?? '';
    if (input !== null && input.value === '' && fallback !== '') {
      input.value = fallback;
    }
  }

  /**
   * Copies the current pane's control value into the hidden input that is submitted.
   *
   * The control is looked up by name through the form, which reads the right value for
   * every type: the hidden input of a checkbox, the checked radio, the selected option.
   *
   * @param notify Whether to tell the form the value changed. Off while the row is only
   *               catching up with the control it was rendered beside.
   */
  private syncValue(notify: boolean = true): void {
    const valueInput = this.querySelector<HTMLInputElement>('[data-fieldmap-value]');
    if (valueInput === null) {
      return;
    }
    let value = '';
    if (this.mode !== 'unset') {
      const pane = this.querySelector<HTMLElement>(`[data-fieldmap-pane="${this.mode}"]`);
      const source = pane?.dataset.fieldmapSource ?? '';
      const control = source === '' ? null : valueInput.form?.elements.namedItem(source) ?? null;
      value = (control as { value?: string } | null)?.value ?? '';
    }
    if (valueInput.value === value) {
      return;
    }
    valueInput.value = value;
    if (notify) {
      valueInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  /**
   * Shows the pane of the current mode and takes the other one out of the way.
   *
   * A row has two panes: `fixed` holds the control of the target table, `payload` holds
   * the placeholder input. plus the one hidden input that is actually submitted. Three
   * states, three mechanisms:
   *
   * - `hidden` on the pane that is not current. That alone keeps it out of the tab order,
   *   and since no control inside either pane is named within the data array, whatever
   *   they hold is ignored on submit anyway.
   * - `inert` on the `fixed` pane of a row that is not written. It stays visible, so the
   *   row keeps its label and shows what the target table would apply, but cannot be
   *   operated. Not `disabled`, because a control that reads `disabled` while setting
   *   itself up may then skip that setup for good. The color picker does exactly that,
   *   and enabling the field later does not bring it back.
   * - `disabled` on the hidden input holding the value. This is the one that matters on
   *   submit: a disabled input is not sent, so the field stays out of the stored map.
   */
  private applyMode(): void {
    for (const pane of this.querySelectorAll<HTMLElement>('[data-fieldmap-pane]')) {
      const name = pane.dataset.fieldmapPane;
      pane.hidden = name === 'payload' ? this.mode !== 'payload' : this.mode === 'payload';
      pane.inert = !pane.hidden && name !== this.mode;
    }
    const valueInput = this.querySelector<HTMLInputElement>('[data-fieldmap-value]');
    if (valueInput !== null) {
      valueInput.disabled = this.mode === 'unset';
    }
  }
}

window.customElements.define('typo3-reactions-field-map-row', FieldMapRowElement);

declare global {
  interface HTMLElementTagNameMap {
    'typo3-reactions-field-map-row': FieldMapRowElement;
  }
}
