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
	import flash.display.DisplayObject;
	
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipType;
	import org.flowplayer.model.Playlist;	
	
	
	
			
	
	
	
	
		
	use namespace flow_internal;

	/**
	 * @author api
	 */
	internal class MediaDisplayFactory {

		private var playList:Playlist;

		public function MediaDisplayFactory(playList:Playlist) {
			this.playList = playList;
		}

		public function createMediaDisplay(clip:Clip):DisplayObject {
			var display:DisplayObject;
			if (clip.type == ClipType.VIDEO)
				display = new VideoDisplay(clip);
				//if we have a video api clip type display the video api display which collects the loader of the swf as a displayobject
			if (clip.type == ClipType.API)
				display = new VideoApiDisplay(clip);
			if (clip.type == ClipType.IMAGE || clip.type == ClipType.AUDIO)
				display = new ImageDisplay(clip);
			return display;
		}
	}
}
