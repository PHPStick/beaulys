
parHost = 'dev.beaulys.com'
mbHost = 'm.dev.beaulys.com'

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
