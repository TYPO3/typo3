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
 * Module: @typo3/workspaces/frontend-change-highlighter
 *
 * Highlights workspace changes in the frontend preview. The FrontendPreviewChangeMarker event
 * listener wraps changed records in comment markers. When highlighting is switched on, the live
 * version of the page is fetched, in which all records are marked, and the text of each changed
 * record is compared with its live counterpart.
 *
 * Loaded in the frontend, so it must not import anything: there is no import map.
 */

type ChangeState = 'new' | 'modified' | 'moved';

interface MarkerData {
  table: string;
  uid: number;
  state?: ChangeState;
}

export interface MarkedRecord {
  key: string;
  state: ChangeState | null;
  /** The nodes rendered for the record, located between the markers */
  nodes: Node[];
}

interface Token {
  node: Text;
  start: number;
  end: number;
  value: string;
  spaceAfter: boolean;
}

type Undo = () => void;

const noop: Undo = (): void => undefined;

const markerStart = 'typo3-workspace-record ';
const markerEnd = '/typo3-workspace-record';
const highlightClass = 'typo3-workspace-highlight';
const storageKey = 'typo3-workspace-preview-highlight';
const tokenPattern = /[\p{L}\p{N}_]+|[^\s\p{L}\p{N}_]/gu;
const ignoredElements = new Set(['SCRIPT', 'STYLE', 'TEMPLATE', 'NOSCRIPT', 'TEXTAREA']);
// Upper bound for the LCS table, larger records are only outlined
const maxDiffCells = 250_000;

const styles = `
  .${highlightClass} .typo3-workspace-change { outline: 3px solid var(--typo3-workspace-change-color); outline-offset: 2px; box-shadow: 0 0 0 5px #fff; }
  .${highlightClass} .typo3-workspace-change--new { --typo3-workspace-change-color: #1e7b34; }
  .${highlightClass} .typo3-workspace-change--modified { --typo3-workspace-change-color: #a05100; outline-style: dashed; }
  .${highlightClass} .typo3-workspace-change--moved { --typo3-workspace-change-color: #1f5fbf; outline-style: dotted; }
  ins.typo3-workspace-ins { background: #cdeccf; color: #0f3d14; text-decoration: underline; text-decoration-color: #1e7b34; }
  del.typo3-workspace-del { background: #f9d0d0; color: #6b1010; text-decoration: line-through; }
`;

/**
 * Collects the records wrapped in markers below the given root. Markers whose end is not a
 * sibling of the start, e.g. due to invalid markup, are ignored.
 *
 * @param outermostOnly skip records nested in other records, their changes are part of the outer diff
 */
export function collectMarkedRecords(root: Node, outermostOnly: boolean): MarkedRecord[] {
  const records: { position: number, record: MarkedRecord }[] = [];
  const open: { position: number, comment: Comment, data: MarkerData | null }[] = [];
  let position = 0;
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_COMMENT);
  while (walker.nextNode()) {
    const comment = walker.currentNode as Comment;
    if (comment.data.startsWith(markerStart)) {
      open.push({ position: position++, comment, data: parseMarker(comment.data.substring(markerStart.length)) });
      continue;
    }
    if (comment.data !== markerEnd) {
      continue;
    }
    const start = open.pop();
    if (start === undefined || start.data === null || start.comment.parentNode !== comment.parentNode) {
      continue;
    }
    if (outermostOnly && open.length > 0) {
      continue;
    }
    const nodes: Node[] = [];
    for (let node = start.comment.nextSibling; node !== null && node !== comment; node = node.nextSibling) {
      nodes.push(node);
    }
    records.push({ position: start.position, record: { key: start.data.table + ':' + start.data.uid, state: start.data.state ?? null, nodes } });
  }
  // Nested records end first, restore document order
  return records.sort((a, b) => a.position - b.position).map(entry => entry.record);
}

/**
 * Outlines the top-level elements of a changed record, which also makes changes
 * visible that are not text, e.g. a replaced image.
 */
export function outlineRecord(record: MarkedRecord, state: ChangeState): Undo {
  const elements = record.nodes.filter((node): node is HTMLElement => node instanceof HTMLElement);
  const classes = ['typo3-workspace-change', 'typo3-workspace-change--' + state];
  const originalClasses = elements.map(element => element.getAttribute('class'));
  elements.forEach(element => element.classList.add(...classes));
  return () => elements.forEach((element, index) => {
    const originalClass = originalClasses[index];
    if (originalClass === null) {
      element.removeAttribute('class');
    } else {
      element.setAttribute('class', originalClass);
    }
  });
}

/**
 * Word-based diff of the visible text of the live and the workspace rendering. Inserted
 * words are wrapped in <ins>, removed words are added as <del> at their former position.
 * Only text nodes are touched, the returned function restores them.
 */
export function highlightTextDifferences(record: MarkedRecord, live: MarkedRecord): Undo {
  const liveTokens = collectTokens(live.nodes);
  const workspaceTokens = collectTokens(record.nodes);
  const diff = diffTokens(liveTokens.map(token => token.value), workspaceTokens.map(token => token.value));
  if (diff === null) {
    return noop;
  }

  const insertions = new Map<Text, [number, number][]>();
  let current: [number, number] | null = null;
  workspaceTokens.forEach((token, index) => {
    if (!diff.inserted.has(index)) {
      current = null;
      return;
    }
    if (current !== null && workspaceTokens[index - 1].node === token.node && !diff.deleted.has(index)) {
      current[1] = token.end;
      return;
    }
    current = [token.start, token.end];
    append(insertions, token.node, current);
  });

  const deletions = new Map<Text, [number, string][]>();
  for (const [anchor, liveIndexes] of diff.deleted) {
    const text = liveIndexes.map(liveIndex => liveTokens[liveIndex].value + (liveTokens[liveIndex].spaceAfter ? ' ' : '')).join('');
    if (anchor < workspaceTokens.length) {
      const token = workspaceTokens[anchor];
      append(deletions, token.node, [token.start, text]);
    } else if (workspaceTokens.length > 0) {
      const token = workspaceTokens[workspaceTokens.length - 1];
      append(deletions, token.node, [token.end, ' ' + text.trimEnd()]);
    }
  }

  const undos = [...new Set([...insertions.keys(), ...deletions.keys()])]
    .map(node => rewriteTextNode(node, insertions.get(node) ?? [], deletions.get(node) ?? []));
  return () => undos.forEach(undo => undo());
}

/**
 * Returns the indexes of inserted tokens of "to" and the indexes of deleted tokens of "from",
 * grouped by the index of the "to" token they were located before. Returns null for texts
 * too large to compare.
 */
export function diffTokens(from: string[], to: string[]): { inserted: Set<number>, deleted: Map<number, number[]> } | null {
  let prefix = 0;
  while (prefix < from.length && prefix < to.length && from[prefix] === to[prefix]) {
    prefix++;
  }
  let suffix = 0;
  while (suffix < from.length - prefix && suffix < to.length - prefix && from[from.length - 1 - suffix] === to[to.length - 1 - suffix]) {
    suffix++;
  }
  const n = from.length - prefix - suffix;
  const m = to.length - prefix - suffix;
  if ((n + 1) * (m + 1) > maxDiffCells) {
    return null;
  }

  // lengths[i * (m + 1) + j] = LCS length of from[prefix + i..] and to[prefix + j..]
  const lengths = new Uint32Array((n + 1) * (m + 1));
  for (let i = n - 1; i >= 0; i--) {
    for (let j = m - 1; j >= 0; j--) {
      lengths[i * (m + 1) + j] = from[prefix + i] === to[prefix + j]
        ? lengths[(i + 1) * (m + 1) + j + 1] + 1
        : Math.max(lengths[(i + 1) * (m + 1) + j], lengths[i * (m + 1) + j + 1]);
    }
  }
  const inserted = new Set<number>();
  const deleted = new Map<number, number[]>();
  let i = 0;
  let j = 0;
  while (i < n || j < m) {
    if (i < n && j < m && from[prefix + i] === to[prefix + j]) {
      i++;
      j++;
    } else if (i < n && (j === m || lengths[(i + 1) * (m + 1) + j] >= lengths[i * (m + 1) + j + 1])) {
      // Deletions first, so removed words are placed before the words replacing them
      append(deleted, prefix + j, prefix + i++);
    } else {
      inserted.add(prefix + j++);
    }
  }
  return { inserted, deleted };
}

/**
 * Highlights the given changes and returns a function reverting all modifications.
 * Without live version, e.g. when it could not be fetched, changes are only outlined.
 */
export function highlightChanges(changes: MarkedRecord[], live: Map<string, MarkedRecord> | null): Undo {
  const undos = changes.map((change): Undo => {
    const liveRecord = live?.get(change.key);
    // A modified record missing in live, e.g. because it is hidden there, is new to visitors
    const state = change.state === 'modified' && live !== null && liveRecord === undefined ? 'new' : change.state ?? 'modified';
    const undoOutline = outlineRecord(change, state);
    const undoText = state !== 'new' && liveRecord !== undefined ? highlightTextDifferences(change, liveRecord) : noop;
    return () => {
      undoText();
      undoOutline();
    };
  });
  return () => undos.forEach(undo => undo());
}

async function fetchLiveRecords(url: string): Promise<Map<string, MarkedRecord> | null> {
  try {
    const response = await fetch(url, { credentials: 'same-origin' });
    if (!response.ok) {
      return null;
    }
    // DOMParser creates an inert document, scripts of the live page are not executed
    const liveDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
    const records = collectMarkedRecords(liveDocument.body, false);
    if (records.length === 0) {
      // The live version was not rendered for comparison, e.g. for preview links without backend login
      return null;
    }
    const map = new Map<string, MarkedRecord>();
    records.forEach(record => map.has(record.key) || map.set(record.key, record));
    return map;
  } catch {
    return null;
  }
}

function append<K, V>(map: Map<K, V[]>, key: K, value: V): void {
  const values = map.get(key);
  if (values === undefined) {
    map.set(key, [value]);
  } else {
    values.push(value);
  }
}

function collectTokens(nodes: Node[]): Token[] {
  const tokens: Token[] = [];
  for (const root of nodes) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: (node: Node): number => {
        for (let parent = node.parentElement; parent !== null && parent !== root.parentNode; parent = parent.parentElement) {
          if (ignoredElements.has(parent.tagName)) {
            return NodeFilter.FILTER_REJECT;
          }
        }
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    for (let node = root.nodeType === Node.TEXT_NODE ? root : walker.nextNode(); node !== null; node = walker.nextNode()) {
      const text = node as Text;
      for (const match of text.data.matchAll(tokenPattern)) {
        const end = match.index + match[0].length;
        tokens.push({ node: text, start: match.index, end, value: match[0], spaceAfter: /^\s/.test(text.data.substring(end)) });
      }
    }
  }
  return tokens;
}

function rewriteTextNode(node: Text, insertions: [number, number][], deletions: [number, string][]): Undo {
  const events = [
    ...deletions.map(([at, text]) => ({ at, end: at, text, type: 'del' })),
    ...insertions.map(([at, end]) => ({ at, end, text: node.data.substring(at, end), type: 'ins' })),
  ].sort((a, b) => a.at - b.at || (a.type === 'del' ? -1 : 1));
  const replacement: Node[] = [];
  let position = 0;
  for (const event of events) {
    if (event.at > position) {
      replacement.push(document.createTextNode(node.data.substring(position, event.at)));
    }
    const element = document.createElement(event.type);
    element.className = 'typo3-workspace-' + event.type;
    element.textContent = event.text;
    replacement.push(element);
    position = Math.max(position, event.end);
  }
  replacement.push(document.createTextNode(node.data.substring(position)));
  node.replaceWith(...replacement);
  return () => {
    replacement[0].parentNode?.insertBefore(node, replacement[0]);
    replacement.forEach(replaced => replaced.parentNode?.removeChild(replaced));
  };
}

function parseMarker(json: string): MarkerData | null {
  try {
    const data = JSON.parse(json);
    return typeof data?.table === 'string' && typeof data?.uid === 'number' ? data as MarkerData : null;
  } catch {
    return null;
  }
}

function readStoredState(): boolean {
  try {
    return window.localStorage.getItem(storageKey) === '1';
  } catch {
    return false;
  }
}

function storeState(enabled: boolean): void {
  try {
    window.localStorage.setItem(storageKey, enabled ? '1' : '0');
  } catch {
    // Storage is not available, e.g. in private browsing mode
  }
}

export function initialize(): void {
  const toggle = document.getElementById('typo3-preview-highlight-changes');
  if (!(toggle instanceof HTMLInputElement)) {
    return;
  }
  const changes = collectMarkedRecords(document.body, true).filter(record => record.state !== null);
  const counter = document.getElementById('typo3-preview-highlight-changes-count');
  if (counter !== null) {
    counter.textContent = String(changes.length);
  }
  if (changes.length === 0) {
    toggle.disabled = true;
    return;
  }

  const styleSheet = new CSSStyleSheet();
  styleSheet.replaceSync(styles);
  document.adoptedStyleSheets = [...document.adoptedStyleSheets, styleSheet];

  let live: Promise<Map<string, MarkedRecord> | null> | null = null;
  let undo: Undo | null = null;
  const update = async (): Promise<void> => {
    if (!toggle.checked) {
      undo?.();
      undo = null;
      document.documentElement.classList.remove(highlightClass);
      return;
    }
    toggle.disabled = true;
    live ??= fetchLiveRecords(toggle.dataset.liveUrl ?? '');
    const liveRecords = await live;
    toggle.disabled = false;
    if (toggle.checked && undo === null) {
      undo = highlightChanges(changes, liveRecords);
      document.documentElement.classList.add(highlightClass);
    }
  };
  toggle.checked = readStoredState();
  update();
  toggle.addEventListener('change', (): void => {
    storeState(toggle.checked);
    update();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initialize);
} else {
  initialize();
}
