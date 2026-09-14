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

// Imported for its side effect: the module defines the custom element. The binding below
// is only ever used as a type, so without this the import would be elided and nothing
// would register the element.
import '@typo3/reactions/field-map-element.js';
import type { FieldMapMode, FieldMapRowElement } from '@typo3/reactions/field-map-element.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/reactions/field-map-element', () => {
  let form: HTMLFormElement;

  beforeEach((): void => {
    form = document.createElement('form');
    document.body.appendChild(form);
  });

  afterEach((): void => {
    form.remove();
    form = null;
  });

  /**
   * The markup the FormEngine element produces. Only the hidden input at the top carries
   * the name of the field; the two editors are named outside the data array, which is what
   * keeps the form's named getter answering with one element rather than a RadioNodeList.
   * A checkbox is rendered as the backend renders it: the box itself has no name and the
   * bitmask sits in a hidden input beside it.
   */
  const createCheckboxRow = (mode: FieldMapMode, value: string = ''): FieldMapRowElement => {
    const row = <FieldMapRowElement>document.createElement('typo3-reactions-field-map-row');
    row.setAttribute('mode', mode);
    row.setAttribute('payload-default', '${hidden}');
    row.innerHTML = `
      <input type="hidden" data-fieldmap-value name="data[fields][hidden]" value="${value}">
      <div data-fieldmap-pane="fixed" data-fieldmap-source="fieldMapFixed[hidden]">
        <input type="checkbox" data-formengine-input-name="fieldMapFixed[hidden]" value="1">
        <input type="hidden" name="fieldMapFixed[hidden]" value="0">
      </div>
      <div data-fieldmap-pane="payload" data-fieldmap-source="fieldMapPayload[hidden]" hidden>
        <input type="text" name="fieldMapPayload[hidden]" value="">
      </div>
      <select data-fieldmap-mode>
        <option value="unset">Do not set</option>
        <option value="fixed">Fixed value</option>
        <option value="payload">Payload value</option>
      </select>`;
    form.appendChild(row);
    (row.querySelector<HTMLSelectElement>('[data-fieldmap-mode]')).value = mode;
    return row;
  };

  const createSelectRow = (mode: FieldMapMode, value: string = ''): FieldMapRowElement => {
    const row = <FieldMapRowElement>document.createElement('typo3-reactions-field-map-row');
    row.setAttribute('mode', mode);
    row.setAttribute('payload-default', '${doktype}');
    row.innerHTML = `
      <input type="hidden" data-fieldmap-value name="data[fields][doktype]" value="${value}">
      <div data-fieldmap-pane="fixed" data-fieldmap-source="fieldMapFixed[doktype]">
        <select name="fieldMapFixed[doktype]">
          <option value="1">Standard</option>
          <option value="254">Folder</option>
        </select>
      </div>
      <div data-fieldmap-pane="payload" data-fieldmap-source="fieldMapPayload[doktype]" hidden>
        <input type="text" name="fieldMapPayload[doktype]" value="">
      </div>
      <select data-fieldmap-mode>
        <option value="unset">Do not set</option>
        <option value="fixed">Fixed value</option>
        <option value="payload">Payload value</option>
      </select>`;
    form.appendChild(row);
    (row.querySelectorAll<HTMLSelectElement>('[data-fieldmap-mode]')[0]).value = mode;
    return row;
  };

  const paneOf = (row: FieldMapRowElement, pane: string): HTMLElement =>
    row.querySelector<HTMLElement>(`[data-fieldmap-pane="${pane}"]`);

  const valueOf = (row: FieldMapRowElement): string =>
    row.querySelector<HTMLInputElement>('[data-fieldmap-value]').value;

  const switchTo = async (row: FieldMapRowElement, mode: FieldMapMode): Promise<void> => {
    const modeSelect = row.querySelector<HTMLSelectElement>('[data-fieldmap-mode]');
    modeSelect.value = mode;
    modeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();
  };

  it('names exactly one control of the row after the field', () => {
    const row = createCheckboxRow('fixed');

    // Two would answer the named getter with a RadioNodeList, whose value setter does
    // nothing, which is how the checkbox element writes its bitmask.
    const named = form.elements.namedItem('data[fields][hidden]');
    expect(named).to.be.instanceOf(HTMLInputElement);
    expect(form.elements.namedItem('fieldMapFixed[hidden]')).to.be.instanceOf(HTMLInputElement);
    expect(row.mode).to.equal('fixed');
  });

  it('shows the field control but lets nothing be done with it while the field is not written', () => {
    const row = createCheckboxRow('unset');

    // Inert rather than disabled: an editor reading `disabled` while it sets itself up,
    // as the colour picker does, would never set itself up at all.
    expect(paneOf(row, 'fixed').hidden).to.equal(false);
    expect(paneOf(row, 'fixed').inert).to.equal(true);
    expect(paneOf(row, 'fixed').querySelector<HTMLInputElement>('input[type=checkbox]').disabled).to.equal(false);
    expect(paneOf(row, 'payload').hidden).to.equal(true);
  });

  it('leaves the field out of the map entirely while it is not written', () => {
    const row = createCheckboxRow('unset');

    // A disabled input is not submitted, so the key is absent rather than empty, which is
    // what tells the form on the next render that the field was set to not be written.
    expect(row.querySelector<HTMLInputElement>('[data-fieldmap-value]').disabled).to.equal(true);
    expect(form.elements.namedItem('data[fields][hidden]')).to.be.instanceOf(HTMLInputElement);
  });

  it('writes nothing for a row naming a mode that does not exist', () => {
    // The attribute is markup and may say anything. Falling back to the field's own
    // control would submit whatever value that control happens to show, which is a value
    // nobody picked, so the row falls back to not writing the field instead.
    const row = createCheckboxRow(<FieldMapMode>'nonsense');

    expect(row.mode).to.equal('unset');
    expect(row.querySelector<HTMLInputElement>('[data-fieldmap-value]').disabled).to.equal(true);
  });

  it('takes the select over where the rendered value is not one of its options', async () => {
    // The browser selects the first option of a select given a value none of them carries,
    // so the row would submit a value it does not show. Flipping the mode away and back
    // used to be what noticed, and by then it had overwritten the shown one.
    const row = createSelectRow('fixed', '9999');
    await Promise.resolve();

    expect(paneOf(row, 'fixed').querySelector<HTMLSelectElement>('select').value).to.equal('1');
    expect(valueOf(row)).to.equal('1');
  });

  it('does not report the row as changed while it is only catching up', async () => {
    let changed = false;
    form.addEventListener('change', () => { changed = true; });

    createSelectRow('fixed', '9999');
    await Promise.resolve();

    expect(changed).to.equal(false);
  });

  it('hides the editor that is not in use', () => {
    const row = createCheckboxRow('payload');

    // Hidden takes it out of the tab order, and neither editor is named inside the data
    // array, so nothing of it reaches the request whatever it holds.
    expect(paneOf(row, 'fixed').hidden).to.equal(true);
    expect(paneOf(row, 'fixed').inert).to.equal(false);
    expect(paneOf(row, 'payload').hidden).to.equal(false);
    expect(paneOf(row, 'payload').inert).to.equal(false);
  });

  it('takes the value of a select over when the mode is switched to fixed', async () => {
    const row = createSelectRow('payload');
    paneOf(row, 'fixed').querySelector<HTMLSelectElement>('select').value = '254';

    await switchTo(row, 'fixed');

    expect(valueOf(row)).to.equal('254');
  });

  it('follows a select the integrator changes', async () => {
    const row = createSelectRow('fixed', '1');
    const select = paneOf(row, 'fixed').querySelector<HTMLSelectElement>('select');

    select.value = '254';
    select.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();

    expect(valueOf(row)).to.equal('254');
  });

  it('follows the field control as it is used', async () => {
    const row = createCheckboxRow('fixed', '0');
    const bitmask = paneOf(row, 'fixed').querySelector<HTMLInputElement>('input[type=hidden]');

    // What UpdateBitmaskOnFieldChange does once the name resolves to a single element.
    bitmask.value = '1';
    bitmask.dispatchEvent(new Event('change', { bubbles: true }));
    await Promise.resolve();

    expect(valueOf(row)).to.equal('1');
  });

  it('writes the field again when it is taken off "do not set"', async () => {
    const row = createCheckboxRow('unset');
    paneOf(row, 'fixed').querySelector<HTMLInputElement>('input[type=hidden]').value = '1';

    await switchTo(row, 'fixed');

    expect(row.querySelector<HTMLInputElement>('[data-fieldmap-value]').disabled).to.equal(false);
    expect(valueOf(row)).to.equal('1');
  });

  it('takes the placeholder over while the mode is payload', async () => {
    const row = createCheckboxRow('fixed');

    await switchTo(row, 'payload');

    expect(paneOf(row, 'payload').querySelector<HTMLInputElement>('input').value).to.equal('${hidden}');
    expect(valueOf(row)).to.equal('${hidden}');
  });

  it('follows the payload input as it is typed into', async () => {
    const row = createCheckboxRow('payload');
    const input = paneOf(row, 'payload').querySelector<HTMLInputElement>('input');

    input.value = '${foo.bar}';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await Promise.resolve();

    expect(valueOf(row)).to.equal('${foo.bar}');
  });

  it('leaves a payload value the integrator already entered alone', async () => {
    const row = createCheckboxRow('fixed');
    paneOf(row, 'payload').querySelector<HTMLInputElement>('input').value = '${foo.bar}';

    await switchTo(row, 'payload');

    expect(valueOf(row)).to.equal('${foo.bar}');
  });

  it('stops submitting the field when it is set back to not being written', async () => {
    const row = createSelectRow('fixed', '254');

    await switchTo(row, 'unset');

    expect(row.querySelector<HTMLInputElement>('[data-fieldmap-value]').disabled).to.equal(true);
  });

  it('never lets an editor reach the request under the field name', async () => {
    const row = createCheckboxRow('fixed');

    for (const mode of <FieldMapMode[]>['payload', 'fixed', 'unset', 'payload']) {
      await switchTo(row, mode);

      const named = form.elements.namedItem('data[fields][hidden]');
      expect(named).to.be.instanceOf(HTMLInputElement);
      expect((named as HTMLInputElement).disabled).to.equal(mode === 'unset');
      expect(row.mode).to.equal(mode);
    }
  });
});
