function loadExt(e,t){var s=this;s.files=e,s.js=[],s.head=document.getElementsByTagName("head")[0],s.after=t||function(){},s.loadStyle=function(e){console.log("Loading CSS: "+e);var t=document.createElement("link");t.rel="stylesheet",t.type="text/css",t.href=e,s.head.appendChild(t)},s.loadScript=function(e){var t=s.js[e].replace("&#038;","&");console.log("Loading JS: "+t);var l=document.createElement("script");l.type="text/javascript",l.src=t;l.onload=function(){++e<s.js.length?s.loadScript(e):s.after()},s.head.appendChild(l)};for(var l=0;l<s.files.length;l++)/\.js$|\.js\?|\.js\#/.test(s.files[l])&&s.js.push(s.files[l]),/\.css$|\.css\?|\.css\#/.test(s.files[l])&&s.loadStyle(s.files[l]);0<s.js.length?s.loadScript(0):s.after()}
