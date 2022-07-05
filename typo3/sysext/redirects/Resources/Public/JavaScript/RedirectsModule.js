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
define(["require","exports","TYPO3/CMS/Core/Event/RegularEvent"],(function(e,t,n){"use strict";return new class{constructor(){const e=document.querySelectorAll('form[data-on-submit="processNavigate"]');e.length>0&&(new n("change",this.executeSubmit.bind(this)).delegateTo(document,'[data-on-change="submit"]'),e.forEach(e=>new n("submit",this.processNavigate.bind(this)).bindTo(e)))}executeSubmit(e){const t=e.target;t instanceof HTMLSelectElement&&t.form.submit()}processNavigate(e){const t=e.target;if(!(t instanceof HTMLFormElement))return;e.preventDefault();const n=t.elements.namedItem("paginator-target-page"),a=parseInt(n.dataset.numberOfPages,10);let r=n.dataset.url,s=parseInt(n.value,10);s>a?s=a:s<1&&(s=1),r=r.replace("987654322",s.toString()),self.location.href=r}}}));