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
    import flash.display.Stage;
    import flash.external.ExternalInterface;
    import flash.utils.*;

    import org.flowplayer.config.Config;
    import org.flowplayer.config.ExternalInterfaceHelper;
    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.flow_internal;
    import org.flowplayer.model.Callable;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.model.DisplayProperties;
    import org.flowplayer.model.PlayerError;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.model.PluginEvent;
    import org.flowplayer.model.PluginEventType;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.util.NumberUtil;
    import org.flowplayer.util.ObjectConverter;
    import org.flowplayer.util.PropertyBinder;

    use namespace flow_internal;

    /**
     * @author api
     */
    public class Flowplayer extends FlowplayerBase {

        private var _keyHandler:KeyboardHandler;
        private var _canvas:StyleableSprite;

        public function Flowplayer(
                stage:Stage,
                pluginRegistry:PluginRegistry,
                panel:Panel,
                animationEngine:AnimationEngine,
                canvas:StyleableSprite,
                errorHandler:ErrorHandler,
                config:Config,
                playerSWFBaseURl:String) {

            super(stage, pluginRegistry, panel, animationEngine, errorHandler, config, playerSWFBaseURl);
            _canvas = canvas;
        }

        public function initExternalInterface():void {
            if (!ExternalInterface.available)
                log.info("ExternalInteface is not available in this runtime. JavaScript access will be disabled.");
            try {
                addCallback("getVersion", function():Array {
                    return version;
                });
                addCallback("getPlaylist", function():Array {
                    return convert(playlist.clips) as Array;
                });

                addCallback("getId", function():String {
                    return id;
                });
                addCallback("play", genericPlay);
                addCallback("playFeed", playFeed);
                addCallback("startBuffering", function():void {
                    startBuffering();
                });
                addCallback("stopBuffering", function():void {
                    stopBuffering();
                });
                addCallback("isFullscreen", isFullscreen);
                addCallback("toggleFullscreen", toggleFullscreen);

                addCallback("toggle", toggle);
                addCallback("getState", function():Number {
                    return state.code;
                });
                addCallback("getStatus", function():Object {
                    return convert(status);
                });
                addCallback("isPlaying", isPlaying);
                addCallback("isPaused", isPaused);

                var wrapper:WrapperForIE = new WrapperForIE(this);
                addCallback("stop", wrapper.fp_stop);
                addCallback("pause", wrapper.fp_pause);
                addCallback("resume", wrapper.fp_resume);
                addCallback("close", wrapper.fp_close);

                addCallback("getTime", function():Number {
                    return status.time;
                });
                addCallback("mute", function():void {
                    muted = true;
                });
                addCallback("unmute", function():void {
                    muted = false;
                });
                addCallback("isMuted", function():Boolean {
                    return muted;
                });
                addCallback("setVolume", function(value:Number):void {
                    volume = value;
                });
                addCallback("getVolume", function():Number {
                    return volume;
                });
                addCallback("seek", genericSeek);
                addCallback("getCurrentClip", function():Object {
                    return new ObjectConverter(currentClip).convert();
                });
                addCallback("getClip", function(index:Number):Object {
                    return convert(playlist.getClip(index));
                });
                addCallback("setPlaylist", function(playlist:Object):void {
                    if (playlist is String) loadPlaylistFeed(playlist as String, _playListController.setPlaylist) else setPlaylist(_config.createClips(playlist));
                });
                addCallback("addClip", function(clip:Object, index:int = -1):void {
                    addClip(_config.createClip(clip), index);
                });
                addCallback("showError", showError);

                addCallback("loadPlugin", pluginLoad);
                addCallback("showPlugin", showPlugin);
                addCallback("hidePlugin", hidePlugin);
                addCallback("togglePlugin", togglePlugin);
                addCallback("animate", animate);
                addCallback("css", css);
                //				return;
                addCallback("reset", reset);
                addCallback("fadeIn", fadeIn);
                addCallback("fadeOut", fadeOut);
                addCallback("fadeTo", fadeTo);
                addCallback("getPlugin", function(pluginName:String):Object {
                    return new ObjectConverter(_pluginRegistry.getPlugin(pluginName)).convert();
                });
                addCallback("getRawPlugin", function(pluginName:String):Object {
                    return _pluginRegistry.getPlugin(pluginName);
                });
                addCallback("invoke", invoke);
                addCallback("addCuepoints", addCuepoints);
                addCallback("updateClip", updateClip);
                addCallback("logging", logging);

                addCallback("setKeyboardShortcutsEnabled", setKeyboardShortcutsEnabled);
                addCallback("isKeyboardShortcutsEnabled", isKeyboardShortcutsEnabled);
                addCallback("validateKey", validateKey);

                addCallback("bufferAnimate", bufferAnimate);

            } catch (e:Error) {
                handleError(PlayerError.INIT_FAILED, "Unable to add callback to ExternalInterface");
            }
        }

//        private function killTheLastClip(evt:*=null):void {
//            var array:Array = new Array();
//            var pl:Playlist = playlist;
//
//            for (var x:int = 0; x < pl.length-1; x++) {
//                var clip:Clip = pl.getClip(x);
//                array.push(clip);
//            }
//            pl.getClip(pl.length-1).setCustomProperty('rel',false);
//            pl.replaceClips2(array);
//        }

        private function loadPlaylistFeed(feedName:String, clipHandler:Function):void {
            var feedLoader:ResourceLoader = createLoader();
            feedLoader.addTextResourceUrl(feedName);
            feedLoader.load(null,
                    function(loader:ResourceLoader):void {
                        log.info("received playlist feed");
                        clipHandler(_config.createClips(loader.getContent()));
                    });
        }

        private function pluginLoad(name:String, url:String, properties:Object = null, callbackId:String = null):void {
            loadPluginWithConfig(name, url, properties, callbackId != null ? createCallback(callbackId) : null);
        }

        private static function addCallback(methodName:String, func:Function):void {
            ExternalInterfaceHelper.addCallback("fp_" + methodName, func);
        }

        private function genericPlay(param:Object = null, instream:Boolean = false):void {
            if (param == null) {
                play();
                return;
            }
            if (param is Number) {
                _playListController.play(null, param as Number);
                return;
            }
            if (param is Array) {
                _playListController.playClips(_config.createClips(param as Array));
                return;
            }
            var clip:Clip = _config.createClip(param);
            if (! clip) {
                showError("cannot convert " + param + " to a clip");
                return;
            }
            if (instream) {
                playInstream(clip);
                return;
            }
            play(clip);
        }

        private function playFeed(feed:String):void {
            loadPlaylistFeed(feed, _playListController.playClips);
        }

        private function genericSeek(target:Object):void {
            var percentage:Number = target is String ? NumberUtil.decodePercentage(target as String) : NaN;
            if (isNaN(percentage)) {
                seek(target is String ? parseInt(target as String) : target as Number);
            } else {
                seekRelative(percentage);
            }
        }

        public function css(pluginName:String, props:Object = null):Object {
            log.debug("css, plugin " + pluginName);
            if (pluginName == "canvas") {
                _canvas.css(props);
                return props;
            }
            return style(pluginName, props, false, 0);
        }

        private function convert(objToConvert:Object):Object {
            return new ObjectConverter(objToConvert).convert();
        }

        private function collectDisplayProps(props:Object, animatable:Boolean):Object {
            var result:Object = new Object();
            var coreDisplayProps:Array = [ "width", "height", "left", "top", "bottom", "right", "opacity" ];
            if (!animatable) {
                coreDisplayProps = coreDisplayProps.concat("display", "zIndex");
            }
            for (var propName:String in props) {
                if (coreDisplayProps.indexOf(propName) >= 0) {
                    result[propName] = props[propName];
                    //					delete props[propName];
                }
            }
            return result;
        }

        private function animate(pluginName:String, props:Object, durationMillis:Number = 400, listenerId:String = null):Object {
            return style(pluginName, props, true, durationMillis, listenerId);
        }

        private function style(pluginName:String, props:Object, animate:Boolean, durationMillis:Number = 400, listenerId:String = null):Object {
            var plugin:Object = _pluginRegistry.getPlugin(pluginName);
            checkPlugin(plugin, pluginName, DisplayPluginModel);
            log.debug("going to animate plugin " + pluginName);

            if (plugin is DisplayProperties && DisplayProperties(plugin).getDisplayObject() is Styleable)
                Styleable(DisplayProperties(plugin).getDisplayObject())[animate ? "onBeforeAnimate" : "onBeforeCss"](props);

            var result:Object;
            if (props) {
                if (pluginName == 'play') {
                    result = convert(_animationEngine.animateNonPanel(DisplayProperties(_pluginRegistry.getPlugin("screen")).getDisplayObject(), DisplayProperties(plugin).getDisplayObject(), collectDisplayProps(props, animate), durationMillis, createCallback(listenerId, plugin)));
                } else {
                    result = convert(_animationEngine.animate(DisplayProperties(plugin).getDisplayObject(), collectDisplayProps(props, animate), durationMillis, createCallback(listenerId, plugin)));
                }
            } else {
                result = convert(plugin);
            }

            // check if plugin is Styleable and delegate to it
            if (plugin is DisplayProperties && DisplayProperties(plugin).getDisplayObject() is Styleable) {
                var newPluginProps:Object = Styleable(DisplayProperties(plugin).getDisplayObject())[animate ? "animate" : "css"](props);
                for (var prop:String in newPluginProps) {
                    result[prop] = newPluginProps[prop];
                }
            }
            return result;
        }

        private function fadeOut(pluginName:String, durationMillis:Number = 400, listenerId:String = null):void {
            var props:DisplayProperties = prepareFade(pluginName, false);
            _animationEngine.fadeOut(props.getDisplayObject(), durationMillis, createCallback(listenerId, props));
        }

        private function fadeIn(pluginName:String, durationMillis:Number = 400, listenerId:String = null):void {
            var props:DisplayProperties = prepareFade(pluginName, true);
            if (pluginName == "play") {
                Screen(screen.getDisplayObject()).showPlay();
            }
            _animationEngine.fadeIn(props.getDisplayObject(), durationMillis, createCallback(listenerId, props), pluginName != "play");
        }

        private function fadeTo(pluginName:String, alpha:Number, durationMillis:Number = 400, listenerId:String = null):void {
            var props:DisplayProperties = prepareFade(pluginName, true);
            if (pluginName == "play") {
                Screen(screen.getDisplayObject()).showPlay();
            }
            _animationEngine.fadeTo(props.getDisplayObject(), alpha, durationMillis, createCallback(listenerId, props), pluginName != "play");
        }

        private function prepareFade(pluginName:String, show:Boolean):DisplayProperties {
            var plugin:Object = _pluginRegistry.getPlugin(pluginName);
            checkPlugin(plugin, pluginName, DisplayProperties);
            if (show) {
                var props:DisplayProperties = plugin as DisplayProperties;
                if (! props.getDisplayObject().parent || props.getDisplayObject().parent != _panel) {
                    props.alpha = 0;
                }
                doShowPlugin(props.getDisplayObject(), props);
            }
            return plugin as DisplayProperties;
        }

        private function invoke(pluginName:String, methodName:String, args:Object = null):Object {
            var plugin:Callable = _pluginRegistry.getPlugin(pluginName) as Callable;
            checkPlugin(plugin, pluginName, Callable);
            try {
                //				log.debug("invoke() on " + plugin + "." + methodName);
                if (plugin.getMethod(methodName).hasReturnValue) {
                    log.debug("method has a return value");
                    return plugin.invokeMethod(methodName, args is Array ? args as Array : [ args ]);
                } else {
                    log.debug("method does not have a return value");
                    plugin.invokeMethod(methodName, args is Array ? args as Array : [ args ]);
                }
            } catch (e:Error) {
                throw e;
                //				handleError(PlayerError.PLUGIN_INVOKE_FAILED, "Error when invoking method '" + methodName + "', on plugin '" + pluginName + "'");
            }
            return "undefined";
        }

        private function addCuepoints(cuepoints:Array, clipIndex:int, callbackId:String):void {
            var clip:Clip = _playListController.playlist.getClip(clipIndex);
            var points:Array = _config.createCuepoints(cuepoints, callbackId, 1);
            if (! points || points.length == 0) {
                showError("unable to create cuepoints from " + cuepoints);
            }
            clip.addCuepoints(points);
            log.debug("clip has now cuepoints " + clip.cuepoints);
        }

        private function updateClip(clipObj:Object, clipIndex:int):void {
            log.debug("updateClip()", clipObj);
            var clip:Clip = _playListController.playlist.getClip(clipIndex);
            new PropertyBinder(clip, "customProperties").copyProperties(clipObj);
            clip.dispatch(ClipEventType.UPDATE);
        }

        private function createCallback(listenerId:String, pluginArg:Object = null):Function {
            if (! listenerId) return null;
            return function(plugin:PluginModel = null):void {
                if (plugin || pluginArg is PluginModel) {
                    PluginModel(pluginArg || plugin).dispatch(PluginEventType.PLUGIN_EVENT, listenerId);
                } else {
                    new PluginEvent(PluginEventType.PLUGIN_EVENT, pluginArg is DisplayProperties ? DisplayProperties(pluginArg).name : pluginArg.toString(), listenerId).fireExternal(_playerId);
                }
            };
        }

        private function validateKey(key:Object, pageDomain:Boolean):Boolean {
            var LicenseKey:Class = Class(getDefinitionByName("org.flowplayer.view.LicenseKey"));
            return LicenseKey["validate"](_canvas.loaderInfo.url, version, key, pageDomain);
        }

    }
}
