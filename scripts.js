if(typeof String.prototype.trim!=='function'){String.prototype.trim=function(){return this.replace(/^\s+|\s+$/g,'');}}
function _$(e){var d=document;if(typeof e=='string'){if(d.getElementById){e=d.getElementById(e);}else if(d.all){e=d.all[name];}else if(d.layers){e=d.layers[name];}}return e;}
var isReady=false,whenReady=[];
function doReady(){if(isReady||!document.addEventListener&&document.readyState!=="complete"){return;}if(!document.body){return setTimeout(doReady,1);}isReady=true;for(var i=0;i<whenReady.length;i++){whenReady[i]();}}
function addOnReady(f){
 if (isReady){setTimeout(f,1);}
 if (whenReady.length==0){
  if(document.addEventListener){document.addEventListener("DOMContentLoaded",function(){document.removeEventListener("DOMContentLoaded",arguments.callee,false);doReady();},false);window.addEventListener("load",doReady,false);} 
  else if(document.attachEvent){
   document.attachEvent("onreadystatechange",function(){if(document.readyState==="complete"){document.detachEvent("onreadystatechange",arguments.callee);doReady();}});window.attachEvent("onload",doReady);
   var top=false;try{top=window.frameElement==null&&document.documentElement;}catch(e){}
   if (top&&top.doScroll)(function iescrlcheck(){if(isReady){return;}try{top.doScroll("left");}catch(e){return setTimeout(iescrlcheck,5);}doReady();})();
  } else if(window.onload){var o=window.onload;window.onload=function(){o();doReady();};}else{window.onload=doReady;}
 }
 whenReady.push(f);
}
function getMouseX(e){e=e?e:window.event;return e.pageX?e.pageX:(e.clientX?e.clientX+document.body.scrollLeft+document.documentElement.scrollLeft:0);}
function indexOf(a,o){for(var i=0;i<a.length;i++){if(a[i]==o){return i;}}return -1;}
function contains(a,o){return indexOf(a,o)>=0;}
function remove(a,o) {var i=indexOf(a,o);return(i>=0)?a.slice(i,1):a;}
function hasClass(x,c){return new RegExp('\\b'+c+'\\b','i').test(x.className);}
function addClass(x,c){x.className+=' '+c;}
function removeClass(x,c){x.className=x.className.replace(new RegExp('\\b'+c+'\\b','gi'),'').trim();}
function toggleClass(x,c){var b=hasClass(x,c);b?removeClass(x,c):addClass(x,c);return !b;}
function toggleHidden(n){n=_$(n);if(n){toggleClass(n,'hidden');}}
function toggleDisplay(n){n=_$(n);if(n){var d=n.style.display;n.style.display=(d&&d=='none')?'':'none';}}
function clamp(x,a,b){return(x<a?a:(x>b?b:x));}
function event_target(e){e=e?e:window.event;var t=e.target?e.target:e.srcElement;return(t.nodeType==3)?t.parentNode:t;}
function pageHeight(){var d=document;return window.innerHeight!=null?window.innerHeight:d.documentElement&&d.documentElement.clientHeight?d.documentElement.clientHeight:d.body!=null?d.body.clientHeight:null;}
function set_tag_visibility(n,h){var t=document.getElementsByTagName(n),i=t.length-1;for(;i>=0;i--)t[i].style.visibility=h;}
function toggle_objects(h){set_tag_visibility('SELECT',h);set_tag_visibility('IFRAME',h);set_tag_visibility('OBJECT',h);}
function for_all_with_tag_and_class(t,c,f) {var C=_$('content');if(!C){return false;}var es=C.getElementsByTagName(t),i,e,x=false;for(i=0,e;(e=es[i]);i++){if(hasClass(e,c)){f(e);x=true;}}return x;}

///// Preloading /////
var img=new Image();img.src='/images/black1.png';

///// Flash-Expander /////
var expandable,expander,expand_startWidth=0,expand_startPos=0,mouseIsUp=true,expand_heightAlso=false;
//set the initial position of the resize-bar
function expand_start(e){expand_startPos=getMouseX(e);expand_startWidth=parseInt(expandable.style.width.replace(/px/gi,''));mouseIsUp=false;e.preventDefault();}
//reset the position of the resize-bar when the mouse moves while button down
function create_expander(elem, expandHeightAlso){
  expandable = _$(elem);
  expandable.className += ' expandable';
  expandable.expandHeightAlso = expandHeightAlso;
  expander = document.createElement('div');
  expander.className = 'expander clearfix';
  expander.style.height = parseInt(expandable.style.height.replace(/px/gi,'')) + 'px';
  expander.onmousedown = expand_start;
  expandable.parentNode.insertBefore(expander, expandable.nextSibling);
  var clr = document.createElement('br');
  clr.style.clear = 'both';
  expander.parentNode.insertBefore(clr, expander.nextSibling);
}
document.onmousemove=function expand_update(e){if(!mouseIsUp){
  var newWidth = expand_startWidth + getMouseX(e) - expand_startPos;
  newWidth = clamp(newWidth,15,725)+'px';
  expandable.style.width = newWidth;
  if (expandable.expandHeightAlso) { expandable.style.height = expander.style.height = newWidth; }
  e.preventDefault();
}};
document.onmouseup=function(e){if(!mouseIsUp){e.preventDefault();}mouseIsUp=true;};
addOnReady(function(){for_all_with_tag_and_class('OBJECT','expandable',function(o){create_expander(o.parentNode,hasClass(o,'exp-both'))});});

///// Zoom Images /////
var close_keys = [8, 13, 27, 32, 88, 120];
function zoom_hide(){var o=_$('overlay');o.style.display='none';o.firstChild.nextSibling.src=null;toggle_objects('visible');document.onkeypress=document.onclick=null;}
function zoom_key_press(e){e=e?e:window.event;var k=e.keyCode?e.keyCode:e.which;if(contains(close_keys,k)){zoom_hide();return false;}}
function zoom(i,c){var o=_$('overlay'),O=o.childNodes[1],s=i.getAttribute('full');O.firstChild.src=s?s:i.src;O.lastChild.textContent=c;o.style.display='';document.onkeypress=zoom_key_press;setTimeout(function(){document.onclick=zoom_hide}, 0);}
addOnReady(function(){
  if (for_all_with_tag_and_class('IMG','zoom',function(i){
    var w=document.createElement('span'),caption=i.title;
    w.title='Click to enlarge'; i.title='';
    w.className=i.className; i.className='';
    i.parentNode.insertBefore(w, i);
    w.appendChild(i);
    w.onclick=function(e){zoom(i,caption);};
  })){
    var d=document,o=d.createElement('div'),O=d.createElement('span'),b=d.getElementsByTagName('BODY')[0];
    o.setAttribute('id','overlay');
    o.appendChild(d.createTextNode('\u00a0'));
    o.appendChild(O);
    o.appendChild(d.createTextNode('\u00a0'));
    O.appendChild(d.createElement('img'));
    O.appendChild(d.createElement('div'));
    b.insertBefore(o, b.firstChild);
    o.style.display = 'none';
    o.style.lineHeight = (pageHeight()-1)+'px';
    window.onresize = function() { o.style.lineHeight = (pageHeight()-1)+'px'; }
  }
});

///// Projects Page Toggling /////
var best_on=true;
function toggleType(x) {
  var t = x.id.substr(4), on = !toggleClass(x, 'inactive'), bb = _$('browseboxes'), a = bb.firstChild;
  var re = new RegExp('\\b'+t+'\\b','i');
  while (a && a.tagName=='A') {
    if (on&&a.inactive&&contains(a.inactive,t)) {
      remove(a.inactive, t);
      a.name += ' '+t;
      if (!best_on || a.rev=='best') a.style.display = '';
    } else if (!on&&a.name.match(t)) {
      if (!a.inactive) { a.inactive = []; }
      a.inactive.push(t);
      a.name = a.name.replace(re,'').trim();
      if (!a.name) { a.style.display = 'none'; }
    }
    a = a.nextSibling;
  }
}
function toggleBest(x) {
  var bb = _$('browseboxes'), a = bb.firstChild;
  best_on = toggleClass(x, 'inactive');
  while (a && a.tagName=='A') {
    if (best_on&&a.rev!='best') { a.style.display = 'none'; }
    else if (!best_on&&a.name) { a.style.display = ''; }
    a = a.nextSibling;
  }
}
addOnReady(function(){var p=_$('projsel');if(p)p.style.display='';});


///// Table of Contents /////
function addNamedLink(link,e){var a=document.createElement('A');e.insertBefore(a,e.firstChild).name=link;if(link==window.location.hash.substring(1)){window.location.hash='#';window.location.hash='#'+link;}return 1;}
function createRefLink(href){var a=document.createElement('A');a.className='ref';a.href='#'+href;return a;}
function openToc(){_$('toc').className='open';}
function closeToc(){_$('toc').className='';}
addOnReady(function makeToc() {
  var list = _$('toc'), all = _$('content').getElementsByTagName('*');
  var reH = /^H[1-3]$/, reX = /[^A-Za-z_0-9]/g;
  var prevLvl = 0, l;
  for (var i = 0, e; (e = all[i]); i++) {
    if (e.tagName.match(reH) && !hasClass(e,'subtitle')) {
      var lvl = parseInt(e.tagName.substr(1), 10), text = e.innerText ? e.innerText : e.textContent, link, prev = e.previousSibling;

      if (e.name) { i+=addNamedLink(link = e.name, e); }
      else if (prev && prev.tagName == 'A' && prev.name) { link = prev.name; }
      else { i+=addNamedLink(link = text.replace(reX, ''), e); }

      if (lvl > prevLvl) {      for (l = prevLvl; l < lvl; l++) { list = list.appendChild(document.createElement('UL')); } }
      else if (lvl < prevLvl) { for (l = lvl; l < prevLvl; l++) { list = list.parentNode; } }
      prevLvl = lvl;

      e.appendChild(document.createTextNode(' '));
      e.appendChild(createRefLink(link)).appendChild(document.createTextNode('\xB6'));
      list.appendChild(document.createElement('LI')).appendChild(createRefLink(link)).appendChild(document.createTextNode(text));
    }
  }
});
