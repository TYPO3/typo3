/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.view {
	import org.flowplayer.util.Arrange;	
	import org.flowplayer.model.Clip;
	import org.flowplayer.view.MediaDisplay;
	
	import flash.display.Sprite;
	import flash.media.Video;	

	/**
	 * @author api
	 */
	internal class VideoDisplay extends AbstractSprite implements MediaDisplay {

		private var video:Video;
		private var _overlay:Sprite;
		private var _clip:Clip;

		public function VideoDisplay(clip:Clip) {
			_clip = clip;
			createOverlay();
		}
		
		private function createOverlay():void {
			// we need to have an invisible layer on top of the video, otherwise the ContextMenu does not work??
			_overlay = new Sprite();
			addChild(_overlay);
			_overlay.graphics.beginFill(0, 0);
			_overlay.graphics.drawRect(0, 0, 10, 10);
			_overlay.graphics.endFill();
		}
		
		public function get overlay():Sprite {
            return _overlay;
        }

		override protected function onResize():void {
			Arrange.sameSize(_overlay, this);
		}

		override public function set alpha(value:Number):void {
			super.alpha = value;
			if (video) {
				video.alpha = value;
				log.debug("display of + " + _clip + " new alpha " + video.alpha);
			} else {
				log.debug("set alpha() no video available");
			}
		}

		public function init(clip:Clip):void {
			_clip = clip;
			log.info("init " + _clip);
			if (video)
				removeChild(video);
			video = clip.getContent() as Video;
            log.debug("init() video == " + video);
			if (video == null) {
				log.warn("no video content in clip " + clip);
				return;
			}
			video.width = this.width;
			video.height = this.height;
			addChild(video);
			swapChildren(_overlay, video);
		}
		
		public function hasContent():Boolean {
			return video != null;
		}
		
		override public function toString():String {
			return "[VideoDisplay] for clip " + _clip;
		}
    }
}
