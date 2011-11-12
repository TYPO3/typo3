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

package org.flowplayer.controller {
	import org.flowplayer.model.Playlist;	
	import flash.display.Loader;
	import flash.display.DisplayObject;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	
	import org.flowplayer.controller.AbstractDurationTrackingController;
	import org.flowplayer.controller.MediaController;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.ClipEventType;
	import org.flowplayer.util.Log;	
	
	import org.flowplayer.view.ImageHolder;

	/**
	 * @author api
	 */
	internal class ImageController extends AbstractDurationTrackingController implements MediaController {

		private var _loader:ClipImageLoader;
//		private var _durationlessClipPaused:Boolean;

		public function ImageController(loader:ResourceLoader, volumeController:VolumeController, playlist:Playlist) {
			super(volumeController, playlist);
			_loader = new ClipImageLoader(loader, null);
		}

		override protected function get allowRandomSeek():Boolean {
			return true;
		}
		
		override protected function doLoad(event:ClipEvent, clip:Clip, pauseAfterStart:Boolean = false):void {
//			_durationlessClipPaused = false;

			// reset the duration tracker, #45
			if (durationTracker) {
				durationTracker.stop();
				durationTracker.time = 0;
			}

			log.info("Starting to load " + clip);
			_loader.loadClip(clip, onLoadComplete);
			dispatchPlayEvent(event);
		}
		
		override protected function doPause(event:ClipEvent):void {
			dispatchPlayEvent(event);
		}
		
		override protected function doResume(event:ClipEvent):void {
			dispatchPlayEvent(event);
		}
		
		override protected function doStop(event:ClipEvent, closeStream:Boolean):void {
			dispatchPlayEvent(event);
		}
		
		override protected function doSeekTo(event:ClipEvent, seconds:Number):void {
			if (event) {
				dispatchPlayEvent(new ClipEvent(ClipEventType.SEEK, seconds));
			}
		}
		
		private function onLoadComplete(loader:ClipImageLoader):void {			
			if ( loader.getContent() is Loader && ImageHolder.hasOffscreenContent(loader.getContent() as Loader ))
			{
				var holder:ImageHolder = new ImageHolder(loader.getContent() as Loader);
				clip.originalHeight = holder.originalHeight;
				clip.originalWidth  = holder.originalWidth;
				clip.setContent(holder);
			}
			else	// no need to wrap it
			{
				clip.setContent(loader.getContent() as DisplayObject);
				clip.originalHeight = loader.getContent().height;
				clip.originalWidth = loader.getContent().width;
			}
			log.info("image loaded " + clip + ", content " + loader.getContent() + ", width " + clip.originalWidth + ", height " + clip.originalHeight + ", duration "+ clip.duration);
            clip.dispatch(ClipEventType.START);
			clip.dispatch(ClipEventType.METADATA);
			clip.dispatch(ClipEventType.BUFFER_FULL);
			
			if (clip.duration == 0) {
				
				clip.onResume(function(event:ClipEvent):void {
					clip.dispatchBeforeEvent(new ClipEvent(ClipEventType.FINISH));
				});
				
				clip.dispatchEvent(new ClipEvent(ClipEventType.RESUME));
			}
		}
	}
}
