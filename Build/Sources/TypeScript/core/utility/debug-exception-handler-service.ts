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
 * Simple helper, loaded (inline) for the DebugExceptionHandler.
 * This adds a toggle button to show/hide stack trace details,
 * and a "copy" button for filenames.
 * Since DebugExceptionHandler does not utilize any ModuleLoaders,
 * this here has zero dependencies.
 * @internal
 */
document.addEventListener('DOMContentLoaded', () => {
  function makeRelativePath(absolutePath: string): string {
    const element = document.querySelector('div[data-project-path]');
    if(!(element instanceof HTMLElement)) {
      return absolutePath;
    }
    const projectPath = element?.dataset.projectPath || '';
    if (!projectPath) {
      return absolutePath;
    }
    if (!absolutePath.startsWith(projectPath)) {
      return absolutePath;
    }
    return absolutePath.substring(projectPath.length);
  }

  function createCopyFilenameWithLinenumberToClipboardButton(): HTMLButtonElement {
    const button = document.createElement('button');
    button.className = 'copy-button';
    button.setAttribute('title', 'Copy file path and line-number to clipboard');
    button.innerHTML = getCopyIcon();
    return button;
  }

  function getCopyIcon(): string {
    return `<svg aria-label="Copy" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg> <span>Copy path</span>`;
  }

  // Temporary state icon after data copied to clipboard.
  function getCheckmarkIcon(): string {
    return `<svg aria-label="Green checkmark" width="16" height="12" viewBox="0 0 16 16" fill="green" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 8L6 12L14 4" stroke="green" stroke-width="2" fill="none"/>
            </svg> <span>Successfully copied to clipboard!</span>`;
  }

  // Temporary state icon after data copied to clipboard.
  function getErrorIcon(): string {
    return `<svg aria-label="Error" width="12" height="12" viewBox="0 0 16 16" fill="red" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 3L13 13M13 3L3 13" stroke="red" stroke-width="2" fill="none"/>
            </svg> <span>Could not copy to clipboard (https only)!</span>`;
  }

  function trimWhitespace(plainText: string): string {
    return plainText
      .trim()
      .split('\n')
      .map(line => line.trimStart())
      .map(line => line.replace(/ {2,}/g, ' '))
      .join('\n')
      .replace(/\n{3,}/g, '\n\n');
    /*
    return plainText
      .trim()
      .split('\n')
      .map(line => line.trimStart()) // Remove leading whitespace from each line
      .join('\n')
      .replace(/\n{2,}/g, '\n') // Collapse multiple newlines to single newline
      .replace(/ {2,}/g, ' '); // Collapse multiple spaces to single space
    */
  }

  // Add copy buttons next to every trace-file-path element
  document.querySelectorAll('.trace-file-path strong').forEach(traceFilePathElement => {
    if (traceFilePathElement instanceof HTMLElement) {
      const copyButton = createCopyFilenameWithLinenumberToClipboardButton();
      copyButton.addEventListener('click', () => {
        const filename = makeRelativePath(traceFilePathElement.textContent?.trim() || '');
        const lineNumber = traceFilePathElement.getAttribute('data-lineno') || '';
        const fullPath = `${filename}:${lineNumber}`;
        try {
          navigator.clipboard.writeText(fullPath).then(() => {
            // Turn copy button into a checkmark to indicate successful action. Revert after 5 seconds.
            copyButton.innerHTML = getCheckmarkIcon();
            setTimeout(() => copyButton.innerHTML = getCopyIcon(), 5000);
          });
        } catch {
          copyButton.innerHTML = getErrorIcon();
        }
      });
      traceFilePathElement.parentElement.after(copyButton);
    }
  });

  // Create a div to hold toggle and copy buttons
  const traceToggleDiv = document.createElement('div');
  traceToggleDiv.className = 'trace-toggle';
  traceToggleDiv.innerHTML = `
  <div class="callout" id="stack-export">
      <div class="callout-title">Stack Trace</div>
      <div class="callout-body">
          <p>
              You can copy the contents of this stack trace into the clipboard, to paste this error and get help.
              Be sure to scan it for any sensitive data, which you might want to redact.
              You can toggle the stack trace between a full and a minimal view (without file contents).
          </p>
      </div>
  </div>
  <div id="plaintextFallback"></div>
  `;

  const toggleButton = document.createElement('button');
  toggleButton.textContent = 'Toggle details';
  toggleButton.className = 'stacktrace-action-button';
  toggleButton.setAttribute('title', 'Toggle visibility of stack trace between full and minimal view (without file contents)');
  toggleButton.addEventListener('click', () => {
    document.querySelectorAll('.trace-file-content').forEach(content => {
      if (content instanceof HTMLElement) {
        content.style.display = content.style.display === 'none' ? '' : 'none';
      }
    });
  });

  const copyAllPlaintextButton = document.createElement('button');
  const copyAllPlaintextButtonLabel = 'Copy plaintext stack trace';
  copyAllPlaintextButton.textContent = copyAllPlaintextButtonLabel;
  copyAllPlaintextButton.className = 'stacktrace-action-button';
  copyAllPlaintextButton.setAttribute('title', 'Copy plaintext stack trace to clipboard');
  copyAllPlaintextButton.addEventListener('click', () => {
    // Clone the current contents and remove 'trace-file-content' plus 'copy-button' from the clone, so it's not copied to clipboard.
    const traceBody = document.querySelector('.trace')?.cloneNode(true) as HTMLElement;
    if (traceBody) {
      traceBody.querySelectorAll('.trace-file-content').forEach(el => el.replaceWith(document.createTextNode('\n')));
      traceBody.querySelectorAll('.copy-button').forEach(el => el.remove());

      traceBody.querySelectorAll('div').forEach(div => div.appendChild(document.createTextNode('\n')));
      traceBody.querySelectorAll('span').forEach(span => span.appendChild(document.createTextNode(' ')));

      try {
        navigator.clipboard.writeText(trimWhitespace(traceBody.innerText)).then(() => {
          copyAllPlaintextButton.innerHTML = getCheckmarkIcon();
          setTimeout(() => copyAllPlaintextButton.innerHTML = copyAllPlaintextButtonLabel, 5000);
        });
      } catch {
        copyAllPlaintextButton.innerHTML = getErrorIcon();
        const plaintextFallbackContent = document.createElement('pre');
        plaintextFallbackContent.className = 'plaintextFallback';
        plaintextFallbackContent.innerText = trimWhitespace(traceBody.innerText);
        const plaintextFallback = document.getElementById('plaintextFallback');
        plaintextFallback.replaceChildren(plaintextFallbackContent);
        plaintextFallback.scrollIntoView({ behavior: 'smooth', block: 'center' });

        try {
          const range = document.createRange();
          range.selectNodeContents(plaintextFallbackContent);
          const selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
        } catch {
          // Wow, your browser is painful. No fallback for the fallback here.
        }
      }
    }
  });

  const actionButtons = document.querySelector('#stacktrace-action-buttons');
  if (actionButtons) {
    actionButtons.appendChild(toggleButton);
    actionButtons.appendChild(copyAllPlaintextButton);
  }

  const traceBody = document.querySelector('.trace');
  if (traceBody) {
    traceBody.after(traceToggleDiv);
  }
});
