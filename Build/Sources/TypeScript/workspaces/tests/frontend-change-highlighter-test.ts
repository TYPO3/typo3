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

import { collectMarkedRecords, diffTokens, highlightChanges, type MarkedRecord } from '@typo3/workspaces/frontend-change-highlighter.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

function wrap(html: string, uid: number = 1, state: string | null = 'modified'): string {
  const marker = { table: 'tt_content', uid, ...(state === null ? {} : { state }) };
  return '<!--typo3-workspace-record ' + JSON.stringify(marker) + '-->' + html + '<!--/typo3-workspace-record-->';
}

function container(html: string): HTMLElement {
  const element = document.createElement('div');
  element.innerHTML = html;
  return element;
}

function liveRecords(html: string): Map<string, MarkedRecord> {
  return new Map(collectMarkedRecords(container(html), false).map(record => [record.key, record]));
}

function highlight(workspace: HTMLElement, live: Map<string, MarkedRecord> | null): string {
  highlightChanges(collectMarkedRecords(workspace, true), live);
  return withoutComments(workspace.innerHTML);
}

function withoutComments(html: string): string {
  return html.replace(/<!--[\s\S]*?-->/g, '');
}

describe('@typo3/workspaces/frontend-change-highlighter', (): void => {
  it('detects inserted and deleted tokens', (): void => {
    const { inserted, deleted } = diffTokens(['a', 'b', 'c', 'd'], ['a', 'x', 'c', 'd', 'e']);
    expect([...inserted]).to.deep.equal([1, 4]);
    expect([...deleted]).to.deep.equal([[1, [1]]]);
  });

  it('does not compare texts exceeding the size limit', (): void => {
    const from = Array.from({ length: 600 }, (_, index) => 'a' + index);
    const to = Array.from({ length: 600 }, (_, index) => 'b' + index);
    expect(diffTokens(from, to)).to.equal(null);
  });

  it('wraps inserted words in ins', (): void => {
    const workspace = container(wrap('<p>On the trail - and more:</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>On the trail:</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">On the trail <ins class="typo3-workspace-ins">- and more</ins>:</p>'
    );
  });

  it('adds removed words as del', (): void => {
    const workspace = container(wrap('<p>Pack light</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Pack very light</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">Pack <del class="typo3-workspace-del">very </del>light</p>'
    );
  });

  it('shows replaced words as del and ins', (): void => {
    const workspace = container(wrap('<p>Bring <strong>three</strong> shirts</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Bring <strong>two</strong> shirts</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">Bring <strong><del class="typo3-workspace-del">two</del><ins class="typo3-workspace-ins">three</ins></strong> shirts</p>'
    );
  });

  it('highlights several changes within one text node', (): void => {
    const workspace = container(wrap('<p>one TWO three FOUR five</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>one two three four five</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">one <del class="typo3-workspace-del">two </del><ins class="typo3-workspace-ins">TWO</ins> three <del class="typo3-workspace-del">four </del><ins class="typo3-workspace-ins">FOUR</ins> five</p>'
    );
  });

  it('adds words removed at the end after the last word', (): void => {
    const workspace = container(wrap('<p>Pack light</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Pack light always</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">Pack light<del class="typo3-workspace-del"> always</del></p>'
    );
  });

  it('compares non-ASCII text', (): void => {
    const workspace = container(wrap('<p>Grüße aus Köln</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Grüße aus München</p>', 1, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">Grüße aus <del class="typo3-workspace-del">München</del><ins class="typo3-workspace-ins">Köln</ins></p>'
    );
  });

  it('ignores text of scripts', (): void => {
    const workspace = container(wrap('<p>Text</p><script>let a = 2;</script>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Text</p><script>let a = 1;</script>', 1, null)))).to.not.contain('typo3-workspace-ins');
  });

  it('marks new records without comparing text', (): void => {
    const workspace = container(wrap('<p>New</p>', 3, 'new'));
    expect(highlight(workspace, liveRecords(''))).to.equal('<p class="typo3-workspace-change typo3-workspace-change--new">New</p>');
  });

  it('marks modified records missing in live as new', (): void => {
    const workspace = container(wrap('<p>Hidden in live</p>'));
    expect(highlight(workspace, liveRecords(wrap('<p>Other</p>', 2, null)))).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--new">Hidden in live</p>'
    );
  });

  it('only outlines changes without live version', (): void => {
    const workspace = container(wrap('<p>Changed</p>'));
    expect(highlight(workspace, null)).to.equal('<p class="typo3-workspace-change typo3-workspace-change--modified">Changed</p>');
  });

  it('finds live records nested in other records', (): void => {
    const workspace = container(wrap('<p>Child changed</p>', 2));
    const live = liveRecords(wrap('<div>Parent' + wrap('<p>Child</p>', 2, null) + '</div>', 1, null));
    expect(highlight(workspace, live)).to.equal(
      '<p class="typo3-workspace-change typo3-workspace-change--modified">Child <ins class="typo3-workspace-ins">changed</ins></p>'
    );
  });

  it('restores the original markup when undone', (): void => {
    const html = wrap('<p>Bring <strong>three</strong> shirts</p>');
    const workspace = container(html);
    const undo = highlightChanges(collectMarkedRecords(workspace, true), liveRecords(wrap('<p>Bring <strong>two</strong> shirts</p>', 1, null)));
    undo();
    expect(workspace.innerHTML).to.equal(html);
    expect(workspace.querySelector('strong').childNodes).to.have.length(1);
  });

  it('only collects the outermost of nested changes', (): void => {
    const records = collectMarkedRecords(container(wrap('<div>Parent' + wrap('<p>Child</p>', 2, 'new') + '</div>')), true);
    expect(records.map(record => record.key)).to.deep.equal(['tt_content:1']);
  });

  it('collects nested records in document order', (): void => {
    const records = collectMarkedRecords(container(wrap('<div>Parent' + wrap('<p>Child</p>', 2) + '</div>') + wrap('<p>Next</p>', 3)), false);
    expect(records.map(record => record.key)).to.deep.equal(['tt_content:1', 'tt_content:2', 'tt_content:3']);
  });

  it('ignores markers whose end is not a sibling of the start', (): void => {
    const html = '<!--typo3-workspace-record {"table":"tt_content","uid":1,"state":"new"}--><div><p>Broken<!--/typo3-workspace-record--></p></div><p>Rest of the page</p>';
    expect(collectMarkedRecords(container(html), true)).to.have.length(0);
  });

  it('ignores malformed markers', (): void => {
    const html = '<!--typo3-workspace-record {broken--><p>Text</p><!--/typo3-workspace-record-->';
    expect(collectMarkedRecords(container(html), true)).to.have.length(0);
  });
});
