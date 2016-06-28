
parHost = 'dev.beaulys.com'
mbHost = 'm.dev.beaulys.com'

nowHost = window.location.host;
changHost=''

var SiteUrl = "http://" + parHost + "/shop"; //"http://v3.haoid.cn/mobile";
var ApiUrl = "http://" + parHost + "/mobile"; //"http://v3.haoid.cn/mobile";
var pagesize = 10;
var WapSiteUrl = "http://" + mbHost; //"http://v3.haoid.cn/wap";
var IOSSiteUrl = "http://" + parHost + "/app.ipa"; //"http://v3.haoid.cn/app.ipa";
var AndroidSiteUrl = "http://" + parHost + "/app.apk"; //"http://v3.haoid.cn/app.apk";
var WeiXinOauth = false;

if (nowHost == mbHost) {
//	changHost=parHost
//	var getElementsObj=function(attrname, TagName) {
//		var classobj = new Array(); //定义数组 
//		var classint = 0; //定义数组的下标 
//		if (typeof(TagName) == 'undefined') {
//			var tags = document.getElementsByTagName("*"); //获取HTML的所有标签 
//		}else{
//			
//			var tags = document.getElementsByTagName(TagName); //获取HTML的所有标签 
//		}
//		alert(tags.length)
//		for (var i in tags) { //对标签进行遍历 
//			if (tags[i].nodeType == 1) {
//				if (tags[i].getAttribute(attrname) != null) {
//						classobj[classint] = tags[i];
//						console.log(classobj[classint])
//						classint++;
//				};
//			};
//		};
//		return classobj; //返回组成的数组 
//	};
//
//	var imgUrl=function(){
//		var a = getElementsObj('src', 'img');
//		console.log(a.length)
//	}
//	imgUrl();
}
