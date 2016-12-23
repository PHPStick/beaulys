
parHost = 'www.beaulys.com'
mbHost = 'm.beaulys.com'

httpParHost="http://"+parHost
var SiteUrl = "http://" + parHost + "/shop"; //"http://v3.haoid.cn/mobile";
var ApiUrl = "http://" + parHost + "/mobile"; //"http://v3.haoid.cn/mobile";
var pagesize = 10;
var WapSiteUrl = "http://" + mbHost; //"http://v3.haoid.cn/wap";
var IOSSiteUrl = "http://" + parHost + "/app.ipa"; //"http://v3.haoid.cn/app.ipa";
var AndroidSiteUrl = "http://" + parHost + "/app.apk"; //"http://v3.haoid.cn/app.apk";
var WeiXinOauth = false;

function changHostReturn(){
	return changHost
}
function filterUrl(Furl){
	if(typeof Furl== "string" && Furl.indexOf('/')==0){
		Furl='http://'+parHost+Furl;
	}
	return Furl;
}
for (var i = 0; i < document.getElementsByTagName("meta").length; i++) {
	if(document.getElementsByTagName("meta")[i].getAttribute('name')=='viewport'){
		document.getElementsByTagName("meta")[i].setAttribute('content','initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no')
	}
	
}

(function(){
    var bp = document.createElement('script');
    var curProtocol = window.location.protocol.split(':')[0];
    if (curProtocol === 'https') {
        bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';        
    }
    else {
        bp.src = 'http://push.zhanzhang.baidu.com/push.js';
    }
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(bp, s);
})();