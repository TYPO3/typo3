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

package org.flowplayer.model {
    import org.flowplayer.model.Callable;
	import org.flowplayer.model.Cloneable;	

	/**
	 * @author api
	 */
	public interface PluginModel extends Identifiable, Callable, Cloneable {

        function get url():String;

        function set url(url:String):void;

        function get isBuiltIn():Boolean;

        function set isBuiltIn(value:Boolean):void;

		function dispatchOnLoad():void;
		
		function dispatchError(code:PluginError, info:Object = null):void;
			
		function dispatch(eventType:PluginEventType, eventId:Object = null, info:Object = null, info2:Object = null, info3:Object = null):void;
		
		function dispatchEvent(event:PluginEvent):void;

        function dispatchBeforeEvent(eventType:PluginEventType, eventId:Object = null, info:Object = null, info2:Object = null, info3:Object = null):Boolean;

        function onPluginEvent(listener:Function):void;

        function onBeforePluginEvent(listener:Function):void;

		function onLoad(listener:Function):void;

		function onError(listener:Function):void;
		
		function unbind(listener:Function, event:EventType = null, beforePhase:Boolean = false):void;

		function get config():Object;
		
		function set config(config:Object):void;
		
		function get pluginObject():Object;
		
		function set pluginObject(pluginObject:Object):void;
	}
}
