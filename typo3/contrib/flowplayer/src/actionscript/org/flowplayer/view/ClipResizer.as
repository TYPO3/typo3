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
	import flash.utils.Dictionary;
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipType;
	import org.flowplayer.model.MediaSize;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.ClipEventSupport;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.util.Log;	

	/**
	 * @author api
	 */
	internal class ClipResizer {

		private var log:Log = new Log(this);
		private var resizers:Dictionary;
		private var screen:Screen;
        private var _playlist:Playlist;
		private var _resizerTimer:Timer;

        public function ClipResizer(playList:Playlist, screen:Screen) {
            resizers = new Dictionary();
            _playlist = playList;
            this.screen = screen;
            createResizers(playList.clips.concat(playList.childClips));
            addListeners(playList);
        }


        private function createResizers(clips:Array):void {
			clips.forEach(function(clip:Clip, index:int, clips:Array):void {
				log.debug("creating resizer for clip " + clip);
				resizers[clip] = new MediaResizer(clip, screen.width, screen.height);
			});
		}

		public function setMaxSize(width:int, height:int):void {
            log.debug("setMaxSize: " + width + " x " + height);
			for each (var resizer:MediaResizer in resizers) {
				resizer.setMaxSize(width, height);
			}
            resizeClip(_playlist.current);
		}
		
		public function resizeClip(clip:Clip, force:Boolean = false):void {
			resizeClipTo(clip, clip.scaling, force);
		}
		
		public function resizeClipTo(clip:Clip, mediaSize:MediaSize, force:Boolean = false):void {
			log.debug("resizeClipTo, clip " + clip);
			if ( _resizerTimer ) {
				log.debug("Killing old resize timer");
				_resizerTimer.reset();
				_resizerTimer = null;
			}
			
			var resizer:MediaResizer = resizers[clip];
			if (! resizer) {
				log.warn("no resizer defined for " + clip);
				return;
			}
			
			var resizingFunc:Function = function(event:TimerEvent = null):void {

				if ( event && ! resizer.hasOrigSize() && Timer(event.target).currentCount < Timer(event.target).repeatCount )	{
					log.debug("we don't have a size yet.. waiting for the video object to have a size");
					return;
				}

				if (resizer.resizeTo(mediaSize, force)) {
					screen.resized(clip);
				}
			};
			
			if ( resizer.hasOrigSize() ) {
				log.debug("we have a size, resizing now !");
				resizingFunc();
			} else {
				// delayed one
				log.warn("we don't have a size now, delaying the resize");
				_resizerTimer = new Timer(500, 5);
				_resizerTimer.addEventListener(TimerEvent.TIMER, resizingFunc);
				_resizerTimer.start();
				
			}
		}

		private function error(errorMsg:String):void {
			log.error(errorMsg);
			throw new Error(errorMsg);
		}
		
		private function onResize(event:ClipEvent = null):void {
			log.debug("received event " + event.target);
            var clip:Clip = Clip(event.target);
			if (clip.type == ClipType.IMAGE && clip.getContent() == null) {
				log.warn("image content not available yet, will not resize: " + clip);
				return;
			}
			resizeClip(clip);
		}
		
		private function addListeners(eventSupport:ClipEventSupport):void {
			eventSupport.onStart(onResize);
			eventSupport.onBufferFull(onResize);
            eventSupport.onPlaylistReplace(onPlaylistChange);
            eventSupport.onClipAdd(onPlaylistChange);
		}
		
		private function onPlaylistChange(event:ClipEvent):void {
			log.info("Received onPlaylistChanged");
			createResizers(ClipEventSupport(event.target).clips.concat(ClipEventSupport(event.target).childClips));
		}
		
	}
}
