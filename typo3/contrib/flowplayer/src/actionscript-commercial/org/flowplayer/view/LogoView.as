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
    import flash.events.FullScreenEvent;
    import flash.events.MouseEvent;
    import flash.events.TimerEvent;
    import flash.net.URLRequest;
    import flash.net.navigateToURL;
    import flash.text.TextField;
    import flash.utils.Timer;

    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.controller.ResourceLoaderImpl;
    import org.flowplayer.model.Logo;
    import org.flowplayer.util.PropertyBinder;
    import org.flowplayer.util.URLUtil;

    /**
	 * @author api
	 */
	public class LogoView extends AbstractSprite {

		private var _model:Logo;
		private var _player:Flowplayer;
		private var _image:DisplayObject;
		private var _panel:Panel;
		private var _copyrightNotice:TextField;
        private var _preHideAlpha:Number = -1;
        private var _hideTimer:Timer;

        public function LogoView(panel:Panel, player:Flowplayer) {
            _panel = panel;
            _player = player;
        }

        public function set model(model:Logo):void {
            setModel(model);
            log.debug("fullscreenOnly " + model.fullscreenOnly);
            setEventListeners();

            CONFIG::commercialVersion {
                if (BuiltInAssetHelper.hasLogo) {
                    log.debug("Using built in logo image");
                    initializeLogoImage(BuiltInAssetHelper.createLogo());
                } else if (_model.url) {
                    load(_model.url, _model.fullscreenOnly);
                }
            }

            CONFIG::freeVersion {
                _copyrightNotice = LogoUtil.createCopyrightNotice(10);
                addChild(_copyrightNotice);
                _model.width = "6.5%";
                _model.height = "6.5%";
                initializeLogoImage(BuiltInAssetHelper.createLogo() || new FlowplayerLogo());
            }

            log.debug("LogoView() model dimensions " + _model.dimensions);
        }

		override protected function onResize():void {
			if (_image) {
				log.debug("onResize, " + _model.dimensions);
				if (_model.dimensions.width.hasValue() && _model.dimensions.height.hasValue()) {
                    log.debug("onResize(), scaling image according to model");
					if (_image.height - copyrightNoticeheight() > _image.width) {
						_image.height = height - copyrightNoticeheight();
						_image.scaleX = _image.scaleY;
					} else {
						_image.width = width;
						_image.scaleY = _image.scaleX;
					}
				}
//				Arrange.center(_image, width, height);
				_image.x = width - _image.width;
				_image.y = 0;
                // log.debug("image: " + Arrange.describeBounds(_image));

				CONFIG::freeVersion {
					_copyrightNotice.y = _image.height;
                    _copyrightNotice.visible = _copyrightNotice.textWidth < width;
                    _copyrightNotice.width = width;
				}
			}
		}
		
		CONFIG::freeVersion
		private function copyrightNoticeheight():Number {
			return _copyrightNotice.height;
		}
		
		CONFIG::commercialVersion
		private function copyrightNoticeheight():Number {
			return 0;
		}
		
//		override public function get width():Number {
//			return managedWidth;
//		}
//		
//		override public function get height():Number {
//			return managedHeight;
//		}

		CONFIG::commercialVersion {
            [External]
            public function configure(props:Object):void {
                _model = Logo(_player.pluginRegistry.getPlugin(_model.name));
                new PropertyBinder(_model).copyProperties(props);

                if (_model.url) {
                    load(_model.url, _model.fullscreenOnly);
                } else if (_image) {
                    removeChild(_image);
                }

                if (_model.linkUrl) {
                    setLinkEventListener();
                } else {
                    removeLinkEventListener();
                }
                _player.pluginRegistry.update(_model);
            }
        }

        CONFIG::commercialVersion {
            private function load(url:String, fullscreenOnly:Boolean):void {
                log.debug("load(), " + url);
                _model.url = url;
                _model.fullscreenOnly = fullscreenOnly;
                //var playerBaseUrl:String = URLUtil.playerBaseUrl(_panel.loaderInfo);
                var playerBaseUrl:String = URLUtil.playerBaseUrl;

                if (_image && _image.parent == this) {
                    removeChild(_image);
                }

                log.debug("loading image from " + url);
                var loader:ResourceLoader = new ResourceLoaderImpl(playerBaseUrl, _player);
                loader.load(url, onImageLoaded);
            }
        }

		CONFIG::commercialVersion
		private function onImageLoaded(loader:ResourceLoader):void {
			log.debug("image loaded " + loader.getContent());
			initializeLogoImage(loader.getContent() as DisplayObject);
		}
		
		private function initializeLogoImage(image:DisplayObject):void {
            log.debug("initializeLogoImage(), setting logo alpha to " + _model.alpha);
            _image = image;

//            CONFIG::commercialVersion {
//                _model.width = image.width;
//                _model.height = image.height;
//            }

			addChild(_image);
			log.debug("createLogoImage() logo shown in fullscreen only " + _model.fullscreenOnly);
			if (! _model.fullscreenOnly) {
				show();
			} else {
                hide(0);
            }
            update();
			onResize();
		}

		private function setEventListeners():void {
			_panel.stage.addEventListener(FullScreenEvent.FULL_SCREEN, onFullscreen);
            setLinkEventListener();
		}

        private function setLinkEventListener():void {
            if (_model.linkUrl) {
                addEventListener(MouseEvent.CLICK, onClick);
                buttonMode = true;
            }
        }

        private function removeLinkEventListener():void {
            removeEventListener(MouseEvent.CLICK, onClick);
            buttonMode = false;
        }


        private function onClick(event:MouseEvent):void {
            navigateToURL(new URLRequest(_model.linkUrl), _model.linkWindow);
        }

		private function onFullscreen(event:FullScreenEvent):void {
            log.debug("onFullscreen(), " + (event.fullScreen ? "enter fullscreen" : "exit fullscreen"));
			if (event.fullScreen) {
				
                if ( (_hideTimer && _hideTimer.running)) {
                    log.debug("onFullscreen(), hide timer is running -> returning")
                    // hide timer is running or the hide time already passed
                    return;
                }

				show();
			} else {
				if (_model.fullscreenOnly) {
					if(_hideTimer && _hideTimer.running) {
						_hideTimer.reset();
						_hideTimer = null;
					}
					hide(0);
				}
			}
		}

		private function show():void {
            log.debug("show()");
            if (_preHideAlpha != -1) {
                this.alpha = _preHideAlpha;
                _model.alpha = _preHideAlpha;
            }
            _model.visible = true;
			this.visible = true;
			CONFIG::freeVersion {
				_model.zIndex = 100;
			}
			if (! this.parent) {
				log.debug("showing " + _model.dimensions + ", " + _model.position);
//                _player.animationEngine.fadeIn(this);
                _panel.addView(this, null, _model);

				if (_model.displayTime > 0) {
                    log.debug("show() creating hide timer");
					startTimer();
				}
			}
//            else {
//				update();
//			}
		}

		private function update():void {
            if (! this.parent) return;
            log.debug("update() " + _model.dimensions + ", " + _model.position);
			_panel.update(this, _model);
			_panel.draw(this);

            if (_player.pluginRegistry.getPlugin(_model.name)) {
                _player.pluginRegistry.updateDisplayProperties(_model);
            }
		}
		
		private function hide(fadeSpeed:int = 0):void {
			log.debug("hide(), hiding logo");
            _preHideAlpha = _model.alpha;
            if (fadeSpeed > 0) {
				_player.animationEngine.fadeOut(this, fadeSpeed);
			} else {
				removeFromPanel();
			}
		}

		private function removeFromPanel():void {
            log.debug("removeFromPanel() " + this.parent);
			if (this.parent) {
                // log.debug("removing logo from panel");
				_panel.removeChild(this);
            }
		}
		
		private function startTimer():void {

			_hideTimer = new Timer(_model.displayTime * 1000, 1);
			_hideTimer.addEventListener(TimerEvent.TIMER_COMPLETE,
							function(event:TimerEvent):void {
                                log.debug("display time complete");
                                hide(_model.fadeSpeed);
                                _hideTimer.stop();
                            });
			_hideTimer.start();
		}
		
		CONFIG::freeVersion
		public function setModel(model:Logo):void {
            log.debug("setModel() ignoring configured logo settings");
			// in the free version we ignore the supplied logo configuration
			_model = new Logo(this, "logo");
			_model.fullscreenOnly = model.fullscreenOnly;
			_model.height = "9%";
			_model.width = "9%";
			_model.top = "20";
			_model.right = "20";
			_model.opacity = 0.3;
			_model.linkUrl = "http://flowplayer.org";
			log.debug("initial model dimensions " + _model.dimensions);
		}
		
		CONFIG::commercialVersion
		public function setModel(model:Logo):void {
            log.debug("setModel() using configured logo settings");
			_model = model;
		}
    }
}
