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

import { GridEditor } from '@typo3/backend/grid-editor.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/backend/grid-editor-test:', () => {

  describe('tests for stripMarkup', () => {
    it('works with string which contains html markup only', () => {
      expect(GridEditor.stripMarkup('<b>\'formula\': "x > y"</b>')).to.equal('\'formula\': "x > y"');
    });
    it('works with string which contains html markup and normal text', () => {
      expect(GridEditor.stripMarkup('<b>foo</b> bar')).to.equal('foo bar');
    });
  });

  describe('tests for content type restrictions', () => {
    const createGridEditor = (cell: Record<string, unknown>): any => {
      const gridEditor = document.createElement('typo3-backend-grid-editor') as any;
      gridEditor.colCount = 1;
      gridEditor.rowCount = 1;
      gridEditor.data = [[{ spanned: 0, rowspan: 1, colspan: 1, name: 'Main', column: 0, ...cell }]];
      return gridEditor;
    };

    it('exports allowed and disallowed content types of a cell', () => {
      const result = createGridEditor({ allowedContentTypes: 'header, textmedia', disallowedContentTypes: 'html' }).export2LayoutRecord();
      expect(result).to.contain('\t\t\t\t\tallowedContentTypes = header, textmedia\n');
      expect(result).to.contain('\t\t\t\t\tdisallowedContentTypes = html\n');
    });

    it('does not export empty content type restrictions', () => {
      const result = createGridEditor({ allowedContentTypes: '', disallowedContentTypes: '' }).export2LayoutRecord();
      expect(result).not.to.contain('allowedContentTypes');
    });

    it('normalizes the list of content types', () => {
      const gridEditor = createGridEditor({});
      gridEditor.setAllowedContentTypes(' header,,textmedia , header<b>x</b>', 0, 0);
      gridEditor.setDisallowedContentTypes(' , ', 0, 0);
      expect(gridEditor.data[0][0].allowedContentTypes).to.equal('header, textmedia, headerx');
      expect(gridEditor.data[0][0].disallowedContentTypes).to.equal('');
    });
  });

});
