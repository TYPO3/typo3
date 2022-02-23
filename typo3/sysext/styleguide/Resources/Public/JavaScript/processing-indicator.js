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
 * Javascript functions regarding the TCA module
 */
import NProgress from 'nprogress';
import Icons from '@typo3/backend/icons.js';
import Notification from '@typo3/backend/notification.js';

let itemProcessing = 0;

Icons.getIcon('spinner-circle-dark', Icons.sizes.small).then(function(spinner) {
  document.querySelectorAll('.t3js-generator-action').forEach((button) => {
    let url = button.dataset.href;
    button.addEventListener('click', (e) => {
      e.preventDefault();
      let originalIcon = button.querySelector('i').outerHTML;
      let disabledButton = button.parentNode.querySelector('button.disabled');

      e.target.querySelector('i').outerHTML = spinner;
      NProgress.start();
      itemProcessing++
      e.target.classList.add('disabled');

      // Trigger generate action
      fetch(url).then((response) => {
        if(response.status !== 200) {
          return Promise.reject({message: 'Error ' + response.status + ' ' + response.statusText});
        }

        return response.json();
      })
      .then((json) => {
        itemProcessing--
        Notification.showMessage(json.title, json.body, json.status, 5);

        // Hide nprogress only if all items done loading/processing
        if(itemProcessing === 0) {
          NProgress.done();
        }

        // Set button states
        e.target.querySelector('.t3js-icon').outerHTML = originalIcon;
        disabledButton.classList.remove('disabled');
      }).finally(() => {
        // Party when done
      }).catch((error) => {
        // Action failed, reset to its original state
        NProgress.done();

        e.target.querySelector('.t3js-icon').outerHTML = originalIcon;
        e.target.classList.remove('disabled');
        Notification.error('', error.message, 5);
      });
    });
  });
});
