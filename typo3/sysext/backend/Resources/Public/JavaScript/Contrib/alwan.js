const Oe={id:"",classname:"",theme:"light",parent:"",toggle:!0,popover:!0,position:"bottom-start",margin:4,preset:!0,color:"#000",default:"#000",target:"",disabled:!1,format:"rgb",singleInput:!1,inputs:!0,opacity:!0,preview:!0,copy:!0,swatches:[],toggleSwatches:!1,closeOnScroll:!1,i18n:{palette:"Color picker",buttons:{copy:"Copy color to clipboard",changeFormat:"Change color format",swatch:"Color swatch",toggleSwatches:"Toggle Swatches"},sliders:{hue:"Change hue",alpha:"Change opacity"}}},Ve='<svg width="18" height="18" viewBox="0 0 24 24" aria-role="none"><path d="M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z"></path></svg>',ye=parseInt,{min:Q,max:ve,abs:Te,round:z,PI:st}=Math,ee=(e,t=100,s=0)=>e>t?t:e<s?s:e,Be=e=>z((e%=360)<0?e+360:e),O=document,D=O.documentElement,we="button",Fe="open",Re="close",te="color",U="click",$e="pointerdown",ze="scroll",_e="keydown",P="input",W="change",De="blur",j="rgb",ie="hsl",Pe=["hex",j,ie],le="aria-label",nt={ArrowUp:[0,-1],ArrowDown:[0,1],ArrowRight:[1,0],ArrowLeft:[-1,0]},je={deg:1,turn:360,rad:180/st,grad:.9},ot=/^hsla?\(\s*([+-]?\d*\.?\d+)(\w*)?\s*[\s,]\s*([+-]?\d*\.?\d+)%?\s*,?\s*([+-]?\d*\.?\d+)%?(?:\s*[\/,]\s*([+-]?\d*\.?\d+)(%)?)?\s*\)?$/,q=(e,t=j)=>{let s=e.a,o="",n=t;return s<1&&(o+=", "+s,n+="a"),t===j?n+`(${e.r}, ${e.g}, ${e.b+o})`:n+`(${e.h}, ${e.s}%, ${e.l}%${o})`},L="alwan",Ne=`${L}__container`,rt=`${L}__palette`,it=`${L}__marker`,lt=`${L}__preview`,Ze=`${L}__button`,at=`${L}__copy-button`,ct=`${L}__slider`,ht=`${L}__hue`,pt=`${L}__alpha`,ut=`${L}__input`,gt=`${L}__inputs`,dt=`${L}__swatch`,ft=`${L}__swatches`,mt=`${L}__reference`,bt=`${L}__backdrop`,yt=`${L}__toggle-button`,vt=`${L}--open`,Ke=`${L}--collapse`,k=(e,t,s,o)=>e.addEventListener(t,s,o),ae=(e,t,s)=>e.removeEventListener(t,s),N=e=>typeof e=="string",X=e=>e!=null,Se=e=>e instanceof Element,ce=e=>Number.isFinite(N(e)&&e.trim()!==""?+e:e),{keys:wt,assign:Y,setPrototypeOf:$t,prototype:_t}=Object,{from:Ue,isArray:We}=Array,xe=e=>X(e)&&typeof e=="object"&&!We(e)&&!Se(e),G=(e,t)=>wt(e).forEach(s=>t(s,e[s])),he=(e,t)=>(xe(e)||(e={}),G(t,(s,o)=>{X(o)&&Y(e,{[s]:xe(o)?he(e[s]||{},o):o})}),e),se=()=>O.body,pe=(e,t=D)=>N(e)&&e.trim()?Ue(t.querySelectorAll(e)):Se(e)?[e]:[],qe=e=>pe(`${P},${we},[tabindex]`,e),Z=(e,...t)=>e.append(...t.filter(s=>s)),ue=(e,t)=>{e.innerHTML=t},ne=(e,t,s)=>{e&&(ce(s)||s)&&e.setAttribute(t,s+"")},ge=(...e)=>e.join(" ").trim(),R=(e,t,s=[],o,n)=>{const a=O.createElement(e);return t&&(a.className=t),o&&ue(a,o),G(n||{},(y,h)=>ne(a,y,h)),Z(a,...s),a},V=(e,...t)=>R("div",e,t),de=e=>e.remove(),Ce=(e,t)=>(e.replaceWith(t),t),J=(e="",t="",s,o=e)=>R(we,ge(Ze,t),[],s,{type:we,[le]:e,title:o}),Xe=(e,t,s=1)=>R(P,ge(ct,e),[],"",{max:t,step:s,type:"range"}),oe=(e,t,s)=>(e&&e.style.setProperty("--"+t,s+""),e),ke=(e,t,s)=>e.classList.toggle(t,s),Le=(e,t,s)=>{e.style.transform=`translate(${t}px,${s}px)`},Ye=e=>e&&e.parentElement||se(),T=(e,t)=>{let s,o,n,a,y,h;return Se(e)?({x:s,y:o,width:n,height:a,right:y,bottom:h}=e.getBoundingClientRect(),t&&(s+=e.clientTop,o+=e.clientLeft)):(s=o=0,n=y=D.clientWidth,a=h=D.clientHeight),[s,o,n,a,y,h]},Ge=e=>e&&e!==se()?e instanceof ShadowRoot?e:Ge(e.parentNode):null,He=R("canvas").getContext("2d"),Ae=(e,t)=>{let s,o,n="";N(e)?n=e.trim():xe(e)&&(s=[j,ie].find(p=>p.split("").every(c=>ce(e[c]))),s&&(n=q(e,s)));const[a,y,h,f,M,E="1",x]=ot.exec(n)||[];if(a)o={h:Be(+y*(je[h]?je[h]:1)),s:ee(+f),l:ee(+M),a:ee(+E/(x?100:1),1)},s=ie;else if(s=j,/^[\da-f]+$/i.test(n)&&(n="#"+n),He.fillStyle="#000",He.fillStyle=n,n=He.fillStyle,n[0]==="#")o={r:ye(n.slice(1,3),16),g:ye(n.slice(3,5),16),b:ye(n.slice(5,7),16),a:1};else{const[p,c,r,m]=/\((.+)\)/.exec(n)[1].split(",").map(i=>+i);o={r:p,g:c,b:r,a:m}}return o.a=z(100*o.a)/100,t?q(o,s):[o,s]},St=e=>{let t,s,o,n=!1;return{t:({swatches:a,toggleSwatches:y,i18n:{buttons:h}})=>We(a)?(t=s=o=null,a.length&&(s=t=V(ge(ft,y&&n?Ke:""),...a.map(f=>oe(J(h.swatch,dt,"",N(f)?f:Ae(f,!0)),te,Ae(f,!0)))),y&&(o=J(h.toggleSwatches,yt,'<svg width="20" height="20" viewBox="0 0 24 24" aria-role="none"><path d="M6.984 14.016l5.016-5.016 5.016 5.016h-10.031z"></path></svg>'),k(o,U,()=>{n=!n,ke(s,Ke,n),e.i.o()}),t=V("",s,o)),k(s,U,({target:f})=>{f!==s&&e.u.p(f.style.getPropertyValue("--"+te),!0,!0)})),t):t}},xt=e=>{let t,s,o=!1;const n=h=>{o=h,ue(s,h?'<svg width="18" height="18" viewBox="0 0 24 24" aria-role="none"><path d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"></path></svg>':Ve)},a=h=>{const f=R(P);Z(D,f),f.value=h,f.select(),O.execCommand("copy"),de(f),s.focus(),n(!0)},y=()=>{if(!o){const h=navigator.clipboard,f=e.u._();h?h.writeText(f).then(()=>n(!0)).catch(()=>a(f)):a(f)}};return{t:({preview:h,copy:f,i18n:M})=>(t=s=null,f&&(s=J(M.buttons.copy,at,Ve),k(s,U,y),k(s,De,()=>o&&n(!1)),k(s,"mouseout",()=>s.blur())),h&&(t=V(lt,s)),t||s)}},Je={top:[1,5,4,0],bottom:[5,1,4,0],right:[4,0,1,5],left:[0,4,1,5]},Qe={start:[0,1,2],center:[1,0,2],end:[2,1,0]},et=(e,t=[O])=>{if((e=Ye(e))===se())return t;const{display:s,overflow:o}=getComputedStyle(e);return/auto|scroll|overflow|clip|hidden/.test(o)&&!["inline","contents"].includes(s)&&t.push(e),et(e,t)},tt=e=>(t=>{const s=!(typeof CSS>"u"||!CSS.supports)&&CSS.supports("-webkit-backdrop-filter","none"),{transform:o,perspective:n,filter:a,containerType:y,backdropFilter:h,willChange:f="",contain:M=""}=getComputedStyle(t);return o!=="none"||n!=="none"||y!=="normal"||!s&&h!=="none"||!s&&a!=="none"||/transform|perspective|filter/.test(f)||/paint|layout|strict|content/.test(M)})(e=Ye(e))?e:e===D||kt(e)?O:tt(e),Ct=[":popover-open",":modal"],kt=e=>Ct.some(t=>{try{return e.matches(t)}catch{return!1}}),Lt=(e,t,s,{margin:o,position:n,toggle:a,closeOnScroll:y},{m:h,$:f})=>{o=ce(o)?+o:0;const[M,E]=N(n)?n.split("-"):[],x=Je[M]||Je.bottom,p=Qe[E]||Qe.center,c=et(e),r=t.style,m=Ge(s),i=()=>{r.height="";const l=T(O),v=T(e),S=T(t),C=T(tt(t),!0),u=[null,null];x.some(g=>{let w=g%2;const A=l[g],_=v[g],I=o+S[w+2];if(I>Te(A-_))return!1;u[w]=_+(g<=1?-I:o),w=(w+1)%2;const K=w+4,B=w+2,F=S[B],Ee=v[w],re=v[K],Ie=l[K]-Ee,me=(F+v[B])/2;return p.some(be=>be==0&&F<=Ie?(u[w]=Ee,!0):be==1&&me<=re&&me<=Ie?(u[w]=re-me,!0):be==2&&F<=re&&(u[w]=re-F,!0)),!0}),Le(t,...u.map((g,w)=>(w&&g===null&&S[3]>l[5]&&(r.height=l[5]-6+"px",S[3]=l[5]-3),z((X(g)?g:(l[w+4]-S[w+2])/2)-C[w]))))},b=({type:l})=>{!h()&&a||(((v,S)=>S.every(C=>{const[u,g,,,w,A]=T(v),[_,I,,,K,B]=T(C);return g<B&&A>I&&u<K&&w>_}))(e,c)?h()?(i(),y&&l===ze&&f(!1)):f(!0,!0):f(!1,!0))},d=l=>{if(h()){const{target:v,key:S,shiftKey:C}=l;if(S==="Escape")f(!1);else if(S==="Tab"){const u=qe(t),g=u[0],w=v===s&&!C,A=C&&v===g||!C&&v===u.pop(),_=w?g:A?s:null;_&&(l.preventDefault(),_.focus())}}},$=({target:l})=>{!h()||m&&l===m.host||l===s||t.contains(l)||Ue(s.labels||[]).some(v=>v.contains(l))||f(!1)},H=l=>{c.forEach(v=>l(v,ze,b)),l(window,"resize",b),l(O,_e,d),l(O,$e,$),m&&l(m,$e,$)};return H(k),i(),{o:i,v(){H(ae),r.transform=""}}},Ht=(e,t)=>{const{config:s,u:o}=e,n=V(L),a=((p,c)=>{let r;const m=se(),i=c&&c!==m&&c!==D?c:null,b=()=>p.i.$();return i?r=i:(r=J(),Z(m,r)),{t:({preset:d,classname:$})=>(i&&d!=(i!==r)&&(d?(r=Ce(i,J()),i.id&&(r.id=i.id)):r=Ce(r,i)),k(r,U,b),i&&!d||(r.className=ge(Ze,mt,N($)?$:"")),r),v(){i?(ae(i,U,b),i!==r&&Ce(r,i)):de(r)}}})(e,pe(t)[0]),y=(({u:p})=>{let c,r,m,i;const b=V(it),d=V(rt,b),$={s:0,l:0},H=(u,[g,w]=[0,0])=>{let A,_,[I,K,B,F]=m;u?(c=u.clientX-I,r=u.clientY-K):(c+=g*B/100,r+=w*F/100),c=ee(c,B),r=ee(r,F),Le(b,c,r),A=1-r/F,_=A*(1-c/(2*B)),$.s=_===1||_===0?0:(A-_)/Q(_,1-_)*100,$.l=100*_,p.S($)},l=u=>{u.buttons?H(u):C(!1)},v=()=>{p.C(),C(!1)},S=()=>p.C(),C=u=>{ke(D,bt,u),(u?k:ae)(O,"pointermove",l),(u?k:ae)(window,De,S)};return k(d,$e,u=>{i||(p.k(),m=T(d),H(u),C(!0),k(O,"pointerup",v,{once:!0}))}),k(d,_e,u=>{const g=nt[u.key];g&&(u.preventDefault(),m=T(d),p.k(),H(null,g),p.C())}),{t:({i18n:u,disabled:g})=>(ne(d,le,u.palette),ne(d,"tabindex",g?"":0),i=g,d),A(u,g){u=(g/=100)+u/100*Q(g,1-g),m=T(d),c=(u?2*(1-g/u):0)*m[2],r=(1-u)*m[3],Le(b,c,r)}}})(e),h=(({u:p,H:c})=>{let r,m;const i=Xe(ht,360);return k(i,P,()=>p.S({h:+i.value})),{t:({opacity:b,i18n:{sliders:d}})=>(r=null,b?(r=Xe(pt,1,.01),k(r,P,()=>p.S({a:+r.value}))):p.L.a=1,ne(i,le,d.hue),ne(r,le,d.alpha),m=V("",i,r),k(m,W,()=>c.M(W)),m),O(b,d){i.value=b+"",r&&(r.value=d+"")}}})(e),f=(p=>{let c,r,m,i,b,d,{config:$,u:H}=p,l=[],v=!1;const S=()=>{let g={},w=l[i];v||(H.k(),v=!0),G(b,(A,_)=>g[A]=_.value),H.p(d?g[w]:q(g,w),!0)},C=()=>{if(r){b={},ue(r,""),d=l[i]==="hex"||$.singleInput;const g=l[i],w=d?[g]:(g+($.opacity?"a":"")).split(""),A=H.L;Z(r,...w.map(_=>(b[_]=R(P,ut,[],"",{type:"text",value:A[_]}),R("label","",[b[_],R("span","",[],_)]))))}},u=()=>{i=(i+1)%l.length,H.V(l[i]),C()};return{t({inputs:g,format:w,i18n:A}){c=r=m=null,l=Pe,g!==!0&&(g=g||{},l=l.filter(I=>g[I]));const _=l.length;return _||(l=Pe),i=ve(l.indexOf(w),0),H.V(l[i]),_&&(_>1&&(m=J(A.buttons.changeFormat,"",'<svg width="15" height="15" viewBox="0 0 20 20" aria-role="none"><path d="M10 1L5 8h10l-5-7zm0 18l5-7H5l5 7z"></path></svg>'),k(m,U,u)),r=V(gt),c=V(Ne,r,m),k(r,P,S),k(r,W,()=>{H.C(),v=!1}),k(r,"focusin",I=>I.target.select()),k(r,_e,I=>I.key==="Enter"&&p.i.$(!1)),C()),c},O(g){v||G(b||{},(w,A)=>A.value=g[w]+"")}}})(e),M=[y,{t:p=>V(Ne,...[xt(e),h].map(c=>c.t(p)))},f,St(e)];let E=!1,x=null;return o.B(n,y,h,f),{I(p){p=p||{};const c=this,{id:r,color:m}=p,{theme:i,parent:b,toggle:d,popover:$,target:H,disabled:l}=he(s,p),v=a.t(s),S=pe(b)[0],C=pe(H)[0];o.F(v),de(n),ue(n,""),Z(n,...M.map(u=>u.t(s))),N(r)&&(n.id=r),Y(n.dataset,{theme:i,display:$?"popover":"block"}),v.style.display=$||d?"":"none",x&&(x.v(),x=null),$?(Z(S||se(),n),x=Lt(C||v,n,v,s,c)):C||S?Z(C||S,n):v.after(n),d||c.$(!0,!0),[v,...qe(n)].forEach(u=>u.disabled=!!l),l&&($?c.$(!1,!0):d||c.$(!0,!0)),X(m)&&o.p(m),o.S({},!1,!0,!0)},$(p=!E,c=!1){p===E||s.disabled&&(!c||p&&s.popover)||!s.toggle&&!c||(p&&x&&x.o(),E=p,ke(n,vt,p),e.H.M(E?Fe:Re))},m:()=>E,o(){x&&x.o()},v(){de(n),x&&x.v(),a.v()}}},fe=e=>(e<16?"0":"")+e.toString(16),Me=(e,t,s)=>(e%=12,z(255*(s-t*Q(s,1-s)*ve(-1,Q(e-3,9-e,1))))),At=e=>{const t={h:0,s:0,l:0,r:0,g:0,b:0,a:1,rgb:"",hsl:"",hex:""},s=e.config,o=e.H.M;let n,a,y,h,f,M,E;return{L:t,_:()=>t[M],V(x){M=s.format=x},F(x){n=x},B(x,p,c,r){a=x,y=p,h=c,f=r},S(x,p=!0,c,r){const m=t.hex;Y(t,x),!c&&Y(t,(({h:i,s:b,l:d})=>({r:Me(i/=30,b/=100,d/=100),g:Me(i+8,b,d),b:Me(i+4,b,d)}))(t)),t.s=z(t.s),t.l=z(t.l),t.rgb=q(t),t.hsl=q(t,ie),t.hex=(({r:i,g:b,b:d,a:$})=>"#"+fe(i)+fe(b)+fe(d)+($<1?fe(z(255*$)):""))(t),oe(n,te,t.rgb),oe(a,j,`${t.r},${t.g},${t.b}`),oe(a,"a",t.a),oe(a,"h",t.h),f.O(t),r&&(h.O(t.h,t.a),y.A(t.s,t.l)),p&&m!==t.hex&&o(te,t)},p(x,p=!1,c){const[r,m]=Ae(x),i=m===j;s.opacity||(r.a=1),t[m]!==q(r,m)&&(Y(t,r,i?(({r:b,g:d,b:$,a:H})=>{const l=ve(b/=255,d/=255,$/=255),v=Q(b,d,$),S=l-v,C=(l+v)/2;return{h:Be(60*(S===0?0:l===b?(d-$)/S%6:l===d?($-b)/S+2:l===$?(b-d)/S+4:0)),s:S?S/(1-Te(2*C-1))*100:0,l:100*C,a:H}})(r):{}),this.S({},p,i,!0),c&&o(W,t))},k(){E=t[M]},C(){E!==t[M]&&o(W,t)}}};class Mt{static version(){return"2.1.1"}static setDefaults(t){he(Oe,t)}constructor(t,s){this.config=he({},Oe),this.H=(o=>{const n={[Fe]:[],[Re]:[],[W]:[],[te]:[]};return{M(a,y=o.u.L){(n[a]||[]).forEach(h=>h(Y({type:a,source:o},y)))},R(a,y){n[a]&&!n[a].includes(y)&&typeof y=="function"&&n[a].push(y)},T(a,y){X(a)?n[a]&&(X(y)?n[a]=n[a].filter(h=>h!==y):n[a]=[]):G(n,h=>{n[h]=[]})}}})(this),this.u=At(this),this.i=Ht(this,t),this.i.I(s)}setOptions(t){this.i.I(t)}setColor(t){return this.u.p(t),this}getColor(){return{...this.u.L}}isOpen(){return this.i.m()}open(){this.i.$(!0)}close(){this.i.$(!1)}toggle(){this.i.$()}on(t,s){this.H.R(t,s)}off(t,s){this.H.T(t,s)}addSwatches(...t){this.i.I({swatches:this.config.swatches.concat(t)})}removeSwatches(...t){this.i.I({swatches:this.config.swatches.filter((s,o)=>!t.some(n=>ce(n)?+n===o:n===s))})}enable(){this.i.I({disabled:!1})}disable(){this.i.I({disabled:!0})}reset(){this.u.p(this.config.default)}reposition(){this.i.o()}trigger(t){this.H.M(t)}destroy(){this.i.v(),G(this,t=>delete this[t]),$t(this,_t)}}export{Mt as default};
