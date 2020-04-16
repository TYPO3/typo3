/**
 * https://github.com/animationatwork/web-animate
 * @license MIT
 */
var WebAnimate = (function (exports) {
  'use strict';

  var upperCasePattern = /[A-Z]/g;
  var propLower = function (m) { return "-" + m.toLowerCase(); };
  var msPattern = /^ms-/;
  var _ = undefined;
  var idle = 'idle';
  var finished = 'finished';
  var paused = 'paused';
  var running = 'running';

  function hyphenate(propertyName) {
    return (propertyName
      .replace(upperCasePattern, propLower)
      .replace(msPattern, '-ms-'));
  }
  function propsToString(keyframe) {
    var rules = [];
    for (var key in keyframe) {
      var value = keyframe[key];
      if (value !== null && value !== _) {
        rules.push(hyphenate(key.trim()) + ':' + value);
      }
    }
    return rules.sort().join(';');
  }
  function waapiToString(keyframes) {
    var frames = {};
    for (var i = 0, ilen = keyframes.length; i < ilen; i++) {
      var keyframe = keyframes[i];
      var offset = keyframe.offset;
      var target = frames[offset] || (frames[offset] = {});
      for (var key in keyframe) {
        var newKey = key;
        if (key === 'easing') {
          newKey = 'animation-timing-function';
        }
        if (key !== 'offset') {
          target[newKey] = keyframe[key];
        }
      }
    }
    var keys = Object.keys(frames).sort();
    var jlen = keys.length;
    var rules = Array(jlen);
    for (var j = 0; j < jlen; j++) {
      var key = keys[j];
      rules[j] = +key * 100 + '%{' + propsToString(frames[key]) + '}';
    }
    return rules.join('\n');
  }

  var sheet;
  var rulesAdded = {};
  function stringHash(str) {
    var value = 5381;
    var len = str.length;
    while (len--) {
      value = (value * 33) ^ str.charCodeAt(len);
    }
    return (value >>> 0).toString(36);
  }
  function insertKeyframes(rules) {
    var hash = 'ea_' + stringHash(rules);
    if (!rulesAdded[hash]) {
      rulesAdded[hash] = 1;
      if (!sheet) {
        var styleElement = document.createElement('style');
        styleElement.setAttribute('rel', 'stylesheet');
        document.head.appendChild(styleElement);
        sheet = styleElement.sheet;
      }
      sheet.insertRule("@keyframes " + hash + "{" + rules + "}", sheet.cssRules.length);
    }
    return hash;
  }

  var global = window || global;
  var lastTime;
  var taskId;
  function resetTime() {
    lastTime = 0;
    taskId = 0;
  }
  function now() {
    taskId = taskId || nextFrame(resetTime);
    return (lastTime = lastTime || (global.performance || Date).now());
  }
  var nextFrame = function (fn, time) { return setTimeout(fn, time || 0); };

  var ANIMATION_PROPS = [
    'Name',
    'Duration',
    'Delay',
    'IterationCount',
    'Direction',
    'FillMode',
    'PlayState',
    'TimingFunction'
  ];
  var ANIMATION = 'animation';
  var WEBKIT = 'webkitAnimation';
  var MS = 'msAnimation';
  function enqueueElement(el) {
    var animations = el._animations;
    var style = el.style;
    var lastVisibility = style.visibility;
    style.visibility = 'hidden';
    for (var i = 0, ilen = ANIMATION_PROPS.length; i < ilen; i++) {
      var key = ANIMATION_PROPS[i];
      style[ANIMATION + key] = style[WEBKIT + key] = style[MS + key] = '';
    }
    void el.offsetWidth;
    for (var i = 0, ilen = ANIMATION_PROPS.length; i < ilen; i++) {
      var key = ANIMATION_PROPS[i];
      var value = void 0;
      for (var name in animations) {
        var animation = animations[name];
        if (animation) {
          value = (value ? ',' : '') + animation[key];
        }
      }
      style[ANIMATION + key] = style[WEBKIT + key] = style[MS + key] = value;
    }
    style.visibility = lastVisibility;
  }

  var epsilon = 0.0001;
  function Animation(element, keyframes, timingOrDuration) {
    var timing = typeof timingOrDuration === 'number'
      ? { duration: timingOrDuration }
      : timingOrDuration;
    timing.direction = timing.direction || 'normal';
    timing.easing = timing.easing || 'linear';
    timing.iterations = timing.iterations || 1;
    timing.fill = timing.fill || 'none';
    timing.delay = timing.delay || 0;
    timing.endDelay = timing.endDelay || 0;
    var self = this;
    self._element = element;
    self._rate = 1;
    var fill = timing.fill;
    var fillBoth = fill === 'both';
    self._fillB = fillBoth || fill === 'forwards';
    self._fillF = fillBoth || fill === 'backwards';
    var rules = waapiToString(keyframes);
    self.id = insertKeyframes(rules);
    self._timing = timing;
    self._totalTime = (timing.delay || 0) + timing.duration * timing.iterations + (timing.endDelay || 0);
    self._yoyo = timing.direction.indexOf('alternate') !== -1;
    self._reverse = timing.direction.indexOf('reverse') !== -1;
    self.finish = self.finish.bind(self);
    self.play();
  }
  Animation.prototype = {
    get currentTime() {
      var time = updateTiming(this)._time;
      return isFinite(time) ? time : null;
    },
    set currentTime(val) {
      var self = this;
      self._time = val;
      updateTiming(self);
      scheduleOnFinish(self);
      updateElement(self);
    },
    get playbackRate() {
      return updateTiming(this)._rate;
    },
    set playbackRate(val) {
      var self = this;
      self._rate = val;
      updateTiming(self);
      scheduleOnFinish(self);
      updateElement(self);
    },
    get playState() {
      return updateTiming(this)._state;
    },
    cancel: function () {
      var self = this;
      updateTiming(self);
      self._time = self._startTime = _;
      self._state = idle;
      clearOnFinish(self);
      updateElement(self);
      self.oncancel && self.oncancel();
    },
    finish: function () {
      var self = this;
      updateTiming(self);
      var start = 0 + epsilon;
      var end = self._totalTime - epsilon;
      self._state = finished;
      self._time = self._rate >= 0 ? (self._fillB ? end : start) : self._fillF ? start : end;
      self._startTime = _;
      self.pending = false;
      clearOnFinish(self);
      updateElement(self);
      self.onfinish && self.onfinish();
    },
    play: function () {
      var self = this;
      updateTiming(self);
      var isForwards = self._rate >= 0;
      var time = self._time === _ ? _ : Math.round(self._time);
      time = isForwards && (time === _ || time >= self._totalTime) ? 0
        : !isForwards && (time === _ || time <= 0)
          ? self._totalTime : time;
      self._state = running;
      self._time = time;
      self._startTime = now();
      scheduleOnFinish(self);
      updateElement(self);
    },
    pause: function () {
      var self = this;
      if (self._state === finished) {
        return;
      }
      updateTiming(self);
      self._state = paused;
      self._startTime = _;
      clearOnFinish(self);
      updateElement(self);
    },
    reverse: function () {
      this.playbackRate = this._rate * -1;
    }
  };
  function clearOnFinish(self) {
    self._finishTaskId && clearTimeout(self._finishTaskId);
  }
  function updateElement(self) {
    var el = self._element;
    var state = self._state;
    var style = el.style;
    if (state === idle) {
      style.animationName = style.animationPlayState = style.animationDelay = '';
    }
    else {
      if (!isFinite(self._time)) {
        self._time = self._rate >= 0 ? 0 : self._totalTime;
      }
      updateAnimation(self);
    }
  }
  function updateAnimation(self) {
    var s = self._state, t = self._timing;
    var playState = s === finished || s === paused ? paused : s;
    var el = self._element;
    var animations = el._animations || (el._animations = {});
    var a = animations[self.id] || (animations[self.id] = {});
    if (s === idle) {
      for (var key in a) {
        a[key] = _;
      }
    }
    else {
      a.Name = self.id;
      a.Duration = self._totalTime + 'ms';
      a.Delay = -toLocalTime(self) + 'ms';
      a.TimingFunction = t.easing;
      a.IterationCount = isFinite(t.iterations) ? t.iterations + '' : 'infinite';
      a.Direction = t.direction;
      a.FillMode = t.fill;
      a.PlayState = playState;
    }
    enqueueElement(el);
  }
  function toLocalTime(self) {
    var timing = self._timing;
    var timeLessDelay = self._time - (timing.delay + timing.endDelay);
    var localTime = timeLessDelay % timing.duration;
    if (self._reverse) {
      localTime = self._timing.duration - localTime;
    }
    if (self._yoyo && !(Math.floor(timeLessDelay / timing.duration) % 2)) {
      localTime = self._timing.duration - localTime;
    }
    return self._totalTime < localTime ? self._totalTime : localTime < 0 ? 0 : localTime;
  }
  function updateTiming(self) {
    var startTime = self._startTime;
    var state = self._state;
    if (!self.pending && state === running) {
      self.pending = true;
      var next = now();
      var time = void 0;
      time = Math.round(self._time + (next - startTime));
      self._time = time;
      self._startTime = next;
      var isForwards = self._rate >= 0;
      if ((isForwards && time >= self._totalTime) || (!isForwards && time <= 0)) {
        self.finish();
      }
      self.pending = false;
    }
    return self;
  }
  function scheduleOnFinish(self) {
    if (self._state !== running) {
      return;
    }
    clearOnFinish(self);
    var isForwards = self._rate >= 0;
    var _remaining = isForwards ? self._totalTime - self._time : self._time;
    self._finishTaskId = nextFrame(self.finish, _remaining);
  }

  function animateElement(keyframes, timingOrDuration) {
    return new Animation(this, keyframes, timingOrDuration);
  }
  function animate(el, keyframes, timingOrDuration) {
    return animateElement.call(el, keyframes, timingOrDuration);
  }
  function polyfill() {
    Element.prototype.animate = animateElement;
  }
  function isPolyfilled() {
    return Element.prototype.animate === animateElement;
  }
  if (typeof Element.prototype.animate === 'undefined') {
    polyfill();
  }

  exports.animate = animate;
  exports.polyfill = polyfill;
  exports.isPolyfilled = isPolyfilled;

  return exports;

}({}));
