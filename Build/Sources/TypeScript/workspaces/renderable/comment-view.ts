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

import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { repeat } from 'lit/directives/repeat.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import { nl2br } from '@typo3/core/directive/nl2br';
import labels from '~labels/workspaces.messages';

type Comment = {
  user_comment: string;
  previous_stage_title: string;
  stage_title: string;
  tstamp: string;
  user_username: string;
  user_avatar: string
};

@customElement('typo3-workspaces-comment-view')
export class CommentViewElement extends LitElement {
  @property({ type: Array })
  public comments: Comment[] = [];

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div>
        ${repeat(this.comments, (comment) => comment.tstamp, (comment) => this.renderComment(comment))}
      </div>
    `;
  }

  protected renderComment(comment: Comment): TemplateResult {
    return html`
      <div class="media">
        <div class="media-left text-center">
          <div>
            ${unsafeHTML(comment.user_avatar)}
          </div>
          ${comment.user_username}
        </div>
        <div class="panel panel-default">
          ${comment.user_comment ? html`
          <div class="panel-body">
            ${nl2br(comment.user_comment)}
          </div>
        ` : nothing}
          <div class="panel-footer">
            <span class="badge badge-success me-2">
              ${comment.previous_stage_title} ⇾ ${comment.stage_title}
            </span>
            <span class="badge badge-info">
              ${comment.tstamp ? labels.get('comment.tstamp.value', { tstamp: new Date(comment.tstamp) }) : nothing}
            </span>
          </div>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-workspaces-comment-view': CommentViewElement;
  }
}
