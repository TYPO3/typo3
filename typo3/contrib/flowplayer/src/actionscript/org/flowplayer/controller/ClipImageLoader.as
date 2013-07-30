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
	import org.flowplayer.model.Clip;
    import org.flowplayer.view.ErrorHandler;

    /**
	 * @author api
	 */
	internal class ClipImageLoader implements ResourceLoader {

		private var _clip:Clip;
		private var _loader:ResourceLoader;

		public function ClipImageLoader(loader:ResourceLoader, clip:Clip) {
			_loader = loader;
			_clip = clip;
		}
		
		public function addTextResourceUrl(url:String):void {
			_loader.addTextResourceUrl(url);
		}
		
		public function addBinaryResourceUrl(url:String):void {
			_loader.addBinaryResourceUrl(url);
		}
		
		public function load(url:String = null, completeListener:Function = null, ignored:Boolean = false):void {
			_loader.load(url, completeListener, false);
		}

		public function set completeListener(listener:Function):void {
			_loader.completeListener = listener;
		}
		
		public function loadClip(clip:Clip, onLoadComplete:Function):void {
			_clip = clip;
			var imageLoader:ClipImageLoader = this;
			load(clip.completeUrl, function(loader:ResourceLoader):void { onLoadComplete(imageLoader); });
		}
		
		public function getContent(url:String = null):Object {
			return _loader.getContent(_clip.completeUrl);
		}
		
		public function clear():void {
			_loader.clear();
		}

        public function get loadComplete():Boolean {
            return _loader.loadComplete;
        }

        public function set errorHandler(errorHandler:ErrorHandler):void {
            _loader.errorHandler = errorHandler;
        }
    }
}
