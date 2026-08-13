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

import SelectBoxFilter from '@typo3/backend/form-engine/element/extra/select-box-filter.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/form-engine/element/extra/select-box-filter', () => {
  let root: HTMLElement;
  let filterField: HTMLInputElement | HTMLSelectElement;

  const optionLabels: string[] = ['Crème brûlée', 'Müller', 'Sirup (Vanille)', 'Cremeschnitte', 'Tiramisu'];

  const options = (labels: string[]): string => labels.map((label: string): string => `<option>${label}</option>`).join('');

  const mount = (selectContent: string, filterMarkup: string = '<input type="text" class="t3js-formengine-multiselect-filter-textfield">'): void => {
    root = document.createElement('div');
    root.innerHTML = `
      <div class="form-wizards-wrap">
        ${filterMarkup}
        <select multiple>${selectContent}</select>
      </div>`;
    document.body.appendChild(root);
    new SelectBoxFilter(root.querySelector('select[multiple]'));
  };

  afterEach((): void => {
    root.remove();
  });

  const visibleOptions = (): string[] => Array
    .from(root.querySelectorAll('select[multiple] option'))
    .filter((option: HTMLOptionElement): boolean => !option.hidden)
    .map((option: HTMLOptionElement): string => option.textContent);

  const filterBy = (value: string): void => {
    filterField.value = value;
    filterField.dispatchEvent(new Event('input', { bubbles: true }));
  };

  describe('text field', () => {
    beforeEach((): void => {
      mount(options(optionLabels));
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-textfield');
    });

    it('shows all options for an empty filter', (): void => {
      filterBy('');
      expect(visibleOptions()).to.eql(optionLabels);
    });

    it('matches case insensitively', (): void => {
      filterBy('MÜLLER');
      expect(visibleOptions()).to.eql(['Müller']);
    });

    it('matches a plain substring anywhere in the label', (): void => {
      filterBy('schnitte');
      expect(visibleOptions()).to.eql(['Cremeschnitte']);
    });

    it('matches labels with diacritics when typing without them', (): void => {
      filterBy('creme');
      expect(visibleOptions()).to.eql(['Crème brûlée', 'Cremeschnitte']);
    });

    it('matches labels without diacritics when typing with them', (): void => {
      filterBy('crèm');
      expect(visibleOptions()).to.eql(['Crème brûlée', 'Cremeschnitte']);
    });

    it('treats regular expression meta characters as literal text', (): void => {
      filterBy('(Vanille)');
      expect(visibleOptions()).to.eql(['Sirup (Vanille)']);
    });

    it('hides everything when nothing matches', (): void => {
      filterBy('.*');
      expect(visibleOptions()).to.eql([]);
    });
  });

  describe('Latin letters that do not decompose', () => {
    const labels: string[] = ['Straße', 'Müller', 'Ærø', 'Łódź'];

    beforeEach((): void => {
      mount(options(labels));
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-textfield');
    });

    it('finds the sharp s when it is typed as a double s', (): void => {
      filterBy('strasse');
      expect(visibleOptions()).to.eql(['Straße']);
    });

    it('finds the sharp s when it is typed as written', (): void => {
      filterBy('straße');
      expect(visibleOptions()).to.eql(['Straße']);
    });

    it('finds the slashed o and the ligature typed as ASCII', (): void => {
      filterBy('aero');
      expect(visibleOptions()).to.eql(['Ærø']);
    });

    it('finds the stroked l typed as a plain l', (): void => {
      filterBy('lodz');
      expect(visibleOptions()).to.eql(['Łódź']);
    });

    it('does not resolve an umlaut to its transcription', (): void => {
      filterBy('mueller');
      expect(visibleOptions()).to.eql([]);
    });
  });

  describe('characters the editor cannot see in the label', () => {
    const nbsp = 'Cr\u00e8me\u00a0br\u00fbl\u00e9e';
    const softHyphen = 'Do\u00adnau';
    const zeroWidth = 'Ti\u200bramisu';
    const labels: string[] = [nbsp, softHyphen, zeroWidth];

    beforeEach((): void => {
      mount(options(labels));
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-textfield');
    });

    it('matches across a no-break space when a plain space is typed', (): void => {
      filterBy('creme brulee');
      expect(visibleOptions()).to.eql([nbsp]);
    });

    it('matches across a soft hyphen', (): void => {
      filterBy('donau');
      expect(visibleOptions()).to.eql([softHyphen]);
    });

    it('matches across a zero width space', (): void => {
      filterBy('tiramisu');
      expect(visibleOptions()).to.eql([zeroWidth]);
    });
  });

  describe('scripts whose combining marks carry meaning', () => {
    // Thai vowel signs are nonspacing marks, but they distinguish syllables rather
    // than accent them. Removing them would fold these three labels into one and
    // the filter would stop narrowing anything for Thai.
    const labels: string[] = ['กิน', 'กีฬา', 'กุญแจ'];

    beforeEach((): void => {
      mount(options(labels));
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-textfield');
    });

    it('keeps Thai vowel signs, so the filter still narrows', (): void => {
      filterBy('กิ');
      expect(visibleOptions()).to.eql(['กิน']);
    });
  });

  describe('predefined filter items', () => {
    beforeEach((): void => {
      mount(
        options(optionLabels),
        `<select class="t3js-formengine-multiselect-filter-dropdown">
          <option value=""></option>
          <option value="creme">creme</option>
        </select>`
      );
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-dropdown');
    });

    it('applies a predefined filter value on change', (): void => {
      filterField.value = 'creme';
      filterField.dispatchEvent(new Event('change', { bubbles: true }));
      expect(visibleOptions()).to.eql(['Crème brûlée', 'Cremeschnitte']);
    });
  });

  describe('option groups', () => {
    beforeEach((): void => {
      mount(`
        <optgroup label="Desserts">${options(['Crème brûlée', 'Tiramisu'])}</optgroup>
        <optgroup label="Namen">${options(['Müller'])}</optgroup>`);
      filterField = root.querySelector('.t3js-formengine-multiselect-filter-textfield');
    });

    const visibleGroups = (): string[] => Array
      .from(root.querySelectorAll('optgroup'))
      .filter((group: HTMLOptGroupElement): boolean => !group.hidden)
      .map((group: HTMLOptGroupElement): string => group.label);

    it('hides a group once all of its options are filtered away', (): void => {
      filterBy('creme');
      expect(visibleOptions()).to.eql(['Crème brûlée']);
      expect(visibleGroups()).to.eql(['Desserts']);
    });

    it('shows every group again for an empty filter', (): void => {
      filterBy('creme');
      filterBy('');
      expect(visibleGroups()).to.eql(['Desserts', 'Namen']);
    });
  });
});
