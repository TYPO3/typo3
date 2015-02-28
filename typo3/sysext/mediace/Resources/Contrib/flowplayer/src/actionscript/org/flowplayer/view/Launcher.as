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
    import org.flowplayer.config.Config;
	import org.flowplayer.config.ConfigParser;
	import org.flowplayer.config.ExternalInterfaceHelper;
	import org.flowplayer.config.VersionInfo;
	import org.flowplayer.controller.PlayListController;
	import org.flowplayer.controller.ResourceLoader;
	import org.flowplayer.controller.ResourceLoaderImpl;
    import org.flowplayer.flow_internal;
	import org.flowplayer.model.Callable;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.model.DisplayPluginModel;
	import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.DisplayPropertiesImpl;
    import org.flowplayer.model.ErrorCode;
    import org.flowplayer.model.EventDispatcher;
	import org.flowplayer.model.Loadable;
	import org.flowplayer.model.Logo;
	import org.flowplayer.model.PlayButtonOverlay;
	import org.flowplayer.model.PlayerError;
	import org.flowplayer.model.PlayerEvent;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.Plugin;
	import org.flowplayer.model.PluginError;
	import org.flowplayer.model.PluginEvent;
	import org.flowplayer.model.PluginModel;
	import org.flowplayer.model.ProviderModel;
	import org.flowplayer.model.State;
	import org.flowplayer.util.Arrange;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.TextUtil;
	import org.flowplayer.util.URLUtil;
	import org.flowplayer.view.Panel;
	import org.flowplayer.view.PluginLoader;
    import org.flowplayer.view.Screen;
	import org.flowplayer.view.KeyboardHandler;
	import org.osflash.thunderbolt.Logger;

	import flash.display.DisplayObject;
	import flash.display.DisplayObjectContainer;
	import flash.display.Sprite;
	import flash.display.BlendMode;

	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.events.TimerEvent;
	import flash.net.URLRequest;
	import flash.net.navigateToURL;
	import flash.system.Capabilities;
	import flash.system.Security;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;

	import flash.utils.*;

    CONFIG::FLASH_10_1 {
    import flash.media.StageVideo;
    }
	use namespace flow_internal;

	public class Launcher extends StyleableSprite implements ErrorHandler {
		private var _panel:Panel;
		private var _screen:Screen;
		private var _config:Config;
		private var _flowplayer:Flowplayer;
		private var _pluginRegistry:PluginRegistry;
		private var _animationEngine:AnimationEngine;
		private var _playButtonOverlay:PlayButtonOverlay;
		private var _controlsModel:DisplayPluginModel;
        private var _providers:Dictionary = new Dictionary();
		private var _canvasLogo:Sprite;
		private var _pluginLoader:PluginLoader;
		private var _error:TextField;
		private var _pluginsInitialized:Number = 0;
		private var _enteringFullscreen:Boolean;
		private var _copyrightNotice:TextField;
        private var _playlistLoader:ResourceLoader;
        private var _fullscreenManager:FullscreenManager;
        private var _screenArrangeCount:int = 0;
        private var _clickCount:int;
        private var _clickTimer:Timer = new Timer(200, 1);
        private var _clickEvent:MouseEvent;
		private var _screenMask:Sprite;

		[Frame(factoryClass="org.flowplayer.view.Preloader")]
		public function Launcher() {
			addEventListener(Event.ADDED_TO_STAGE, function(e:Event):void {
                URLUtil.loaderInfo = loaderInfo;
                trace("Launcher added to stage");
                callAndHandleError(createFlashVarsConfig, PlayerError.INIT_FAILED);
            });
            super("#canvas", this);
        }

        private function initPhase1():void {

            if (_flowplayer) {
                log.debug("already initialized, returning");
                return;
            }

			Log.configure(_config.getLogConfiguration());
            trace("created log configuration, tracing enabled? " + Log.traceEnabled)

            initCustomClipEvents();

			if (_config.playerId) {
				Security.allowDomain(URLUtil.pageLocation);
			}

			loader = createNewLoader();

			rootStyle = _config.canvas.style;
            stage.addEventListener(Event.RESIZE, onStageResize);
            stage.addEventListener(Event.RESIZE, arrangeScreen);

			setSize(Arrange.parentWidth, Arrange.parentHeight);

			if (! VersionInfo.commercial) {
				log.debug("Adding logo to canvas");
				createLogoForCanvas();
			}

			log = new Log(this);
			EventDispatcher.playerId = _config.playerId;

			log.debug("security sandbox type: " + Security.sandboxType);

			log.info(VersionInfo.versionInfo());
            trace(VersionInfo.versionInfo());
			log.debug("creating Panel");

			createPanel();
			_pluginRegistry = new PluginRegistry(_panel);

			log.debug("Creating animation engine");
			createAnimationEngine(_pluginRegistry);

			log.debug("creating play button overlay");
			createPlayButtonOverlay();

            log.debug("creating Flowplayer API");
            createFlowplayer();

			// keyboard handler must be present for plugins.
			//

            loadPlaylistFeed();
		}

        private function initPhase2(event:Event = null):void {
            log.info("initPhase2");
			_flowplayer.keyboardHandler = new KeyboardHandler(stage, function():Boolean { return enteringFullscreen });
            loadPlugins();
        }

		private function initPhase3(event:Event = null):void {
            log.debug("initPhase3, all plugins loaded");
            createScreen();

            _config.getPlaylist().onBeforeBegin(function(event:ClipEvent):void { hideErrorMessage(); });
            if (_playButtonOverlay) {
                PlayButtonOverlayView(_playButtonOverlay.getDisplayObject()).playlist = _config.getPlaylist();
            }

			log.debug("creating PlayListController");
			_providers = _pluginLoader.providers;
			var playListController:PlayListController = createPlayListController();

			addPlayListListeners();
			createFullscreenManager(playListController.playlist);

			addScreenToPanel();

			if (!validateLicenseKey()) {
				createLogoForCanvas();
				resizeCanvasLogo();
			}

			log.debug("creating logo");
			createLogo();

			contextMenu = new ContextMenuBuilder(_config.playerId, _config.contextMenu).build();

			log.debug("initializing ExternalInterface");
			if (useExternalInterface()) {
				_flowplayer.initExternalInterface();
			}

			log.debug("calling onLoad to plugins");
			_pluginRegistry.onLoad(_flowplayer);

            if (countPlugins() == 0) {
                log.debug("no loadable plugins, calling initPhase4");
                initPhase4();
            }
		}

		private function initPhase4(event:Event = null):void {
            log.info("initPhase4, all plugins initialized");

			log.debug("Adding visible plugins to panel");
			addPluginsToPanel(_pluginRegistry);

			log.debug("dispatching onLoad");
			_flowplayer.dispatchEvent(PlayerEvent.load("player"));

			log.debug("starting configured streams");
            startStreams();

            //#627 re-enabling screen mask for stage video.
			createScreenMask();
            arrangeScreen();

            addListeners();

//            _controlsModel.onPluginEvent(function(event:PluginEvent):void {
//                log.debug("received plugin event " + event.id);
//                var model:DisplayPluginModel = event.target as DisplayPluginModel;
//                log.debug("controls y-pos now is " + model.getDisplayObject().y);
//            });
//
//            _controlsModel.onBeforePluginEvent(function(event:PluginEvent):void {
//                log.debug("received before plugin event " + event.id);
//                var model:DisplayPluginModel = event.target as DisplayPluginModel;
//                log.debug("controls y-pos now is " + model.getDisplayObject().y);
//                event.preventDefault();
//            });
//            lookupSlowMotionPlugin(_flowplayer);
		}

        //#508 disabling the stagevideo screen mask, canvas is visible without it.
		private function createScreenMask():void {
			blendMode = BlendMode.LAYER;

			_screenMask = new Sprite();
			_screenMask.graphics.beginFill(0xff0000);
			_screenMask.graphics.drawRect(0, 0, 1, 1);
			_screenMask.blendMode = BlendMode.ERASE;

			_screenMask.x = 0;
			_screenMask.y = 0;
			_screenMask.width = 100;
			_screenMask.height = 100;
		}

		private function resizeCanvasLogo():void {
			_canvasLogo.alpha = 1;
			_canvasLogo.width = 150;
			_canvasLogo.scaleY = _canvasLogo.scaleX;
			arrangeCanvasLogo();
		}

		private function useExternalInterface():Boolean {
			log.debug("useExternalInteface: " + (_config.playerId != null));
			return _config.playerId != null;
		}

		private function onStageResize(event:Event = null):void {
			setSize(Arrange.parentWidth, Arrange.parentHeight);
			arrangeCanvasLogo();
		}

		private function arrangeCanvasLogo():void {
			if (!_canvasLogo) return;
			_canvasLogo.x = 15;
			_canvasLogo.y = Arrange.parentHeight - (_controlsModel ? _controlsModel.dimensions.height.toPx(Arrange.parentHeight) + 10 : 10) - _canvasLogo.height - _copyrightNotice.height;
			_copyrightNotice.x = 12;
			_copyrightNotice.y  = _canvasLogo.y + _canvasLogo.height;
		}

		private function loadPlugins():void {
			var plugins:Array = _config.getLoadables();
			log.debug("will load following plugins: ");
            logPluginInfo(plugins);
			_pluginLoader = new PluginLoader(URLUtil.playerBaseUrl, _pluginRegistry, this, useExternalInterface());
            _pluginLoader.addEventListener(Event.COMPLETE, pluginLoadListener);
            _flowplayer.pluginLoader = _pluginLoader;
            if (plugins.length == 0) {
                log.debug("configuration has no plugins");
                initPhase3();
            } else {
//                _builtInPlugins = _config.createLoadables(BuiltInConfig.config.plugins);
//                log.debug("following built-in plugins will be instantiated");
//                trace("builtIn plugins: ");
//                logPluginInfo(_builtInPlugins, true);
                _pluginLoader.load(plugins, onPluginLoad, onPluginLoadError);
            }
        }

        private function logPluginInfo(plugins:Array, doTrace:Boolean = false):void {
            for (var i:Number = 0; i < plugins.length; i++) {
                log.info("" + plugins[i]);
                if (doTrace) {
                    trace("" + plugins[i]);
                }
            }
        }

        private function pluginLoadListener(event:Event = null):void {
            _pluginLoader.removeEventListener(Event.COMPLETE, pluginLoadListener);
            callAndHandleError(initPhase3, PlayerError.INIT_FAILED);
        }

		private function loadPlaylistFeed():void {
            var playlistFeed:String = _config.playlistFeed;
            if (! playlistFeed) {
                callAndHandleError(initPhase2, PlayerError.INIT_FAILED);
                return;
            }
            log.info("loading playlist from " + playlistFeed);
            _playlistLoader = _flowplayer.createLoader();
            _playlistLoader.addTextResourceUrl(playlistFeed);
            _playlistLoader.load(null,
                    function(loader:ResourceLoader):void {
                        log.info("received playlist feed");
                        _config.playlistDocument = loader.getContent() as String;
                        _config.getPlaylist().dispatchPlaylistReplace();
                        callAndHandleError(initPhase2, PlayerError.INIT_FAILED);
                    });
        }

		private function onPluginLoad(event:PluginEvent):void {
			var plugin:PluginModel = event.target as PluginModel;
			log.info("plugin " + plugin + " initialized");
			checkPluginsInitialized();
		}

		private function onPluginLoadError(event:PluginEvent):void {
            if (event.target is Loadable) {
                handleError(PlayerError.PLUGIN_LOAD_FAILED, "unable to load plugin '" + Loadable(event.target).name + "', url: '" + Loadable(event.target).url + "'");
//                throw new Error("unable to load plugin '" + Loadable(event.target).name + "', url: '" + Loadable(event.target).url + "'");
            } else {
                var plugin:PluginModel = event.target as PluginModel;
                _pluginRegistry.removePlugin(plugin);
                handleError(PlayerError.PLUGIN_LOAD_FAILED, "load/init error on " + plugin);
            }
		}

		private function checkPluginsInitialized():void {
			var numPlugins:int = countPlugins();
			if (++_pluginsInitialized == numPlugins) {
				log.info("all plugins initialized");
				callAndHandleError(initPhase4, PlayerError.INIT_FAILED);
			}
			log.info(_pluginsInitialized + " out of " + numPlugins + " plugins initialized");
		}

		private function countPlugins():int {
			var count:Number = 0;
			var loadables:Array = _config.getLoadables();
			for (var i:Number = 0; i < loadables.length; i++) {

				var plugin:PluginModel = Loadable(loadables[i]).plugin;
                if (! plugin) {
                    handleError(PlayerError.PLUGIN_LOAD_FAILED, "Unable to load plugin, url " + Loadable(loadables[i]).url + ", name " + Loadable(loadables[i]).name);
//                    throw new Error("Plugin " + loadables[i] + " not available");
                }
				else
				{
	                var isNonAdHocPlugin:Boolean = plugin.pluginObject is Plugin;
	//                var isNonAdHocPlugin:Boolean = (plugin is DisplayPluginModel && DisplayPluginModel(plugin).getDisplayObject() is Plugin) ||
	//                    plugin is ProviderModel && ProviderModel(plugin).pluginObject is Plugin;

	                if (Loadable(loadables[i]).loadFailed) {
	                    log.debug("load failed for " + loadables[i]);
	                    count++;
	                } else if (! plugin) {
	                    log.debug("this plugin is not loaded yet");
	                    count++;
	                } else if (isNonAdHocPlugin) {
						log.debug("will wait for onLoad from plugin " + plugin);
						count++;
					} else {
						log.debug("will NOT wait for onLoad from plugin " + Loadable(loadables[i]).plugin);
					}
				}
			}
			// +1 comes from the playbuttonoverlay
			return count + (_playButtonOverlay ? 1 : 0);
		}

		private function validateLicenseKey():Boolean {
			try {
				return LicenseKey.validate(root.loaderInfo.url, _flowplayer.version, _config.licenseKey, useExternalInterface());
			} catch (e:Error) {
				log.warn("License key not accepted, will show flowplayer logo");
			}
			return false;
		}

		private function createFullscreenManager(playlist:Playlist):void {
			_fullscreenManager = new FullscreenManager(stage, playlist, _panel, _pluginRegistry, _animationEngine);
            _flowplayer.fullscreenManager = _fullscreenManager;
		}

		public function showError(message:String):void {
			if (! _panel) return;
			if (! _config.showErrors) return;
			if (_error && _error.parent == this) {
				removeChild(_error);
			}

			_error = TextUtil.createTextField(false);
			_error.background = true;
			_error.backgroundColor = 0;
			_error.textColor = 0xffffff;
			_error.autoSize = TextFieldAutoSize.CENTER;
			_error.multiline = true;
			_error.wordWrap = true;
			_error.text = message;
			_error.selectable = true;
			_error.width = Arrange.parentWidth - 40;
			Arrange.center(_error, Arrange.parentWidth, Arrange.parentHeight);
			addChild(_error);

			createErrorMessageHideTimer();
		}

		private function createErrorMessageHideTimer():void {
			var errorHideTimer:Timer = new Timer(10000, 1);
			errorHideTimer.addEventListener(TimerEvent.TIMER_COMPLETE, hideErrorMessage);
			errorHideTimer.start();
		}

		private function hideErrorMessage(event:TimerEvent = null):void {
			if (_error && _error.parent == this) {
				if (_animationEngine) {
					_animationEngine.fadeOut(_error, 1000, function():void { removeChild(_error); });
				} else {
					removeChild(_error);
				}
			}
		}

		public function handleError(error:ErrorCode, info:Object = null, throwError:Boolean = true):void {
            if (_flowplayer) {
                _flowplayer.dispatchError(error, info);
            } else {
                // initialization is not complete, create a dispatcher just to dispatch this error
                new PlayerEventDispatcher().dispatchError(error, info);
            }
			var stack:String = "";
			if ( CONFIG::debug && info is Error && info.getStackTrace() )
				stack = info.getStackTrace();
			doHandleError(error.code + ": " + error.message + ( info ? ": " + info + (stack ? " - Stack: "+ stack : "") : ""), throwError);
		}

		private function doHandleError(message:String, throwError:Boolean = true):void {
			if (_config && _config.playerId) {
				Logger.error(message);
			}
			showError(message);
			if (throwError && Capabilities.isDebugger && _config.showErrors) {
				throw new Error(message);
			}
		}

		private function createAnimationEngine(pluginRegistry:PluginRegistry):void {
			_animationEngine = new AnimationEngine(_panel, pluginRegistry);
		}

		private function addPluginsToPanel(_pluginRegistry:PluginRegistry):void {
			for each (var pluginObj:Object in _pluginRegistry.plugins) {
				if (pluginObj is DisplayPluginModel) {
					var model:DisplayPluginModel = pluginObj as DisplayPluginModel;
					log.debug("adding plugin '"+ model.name +"' to panel: " + model.visible + ", plugin object is " + model.getDisplayObject());
					if (model.visible) {
						if (model.zIndex == -1) {
							model.zIndex = 100;
						}
						_panel.addView(model.getDisplayObject(), undefined, model);
					}
					if (model.name == "controls") {
						_controlsModel = model;
					}
				}
			}
			if (_controlsModel) {
				arrangeCanvasLogo();
			}
		}

		private function addScreenToPanel():void {
			// if controls visible and screen was not explicitly configured --> place screen on top of controls
			var screen:DisplayProperties = _pluginRegistry.getPlugin("screen") as DisplayProperties;
			screen.display = "none";
			screen.getDisplayObject().visible = false;
			_panel.addView(screen.getDisplayObject(), null, screen);
		}

		private function arrangeScreen(event:Event = null):void {
            log.debug("arrangeScreen(), already arranged " + _screenArrangeCount);
            if (_screenArrangeCount > 1) return;
            if (! _pluginRegistry) return;
            var screen:DisplayProperties = _pluginRegistry.getPlugin("screen") as DisplayProperties;
            if (! screen) return;

			if (_controlsModel && _controlsModel.visible) {
				if (isControlsAlwaysAutoHide() || (_controlsModel.position.bottom.px > 0)) {
					log.debug("controls is autoHide or it's in a non-default vertical position, configuring screen to take all available space");
					setScreenBottomAndHeight(screen, 100, 0);
				} else {
					var controlsHeight:Number = _controlsModel.getDisplayObject().height;
					var occupiedHeight:Number = screenTopOrBottomConfigured() ? getScreenTopOrBottomPx(screen) : controlsHeight;
					log.debug("occupied by controls or screen's configured bottom/top is " + occupiedHeight);

					var heightPct:Number = 0;
					if (screenTopOrBottomConfigured() && (screen.position.top.pct >= 0 || screen.position.bottom.pct >= 0)) {
						heightPct = 100 - Math.abs(50 - (screen.position.top.pct >= 0 ? screen.position.top.pct : screen.position.bottom.pct))*2;
						setScreenBottomAndHeight(screen, heightPct, controlsHeight);
					} else {
						heightPct = ((Arrange.parentHeight - occupiedHeight) / Arrange.parentHeight) * 100;
						setScreenBottomAndHeight(screen, heightPct, controlsHeight);
					}
				}
			}
			log.debug("arrangeScreen(): arranging screen to pos " + screen.position);
			screen.display = "block";
            screen.alpha = 1;
			screen.getDisplayObject().visible = true;
			_pluginRegistry.updateDisplayProperties(screen, true);
			_panel.update(screen.getDisplayObject(), screen);
			_panel.draw(screen.getDisplayObject());
            _screenArrangeCount++;
		}

		private function getScreenTopOrBottomPx(screen:DisplayProperties):Number {
			var screenConf:Object = _config.getObject("screen");
			if (screenConf.hasOwnProperty("top")) return screen.position.top.toPx(Arrange.parentHeight);
			if (screenConf.hasOwnProperty("bottom")) return screen.position.bottom.toPx(Arrange.parentHeight);
			return 0;
		}

		private function setScreenBottomAndHeight(screen:DisplayProperties, heightPct:Number, bottom:Number = 0):void {
			if (! screenTopOrBottomConfigured()) {
				log.debug("screen vertical pos not configured, setting bottom to value " + bottom);
				screen.bottom = bottom;
			} else {
				log.debug("using configured top/bottom for screen");
			}

            var heightConfigured:Boolean = _config.getObject("screen") && _config.getObject("screen").hasOwnProperty("height");
			if (! heightConfigured) {
				log.debug("screen height not configured, setting it to value " + heightPct + "%");
				screen.height =  heightPct + "%";
			} else {
				log.debug("using configured height for screen");
			}
		}

		private function screenTopOrBottomConfigured():Boolean {
			var screen:Object = _config.getObject("screen");
			if (! screen) return false;
			if (screen.hasOwnProperty("top")) return true;
			if (screen.hasOwnProperty("bottom")) return true;
			return false;
		}

		private function isControlsAlwaysAutoHide():Boolean {
			if (!_controlsModel) return false;
            var controls:Object = _controlsModel.getDisplayObject();
			log.debug("controls.auotoHide " + controls.getAutoHide());
            //#583 this seems to handle the fullscreenOnly property better
            return  !controls.getAutoHide().fullscreenOnly;
		}

		private function createFlowplayer():void {
			_flowplayer = new Flowplayer(stage, _pluginRegistry, _panel,
				_animationEngine, this, this, _config, URLUtil.playerBaseUrl);

			_flowplayer.onBeforeFullscreen(onFullscreen);
//			_flowplayer.onFullscreenExit(onFullscreen);
		}

		private function onFullscreen(event:PlayerEvent):void {
            log.debug("entering fullscreen, disabling display clicks");
            _screenArrangeCount = 100;
            stage.removeEventListener(Event.RESIZE, arrangeScreen);

            _enteringFullscreen = true;
            var delay:Timer = new Timer(1000, 1);
            delay.addEventListener(TimerEvent.TIMER_COMPLETE, onTimerComplete);
            delay.start();
		}

		private function onTimerComplete(event:TimerEvent):void {
			log.debug("fullscreen wait delay complete, display clicks are enabled again");
			_enteringFullscreen = false;
		}

		private function createFlashVarsConfig():void {
            log.debug("createFlashVarsConfig()");
			if (! root.loaderInfo.parameters) {
				return;
			}
            var configStr:String = Preloader(root).injectedConfig || root.loaderInfo.parameters["config"];
            var configObj:Object = configStr && configStr.indexOf("{") == 0 ? ConfigParser.parse(configStr) : {};

            if (! configStr || (configStr && configStr.indexOf("{") == 0 && ! configObj.hasOwnProperty("url"))) {
                _config = ConfigParser.parseConfig(configObj, BuiltInConfig.config, loaderInfo.url, VersionInfo.controlsVersion, VersionInfo.audioVersion);
                callAndHandleError(initPhase1, PlayerError.INIT_FAILED);

            } else {
                ConfigParser.loadConfig(configObj.hasOwnProperty("url") ? String(configObj["url"]) : configStr, BuiltInConfig.config, function(config:Config):void {
                    _config = config;
                    callAndHandleError(initPhase1, PlayerError.INIT_FAILED);
                }, new ResourceLoaderImpl(null, this), loaderInfo.url, VersionInfo.controlsVersion, VersionInfo.audioVersion);
            }
		}

		private function createPlayListController():PlayListController {
            createHttpProviders();

            var playListController:PlayListController = new PlayListController(_config.getPlaylist(), _providers, _config, createNewLoader());
            playListController.playerEventDispatcher = _flowplayer;
            _flowplayer.playlistController = playListController;
            return playListController;
        }

        private function createHttpProviders():void {
            if (! _providers) {
                _providers = new Dictionary();
            }
            _providers["http"] = createProvider("http");
            _providers["httpInstream"] = createProvider("httpInstream");
        }

        private function createProvider(name:String):Object {
            log.debug("creating provider with name " + name);
            var httpProvider:ProviderModel = _config.createHttpProvider(name);
            _pluginRegistry.registerProvider(httpProvider);
            return httpProvider.pluginObject;
        }

        private function get hasHttpChildClip():Boolean {
            var children:Array = _config.getPlaylist().childClips;
//            log.debug("configuration has child clips", children);
            for (var i:int = 0; i < children.length; i++) {
                if (Clip(children[i]).provider == "httpInstream") {
                    log.info("child clip with http provider found");
                    return true;
                }
            }
            return false;
        }

		private function createScreen():void {
			_screen = new Screen(_config.getPlaylist(), _animationEngine, _playButtonOverlay, _pluginRegistry);
			var screenModel:DisplayProperties = _config.getScreenProperties();
			initView(_screen, screenModel, null, false);
			if (_playButtonOverlay) {
				PlayButtonOverlayView(_playButtonOverlay.getDisplayObject()).setScreen(_screen, hasClip && _config.useBufferingAnimation);
			}
//			addViewLiteners(_screen);
		}

		private function createPlayButtonOverlay():void {
			_playButtonOverlay = _config.getPlayButtonOverlay();
			if (! _playButtonOverlay) return;

			_playButtonOverlay.onLoad(onPluginLoad);
			_playButtonOverlay.onError(onPluginLoadError);

			var overlay:PlayButtonOverlayView = new PlayButtonOverlayView(! playButtonOverlayWidthDefined(), _playButtonOverlay, _pluginRegistry);
			initView(overlay, _playButtonOverlay, null, false);
		}

		private function playButtonOverlayWidthDefined():Boolean {
			if (! _config.getObject("play")) return false;
			return _config.getObject("play").hasOwnProperty("width");
		}

		private function get hasClip():Boolean {
			var firstClip:Clip = _config.getPlaylist().current;
			var hasClip:Boolean = ! firstClip.isNullClip && (firstClip.url || firstClip.provider != 'http');
			return hasClip;
		}

		private function createLogo():void {
            var logoView:LogoView = new LogoView(_panel, _flowplayer);
            var logo:Logo = _config.getLogo(logoView) || new Logo(logoView, "logo");
            // do not show it initially
            logo.visible = false;
            logoView.model = logo;
			initView(logoView, logo, logoView.draw, false);
		}

		private function initView(view:DisplayObject, props:DisplayProperties, resizeListener:Function = null, addToPanel:Boolean = true):void {
			if (props.name != "logo" || VersionInfo.commercial) {
				_pluginRegistry.registerDisplayPlugin(props, view);
			}
			if (addToPanel) {
				_panel.addView(view, resizeListener, props);
			}
			if (props is Callable) {
				ExternalInterfaceHelper.initializeInterface(props as Callable, view);
			}
		}

		private function addListeners():void {
            _clickTimer.addEventListener(TimerEvent.TIMER, onClickTimer);

            doubleClickEnabled = true;
            addEventListener(MouseEvent.DOUBLE_CLICK, onDoubleClick);

            _screen.addEventListener(MouseEvent.CLICK, onClickEvent);
            if (_playButtonOverlay) {
                _playButtonOverlay.getDisplayObject().addEventListener(MouseEvent.CLICK, onClickEvent);
            }
			addEventListener(MouseEvent.ROLL_OVER, onMouseOver);
			addEventListener(MouseEvent.ROLL_OUT, onMouseOut);

			// add some color so that the ROLL_OVER/ROLL_OUT events are always triggered
			graphics.beginFill(0, 0);
			graphics.drawRect(0, 0, Arrange.parentWidth, Arrange.parentHeight);
			graphics.endFill();

            //#508 disabling the stagevideo screen mask, canvas is visible without it.
            CONFIG::FLASH_10_1 {
			   _flowplayer.playlist.onStageVideoStateChange(onStageVideoStateChange);

               //#44 fixes for #627, now bind and unbind stagevideo events during seeking to prevent the mask repositioning.
               _flowplayer.playlist.onBeforeSeek(function(event:ClipEvent):void {
                   _flowplayer.playlist.unbind(onStageVideoStateChange);
               });

               _flowplayer.playlist.onSeek(function(event:ClipEvent):void {
                   _flowplayer.playlist.onStageVideoStateChange(onStageVideoStateChange);
               });
            }
		}

		private function onMouseOut(event:MouseEvent):void {
			_flowplayer.dispatchEvent(PlayerEvent.mouseOut());
		}

		private function onMouseOver(event:MouseEvent):void {
			_flowplayer.dispatchEvent(PlayerEvent.mouseOver());
		}

        //#508 disabling the stagevideo screen mask, canvas is visible without it.
        CONFIG::FLASH_10_1 {
            private function onStageVideoStateChange(event:ClipEvent):void {
                var stageVideo:StageVideo = event.info as StageVideo;
                log.debug("stage video state changed " + stageVideo);

                if (stageVideo) {
                    //#44 fixes for #627 check if the stagevideo dimensions and positioning has changed to update the stage video mask with.
                    //unbinding and binding stage video events caused issues with instream playlists therefore has to be kept binded.
                    if (_screenMask.width !== stageVideo.viewPort.width) {
                        _screenMask.width = stageVideo.viewPort.width;
                    }

                    if (_screenMask.height !== stageVideo.viewPort.height) {
                        _screenMask.height = stageVideo.viewPort.height;
                    }

                    if (_screenMask.x !== stageVideo.viewPort.x) _screenMask.x = stageVideo.viewPort.x;
                    if (_screenMask.y !== stageVideo.viewPort.y) _screenMask.y = stageVideo.viewPort.y;

                    log.debug("mask dimensions " + _screenMask.width + " x " + _screenMask.height);
                    log.debug("mask pos " + _screenMask.x + ", " + _screenMask.y);


                    if (!contains(_screenMask)) {
                        //#508 stage video mask was being added to the top layer and hiding all children.
                        //_canvasLogo.visible = false;
                        //#20 for the free player swap the logo with the stage video mask to display underneath not on top.
                        CONFIG::freeVersion {

                            addChildAt(_screenMask, 0);
                            swapChildren(_screenMask, _copyrightNotice);
                            swapChildren(_screenMask, _canvasLogo);

                        }

                        CONFIG::commercialVersion {
                            addChildAt(_screenMask, 1);
                        }
                        //addChildAt(_screenMask, _canvasLogo ? 1 : 0);
                        log.debug("adding mask");
                    }
                } else {
                    if (contains(_screenMask)) {
                        log.debug("removing mask")
                        removeChild(_screenMask);
                    }
                }
            }
        }

		private function createPanel():void {
			_panel = new Panel();
			addChild(_panel);
		}

		private function startStreams():void {
			var canStart:Boolean = true;
			if (_flowplayer.state != State.WAITING) {
				log.debug("streams have been started in player.onLoad(), will not start streams here.");
				canStart = false;
			}
			if (! hasClip) {
				log.info("Configuration has no clips to play.");
				canStart = false;
			}

			var playButton:PlayButtonOverlayView = _playButtonOverlay ? PlayButtonOverlayView(_playButtonOverlay.getDisplayObject()) : null;

			if (canStart) {
				if (_flowplayer.currentClip.autoPlay) {
					log.debug("clip is autoPlay");
					_flowplayer.play();
				} else if (_flowplayer.currentClip.autoBuffering) {
					log.debug("clip is autoBuffering");
					_flowplayer.startBuffering();
				} else {
					if (playButton) {
						playButton.stopBuffering();
						playButton.showButton();
					}
				}
			} else {
				// cannot start playing here, stop buffering indicator, don't show the button
				if (playButton) {
					playButton.stopBuffering();
				}
			}
		}

		private function addPlayListListeners():void {
			var playlist:Playlist = _config.getPlaylist();
			playlist.onError(onClipError);
            playlist.onBegin(onBegin);
		}

        private function onBegin(event:ClipEvent):void {
            this.buttonMode = Boolean(Clip(event.target).linkUrl);
        }

		private function onClipError(event:ClipEvent):void {
            if (event.isDefaultPrevented()) return;
			doHandleError(event.error.code + ", " + event.error.message + ", " + event.info2 + ", clip: '" + Clip(event.target) + "'");
		}

        private function onClickTimer(event:TimerEvent):void {
            if (_clickCount == 1) {
                onSingleClick(_clickEvent);
            }
            _clickCount = 0;
        }

        private function onDoubleClick(event:MouseEvent = null):void {
            log.debug("onDoubleClick");
            _flowplayer.toggleFullscreen();
        }

        private function onSingleClick(event:MouseEvent):void {
            if (isParent(DisplayObject(event.target), _screen)) {
                log.debug("screen clicked");
                _flowplayer.toggle();
            }
        }

        private function onClickEvent(event:MouseEvent):void {
            if (_enteringFullscreen) return;
            log.debug("onViewClicked, target " + event.target + ", current target " + event.currentTarget);
            event.stopPropagation();

            if (_playButtonOverlay && isParent(DisplayObject(event.target), _playButtonOverlay.getDisplayObject())) {
                _flowplayer.toggle();
                return;
            } else {
                // if using linkUrl, no doubleclick to fullscreen
                var clip:Clip = _flowplayer.playlist.current;
                if (clip.linkUrl) {
                    log.debug("opening linked page " + clip.linkUrl);
                    _flowplayer.pause();
                    URLUtil.openPage(clip.linkUrl, clip.linkWindow);
                    return;
                }
            }
            if (++_clickCount == 2) {
                onDoubleClick(event);
            } else {
                _clickEvent = event;
                _clickTimer.start();
            }
        }

		private function isParent(child:DisplayObject, parent:DisplayObject):Boolean {
			try {
                if (DisplayObject(child).parent == parent) return true;
                if (! (parent is DisplayObjectContainer)) return false;
                for (var i:Number = 0;i < DisplayObjectContainer(parent).numChildren; i++) {
                    var curChild:DisplayObject = DisplayObjectContainer(parent).getChildAt(i);
                    if (isParent(child, curChild)) {
                        return true;
                    }
                }
            } catch (e:SecurityError) {
                return true;
            }
            return false;
		}

		override protected function onRedraw():void {
			if (bgImageHolder && getChildIndex(bgImageHolder) > getChildIndex(_panel)) {
				swapChildren(bgImageHolder, _panel);
			}
		}

		private function createLogoForCanvas():void {
			if (_canvasLogo) return;
			_copyrightNotice = LogoUtil.createCopyrightNotice(8);
			addChild(_copyrightNotice);

			_canvasLogo = new CanvasLogo();
			_canvasLogo.width = 85;
			_canvasLogo.scaleY = _canvasLogo.scaleX;
			_canvasLogo.alpha = .4;
			_canvasLogo.addEventListener(MouseEvent.CLICK,
				function(event:MouseEvent):void { navigateToURL(new URLRequest("http://flowplayer.org"), "_self"); });
			_canvasLogo.buttonMode = true;
			log.debug("adding logo to display list");
			addChild(_canvasLogo);
			onStageResize();
		}

		private function createNewLoader():ResourceLoader {
			return new ResourceLoaderImpl(_config.playerId ? null : URLUtil.playerBaseUrl, this);
		}

        private function initCustomClipEvents():void {
            createCustomClipEvents(_config.connectionCallbacks);
            createCustomClipEvents(_config.streamCallbacks);
        }

        private function createCustomClipEvents(callbacks:Array):void {
            if (! callbacks) return;
            for (var i:int = 0; i < callbacks.length; i++) {
                log.debug("creating custom event type " + callbacks[i]);
                new ClipEventType(callbacks[i], true);
            }
        }

        private function callAndHandleError(func:Function, error:PlayerError):void {
            try {

                func();
            } catch (e:Error) {
                handleError(error, e, false);
                throw e;
            }
        }

        internal function get enteringFullscreen():Boolean {
            return _enteringFullscreen;
        }
    }
}