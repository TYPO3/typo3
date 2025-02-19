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
(function() {
  document.addEventListener('click', (evt: MouseEvent) => {
    const target = evt.target as HTMLElement|null;
    if (!target.classList.contains('tx-indexedsearch-page-selector')) {
      return;
    }
    evt.preventDefault();

    const data = target.dataset;
    (document.getElementById(data.prefix + '_pointer') as HTMLInputElement).value = data.pointer;
    (document.getElementById(data.prefix + '_freeIndexUid') as HTMLInputElement).value = data.freeIndexUid;
    document.forms.namedItem(data.prefix).submit();
  });
})();
