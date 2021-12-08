export default (new function() {
  const module = { exports: {} }, exports = module.exports, define = null;
var WebAnimate=function(t){"use strict"
var i,e=/[A-Z]/g,n=function(t){return"-"+t.toLowerCase()},a=/^ms-/,r=void 0,o="idle",s="finished",l="paused",m="running"
function f(t){var i=[]
for(var o in t){var s=t[o]
null!==s&&s!==r&&i.push(o.trim().replace(e,n).replace(a,"-ms-")+":"+s)}return i.sort().join(";")}var u,_,c={},d=window||d
function h(){u=0,_=0}function v(){return _=_||y(h),u=u||(d.performance||Date).now()}var y=function(t,i){return setTimeout(t,i||0)},p=["Name","Duration","Delay","IterationCount","Direction","FillMode","PlayState","TimingFunction"],g="animation",T="webkitAnimation",b="msAnimation",D=1e-4
function k(t,e,n){var a="number"==typeof n?{duration:n}:n
a.direction=a.direction||"normal",a.easing=a.easing||"linear",a.iterations=a.iterations||1,a.fill=a.fill||"none",a.delay=a.delay||0,a.endDelay=a.endDelay||0
var r=this
r._element=t,r._rate=1
var o=a.fill,s="both"===o
r._fillB=s||"forwards"===o,r._fillF=s||"backwards"===o
var l=function(t){for(var i={},e=0,n=t.length;n>e;e++){var a=t[e],r=a.offset,o=i[r]||(i[r]={})
for(var s in a){var l=s
"easing"===s&&(l="animation-timing-function"),"offset"!==s&&(o[l]=a[s])}}for(var m=Object.keys(i).sort(),u=m.length,_=Array(u),c=0;u>c;c++)s=m[c],_[c]=100*+s+"%{"+f(i[s])+"}"
return _.join("\n")}(e)
r.id=function(t){var e="ea_"+function(t){for(var i=5381,e=t.length;e--;)i=33*i^t.charCodeAt(e)
return(i>>>0).toString(36)}(t)
if(!c[e]){if(c[e]=1,!i){var n=document.createElement("style")
n.setAttribute("rel","stylesheet"),document.head.appendChild(n),i=n.sheet}i.insertRule("@keyframes "+e+"{"+t+"}",i.cssRules.length)}return e}(l),r._timing=a,r._totalTime=(a.delay||0)+a.duration*a.iterations+(a.endDelay||0),r._yoyo=-1!==a.direction.indexOf("alternate"),r._reverse=-1!==a.direction.indexOf("reverse"),r.finish=r.finish.bind(r),r.play()}function F(t){t._finishTaskId&&clearTimeout(t._finishTaskId)}function w(t){var i=t._element,e=t._state,n=i.style
e===o?n.animationName=n.animationPlayState=n.animationDelay="":(isFinite(t._time)||(t._time=0>t._rate?t._totalTime:0),function(t){var i,e,n,a,m=t._state,f=t._timing,u=m===s||m===l?l:m,_=t._element,c=_._animations||(_._animations={}),d=c[t.id]||(c[t.id]={})
if(m===o)for(var h in d)d[h]=r
else d.Name=t.id,d.Duration=t._totalTime+"ms",d.Delay=(i=t,e=i._timing,n=i._time-(e.delay+e.endDelay),a=n%e.duration,i._reverse&&(a=i._timing.duration-a),!i._yoyo||Math.floor(n/e.duration)%2||(a=i._timing.duration-a),-(i._totalTime<a?i._totalTime:0>a?0:a)+"ms"),d.TimingFunction=f.easing,d.IterationCount=isFinite(f.iterations)?f.iterations+"":"infinite",d.Direction=f.direction,d.FillMode=f.fill,d.PlayState=u
!function(t){var i=t._animations,e=t.style,n=e.visibility
e.visibility="hidden"
for(var a=0,r=p.length;r>a;a++){var o=p[a]
e[g+o]=e[T+o]=e[b+o]=""}for(t.offsetWidth,a=0,r=p.length;r>a;a++){o=p[a]
var s=void 0
for(var l in i){var m=i[l]
m&&(s=(s?",":"")+m[o])}e[g+o]=e[T+o]=e[b+o]=s}e.visibility=n}(_)}(t))}function A(t){var i=t._startTime,e=t._state
if(!t.pending&&e===m){t.pending=!0
var n,a=v()
n=Math.round(t._time+(a-i)),t._time=n,t._startTime=a
var r=t._rate>=0;(r&&n>=t._totalTime||!r&&0>=n)&&t.finish(),t.pending=!1}return t}function C(t){if(t._state===m){F(t)
var i=0>t._rate?t._time:t._totalTime-t._time
t._finishTaskId=y(t.finish,i)}}function I(t,i){return new k(this,t,i)}function M(){Element.prototype.animate=I}return k.prototype={get currentTime(){var t=A(this)._time
return isFinite(t)?t:null},set currentTime(t){this._time=t,A(this),C(this),w(this)},get playbackRate(){return A(this)._rate},set playbackRate(t){this._rate=t,A(this),C(this),w(this)},get playState(){return A(this)._state},cancel:function(){var t=this
A(t),t._time=t._startTime=r,t._state=o,F(t),w(t),t.oncancel&&t.oncancel()},finish:function(){var t=this
A(t)
var i=0+D,e=t._totalTime-D
t._state=s,t._time=0>t._rate?t._fillF?i:e:t._fillB?e:i,t._startTime=r,t.pending=!1,F(t),w(t),t.onfinish&&t.onfinish()},play:function(){var t=this
A(t)
var i=t._rate>=0,e=t._time===r?r:Math.round(t._time)
e=!i||e!==r&&e<t._totalTime?i||e!==r&&e>0?e:t._totalTime:0,t._state=m,t._time=e,t._startTime=v(),C(t),w(t)},pause:function(){var t=this
t._state!==s&&(A(t),t._state=l,t._startTime=r,F(t),w(t))},reverse:function(){this.playbackRate=-1*this._rate}},"undefined"==typeof Element.prototype.animate&&M(),t.animate=function(t,i,e){return I.call(t,i,e)},t.polyfill=M,t.isPolyfilled=function(){return Element.prototype.animate===I},t}({})

  this.__default_export = module.exports;
}).__default_export;