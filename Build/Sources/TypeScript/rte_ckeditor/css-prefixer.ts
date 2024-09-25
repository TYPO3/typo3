import * as csstree from 'css-tree';
import type { CssNode, Selector, Url } from 'css-tree';

type NodeProcessor = (node: CssNode) => void;

export function prefixAndRebaseCss(contents: string, currentPath: string, prefix: string): string {
  const ast = csstree.parse(contents);
  const prefixer = cssPrefixer(prefix);
  const relocator = cssRelocator(currentPath);
  csstree.walk(ast, (node) => {
    relocator(node);
    return prefixer(node);
  });
  return csstree.generate(ast);
}

export function cssRelocator(currentPath: string): NodeProcessor {
  return (node: CssNode): void => {
    if (node.type !== 'Url') {
      return;
    }

    const url: Url = node;
    if (
      url.value.startsWith('data:') ||
      url.value.startsWith('/') ||
      url.value.includes('://')
    ) {
      return;
    }

    const absoluteUrl = new URL(currentPath.replace(/\?.+/, '') + '/../' + url.value, document.baseURI);
    url.value = absoluteUrl.pathname + absoluteUrl.search;
  }
}

export function cssPrefixer(prefix: string): NodeProcessor {
  if (prefix === '') {
    return (): void => {
      return;
    };
  }

  return (node: CssNode): void|typeof csstree.walk.skip => {
    if (node.type !== 'Selector') {
      return;
    }
    const selector: Selector = node;
    if (selector.children.isEmpty) {
      return csstree.walk.skip;
    }

    const prefixAst = csstree.parse(prefix + '{}');
    const prefixSelector = csstree.find(prefixAst, (node) => node.type === 'Selector') as Selector;
    if (prefixSelector === null) {
      throw new Error(`Failed to parse "${prefix}" as CSS prefix`);
    }

    if (
      selector.children.first.type === 'PseudoClassSelector' &&
      selector.children.first.name === 'root'
    ) {
      /* Replace `:root foo bar {}` with `#prefix foo bar {}` */
      selector.children.shift();
      selector.children.prependList(prefixSelector.children);
      return csstree.walk.skip;
    }

    /* Replace `html foo bar {}` or `body foo bar {}` with `#prefix foo bar {}` */
    let htmlBodyHasBeenReplaced = false;
    selector.children.forEach((childNode, item) => {
      if (
        childNode.type === 'TypeSelector' &&
        ['html', 'body'].includes(childNode.name.toLowerCase())
      ) {
        if (!htmlBodyHasBeenReplaced) {
          selector.children.replace(item, prefixSelector.children);
          htmlBodyHasBeenReplaced = true;
        } else {
          /* Ensure `html body` is replaced with `#prefix` instead of `#prefix #prefix` */
          selector.children.remove(item);
        }
      }
    });
    if (htmlBodyHasBeenReplaced) {
      return csstree.walk.skip;
    }

    selector.children.unshift({ type: 'Combinator', loc: null, name: ' ' });
    selector.children.prependList(prefixSelector.children);

    return csstree.walk.skip;
  }
}
