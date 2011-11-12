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

package org.flowplayer.config {
	import flash.external.ExternalInterface;
	import flash.utils.describeType;
	import flash.utils.getDefinitionByName;
	import flash.utils.getQualifiedClassName;
	
	import org.flowplayer.model.Callable;
	import org.flowplayer.model.PluginMethod;
	import org.flowplayer.util.Log;	

	/**
	 * @author api
	 */
	public class ExternalInterfaceHelper {

		private static var log:Log = new Log("org.flowplayer.config::ExternalInterfaceHelper");

		public static function initializeInterface(callable:Callable, plugin:Object):void {
			if (!ExternalInterface.available) return;
			var xml:XML = describeType(plugin);
			
			var exposed:XMLList = xml.*.(hasOwnProperty("metadata") && metadata.@name=="External");
			log.info("Number of exposed methods and accessors: " + exposed.length());
			for each (var exposedNode:XML in exposed) {
				log.debug("processing exposed method or accessor " + exposedNode);
				addMethods(callable, exposedNode, plugin);
			}
		}
		
		private static function addMethods(callable:Callable, exposedNode:XML, plugin:Object):void {
			var methodName:String = exposedNode.@name;
            var convert:Boolean = exposedNode.metadata.arg.@key == "convert" ? exposedNode.metadata.arg.@value == "true" : false;
            
			log.debug("------------" + methodName + ", has return value " + (exposedNode.@returnType != "void") +", convertResult " + convert);
			if (exposedNode.name() == "method") { 
				callable.addMethod(PluginMethod.method(methodName, methodName, (exposedNode.@returnType != "void"), convert));
				
			} else if (exposedNode.name() == "accessor") {
				var methodNameUppercased:String = methodName.charAt(0).toUpperCase() + methodName.substring(1);
				if (exposedNode.@access == "readwrite") {
					callable.addMethod(PluginMethod.getter("get" + methodNameUppercased, methodName, convert));
					callable.addMethod(PluginMethod.setter("set" + methodNameUppercased, methodName));
					
				} else if (exposedNode.@access == "readonly") {
					callable.addMethod(PluginMethod.getter("get" + methodNameUppercased, methodName, convert));
					
				} else {
					callable.addMethod(PluginMethod.setter("set" + methodNameUppercased, methodName));
				}
			}			
		}

		public static function addCallback(methodName:String, func:Function):void {
			try {
				ExternalInterface.addCallback(methodName, func);
			} catch (error:Error) {
				log.error("Unable to register callback for " + error);
			}
		}
	}
}
