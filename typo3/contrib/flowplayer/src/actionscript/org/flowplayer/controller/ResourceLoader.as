/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {

	/**
	 * Loader is to load different kinds of resources of the net. The URLs will
	 * be resolved relative to the embedding HTML page or to the player SWF.
	 * The urls are resolved relative to the player SWF when the player is
	 * in "embedded mode" (embedded outside of the hosting site).
	 * 
	 * @author api
	 */
	public interface ResourceLoader {

		function addTextResourceUrl(url:String):void;
		
		function addBinaryResourceUrl(url:String):void;
		
		/**
		 * Clears the urls previously added.
		 */
		function clear():void;
		
		function set completeListener(listener:Function):void;
		
		/**
		 * Loads the specified url or from urls previously added.
		 */
		function load(url:String = null, completeListener:Function = null, isTextResource:Boolean = false):void;
		
		
		function getContent(url:String = null):Object;

        function get loadComplete():Boolean;

	}
}
