define(["exports","../lit-html","../directive"],(function(exports,litHtml,directive){"use strict";
/**
	 * @license
	 * Copyright 2020 Google LLC
	 * SPDX-License-Identifier: BSD-3-Clause
	 */const o=directive.directive(class extends directive.Directive{constructor(t){if(super(t),t.type!==directive.PartType.CHILD)throw Error("templateContent can only be used in child bindings")}render(r){return this.at===r?litHtml.noChange:(this.at=r,document.importNode(r.content,!0))}});exports.templateContent=o,Object.defineProperty(exports,"__esModule",{value:!0})}));
