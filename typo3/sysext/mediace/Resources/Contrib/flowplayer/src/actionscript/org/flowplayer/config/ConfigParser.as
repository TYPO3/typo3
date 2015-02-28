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

    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.flow_internal;
	import org.flowplayer.util.Log;
	import com.adobe.serialization.json.JSON;

    use namespace flow_internal;

	/**
	 * @author anssi
	 */
	public class ConfigParser {
		private static var log:Log = new Log(ConfigParser);

        flow_internal static function parse(config:String):Object {
            //#590 add full package reference to work with Flex 4.6
            return com.adobe.serialization.json.JSON.decode(config);
        }

        flow_internal static function parseConfig(config:Object, builtInConfig:Object, playerSwfUrl:String, controlsVersion:String, audioVersion:String):Config {
            if (!config) return new Config({}, builtInConfig, playerSwfUrl, controlsVersion, audioVersion);
            var configObj:Object = config is String ? com.adobe.serialization.json.JSON.decode(config as String) : config;
            return new Config(configObj, builtInConfig, playerSwfUrl, controlsVersion, audioVersion);
        }

        flow_internal static function loadConfig(fileName:String, builtInConfig:Object, listener:Function, loader:ResourceLoader, playerSwfName:String, controlsVersion:String, audioVersion:String):void {
            loader.load(fileName, function(loader:ResourceLoader):void {
                //trace(loader.getContent());
                listener(parseConfig(loader.getContent(), builtInConfig, playerSwfName, controlsVersion, audioVersion))
            }, true);
        }
	}
}
