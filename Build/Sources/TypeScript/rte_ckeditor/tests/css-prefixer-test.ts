import { prefixAndRebaseCss } from '@typo3/rte-ckeditor/css-prefixer.js';
import { expect } from '@open-wc/testing';
import type { } from 'mocha';

describe('@typo3/rte-ckeditor/css-prefixer-test', () => {
  describe('relative URL in css is relocated', () => {
    it('relocates URLs', () => {
      const result = prefixAndRebaseCss(
        `
          foo {
            background-image: url('../foo.png?v=2')
          }
        `,
        '/assets/css/style.css?v=3',
        ''
      );

      expect(result).to.equal('foo{background-image:url(/assets/foo.png?v=2)}');
    });
  });
  describe('data URI in css is not relocated', () => {
    it('does not relocate data URIs', () => {
      const result = prefixAndRebaseCss(
        `
          foo {
            background-image: url('data:image/gif;base64,R0lGODlhAQABAPAAAP///wAAACH5BAUAAAAALAAAAAABAAEAQAICRAEAOw==')
          }
        `,
        '/assets/css/style.css?v=3',
        ''
      );

      expect(result).to.equal('foo{background-image:url(data:image/gif;base64,R0lGODlhAQABAPAAAP///wAAACH5BAUAAAAALAAAAAABAAEAQAICRAEAOw==)}');
    });
  });
  describe('css is prefixed', () => {
    interface CssDataSet {
      prefix: string,
      source: string,
      target: string,
    }
    function cssIsPrefixedForScssDataProvider(): Record<string, CssDataSet> {
      return {
        mini: {
          prefix: '#foo',
          source: 'div, bar {}',
          target: '#foo div,#foo bar{}',
        },
        rootSelector: {
          prefix: '#foo',
          source: ':root {}',
          target: '#foo{}',
        },
        htmlSelector: {
          prefix: '#foo',
          source: 'html {}',
          target: '#foo{}',
        },
        bodySelector: {
          prefix: '#foo',
          source: 'body {}',
          target: '#foo{}',
        },
        htmlAndBodySelector: {
          prefix: '#foo',
          source: 'html body foo {}',
          target: '#foo  foo{}',
        },
        minified: {
          prefix: '#foo .bar',
          source: 'div{color:#abc;}body{color:#abc;}',
          target: '#foo .bar div{color:#abc}\n#foo .bar{color:#abc}',
        },
        chained: {
          prefix: 'foo',
          source: 'body,html{color:#abc}html,body{color:#abc}',
          target: 'foo,foo{color:#abc}\nfoo,foo{color:#abc}',
        },
        emptyIdSelector: {
          prefix: 'foo',
          source: '#html {}',
          target: 'foo #html{}',
        },
        emptyClassSelector: {
          prefix: 'foo',
          source: '.html {}',
          target: 'foo .html{}',
        },
        customElementsSelector: {
          prefix: 'foo',
          source: 'my---html---div { color: #abc; }',
          target: 'foo my---html---div{color:#abc}',
        },
        strangeCustomSelectors: {
          prefix: 'foo',
          source: 'my___html___div { color: #abc; }',
          target: 'foo my___html___div{color:#abc}',
        },
        hasSelector: {
          prefix: 'foo',
          source: 'ul li:has(> p.text-center) { color: red; }',
          target: 'foo ul li:has(>p.text-center){color:red}',
        },
        hasOnRootSelector: {
          prefix: 'foo',
          source: ':root:has(>header) { color: red; }',
          target: 'foo:has(>header){color:red}',
        },
        hasSelectorWithInvalidNestingSelector: {
          prefix: 'foo',
          source: 'ul li:has(& > p.text-center) { color: red; }',
          target: 'foo ul li:has(&>p.text-center){color:red}',
        },
        whereSelector: {
          prefix: 'foo',
          source: ':where(ol, ul) :where(ol, ul) ol { color: red; }',
          target: 'foo :where(ol,ul) :where(ol,ul) ol{color:red}',
        },
        isSelector: {
          prefix: 'foo',
          source: ':is(ol, ul) :is(ol, ul) ol { color: red; }',
          target: 'foo :is(ol,ul) :is(ol,ul) ol{color:red}',
        },
        containerQuery: {
          prefix: 'foo',
          source: '.card h2 { font-size: 1em; } @container (min-width: 700px) { .card h2 { font-size: 2em; } }',
          target: 'foo .card h2{font-size:1em}\n@container (min-width:700px){foo .card h2{font-size:2em}\n}',
        },
        supportsQuery: {
          prefix: 'foo',
          source: '@supports selector(.a) { .a{ display:block } }',
          target: '@supports selector(foo .a){foo .a{display:block}\n}',
        },
        complete: {
          prefix: 'foo',
          source: getOriginCss(),
          target: getPrefixedCss(),
        },
      }
    }

    for (const [name, dataSet] of Object.entries(cssIsPrefixedForScssDataProvider())) {
      it('can prefix ' + name, () => {
        const target = prefixAndRebaseCss(dataSet.source, 'foo.css', dataSet.prefix)
          .replace(/}/g, '}\n').trim();
        expect(target).to.equal(dataSet.target);
      });
    }
  });
});


export function getOriginCss() {
  return`
body{margin:0}

html,
body{margin:0}

body,
html{margin:0}

BODY{margin:0}

HTML,
BODY{margin:0}

BODY,
HTML{margin:0}

.someclass{padding:0} body,html{margin:0}

body {margin:0}
body { margin:0; }
body     {margin:0}

body{
    padding: 0;
}

body{
    padding: 0;
}

body{
    font-family: var(--bs-body-font-family);
}

body,
body {
    padding: 0;
}

body > :first-child {
    margin-top: 0;
}

body[dir="ltr"] blockquote {
    padding-left: 20px;
    border-left-width: 5px;
}

body[dir="rtl"] ol,
body[dir="rtl"] ul,
body[dir="rtl"] dl {
    padding: 0;
}

.some body[dir="rtl"] ol,
.some body[dir="rtl"] ul,
.some body[dir="rtl"] dl {
    padding: 0;
}

/* minified version */
body{margin:0}html,body{margin:0}body,html{margin:0}.someclass{padding:0} body,html{margin:0}body {margin:0}body     {margin:0}body{padding: 0;}body{padding: 0;}

/* NO CHANGES HERE */
body-unofindmemargin {
    padding: 0
}

#body {
    padding: 0;
}

.body {}

.body-some {}
.some-body {}

html-unofindmemargin {
    padding: 0
}

#html {
    padding: 0;
}

.html {}

.html-some {}
.some-html {}

/* minified version */
body-unofindmemargin {padding: 0} #body {padding: 0;} .body {} .body-some {} .some-body {} html-unofindmemargin {padding: 0} #html {padding: 0;} .html {} .html-some {} .some-html {}
`;
}

export function getPrefixedCss() {
  return `
foo{margin:0}
foo,foo{margin:0}
foo,foo{margin:0}
foo{margin:0}
foo,foo{margin:0}
foo,foo{margin:0}
foo .someclass{padding:0}
foo,foo{margin:0}
foo{margin:0}
foo{margin:0}
foo{margin:0}
foo{padding:0}
foo{padding:0}
foo{font-family:var(--bs-body-font-family)}
foo,foo{padding:0}
foo>:first-child{margin-top:0}
foo[dir="ltr"] blockquote{padding-left:20px;border-left-width:5px}
foo[dir="rtl"] ol,foo[dir="rtl"] ul,foo[dir="rtl"] dl{padding:0}
.some foo[dir="rtl"] ol,.some foo[dir="rtl"] ul,.some foo[dir="rtl"] dl{padding:0}
foo{margin:0}
foo,foo{margin:0}
foo,foo{margin:0}
foo .someclass{padding:0}
foo,foo{margin:0}
foo{margin:0}
foo{margin:0}
foo{padding:0}
foo{padding:0}
foo body-unofindmemargin{padding:0}
foo #body{padding:0}
foo .body{}
foo .body-some{}
foo .some-body{}
foo html-unofindmemargin{padding:0}
foo #html{padding:0}
foo .html{}
foo .html-some{}
foo .some-html{}
foo body-unofindmemargin{padding:0}
foo #body{padding:0}
foo .body{}
foo .body-some{}
foo .some-body{}
foo html-unofindmemargin{padding:0}
foo #html{padding:0}
foo .html{}
foo .html-some{}
foo .some-html{}
`.trim();
}
