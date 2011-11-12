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

package org.flowplayer.layout {
	import org.flowplayer.view.AbstractSprite;	
	import org.flowplayer.util.Log;	
	
	import flash.display.DisplayObject;
	import flash.geom.Rectangle;		

	/**
	 * @author api
	 */
	public class DrawWrapper {

		private var view:DisplayObject;
		private var log:Log = new Log(this);

		public function DrawWrapper(view:DisplayObject) {
			this.view = view;
		}

		public function draw(event:LayoutEvent):void {
			var bounds:Rectangle = event.layout.getBounds(view);
			if (bounds == null) {
				log.warn("Did not get bounds for view " + view);
				return;
			}
			log.debug("got bounds " + bounds + " for view " + view);
			view.x = bounds.x;
			view.y = bounds.y;
			if (view is AbstractSprite) {
				AbstractSprite(view).setSize(bounds.width, bounds.height);
			} else {
				view.width = bounds.width;
				view.height = bounds.height;
			}
		}
	}
}
