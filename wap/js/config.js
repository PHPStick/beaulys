
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
//		var classobj = new Array(); //�������� 
//		var classint = 0; //����������±� 
//		if (typeof(TagName) == 'undefined') {
//			var tags = document.getElementsByTagName("*"); //��ȡHTML�����б�ǩ 
//		}else{
//			
//			var tags = document.getElementsByTagName(TagName); //��ȡHTML�����б�ǩ 
//		}
//		alert(tags.length)
//		for (var i in tags) { //�Ա�ǩ���б��� 
//			if (tags[i].nodeType == 1) {
//				if (tags[i].getAttribute(attrname) != null) {
//						classobj[classint] = tags[i];
//						console.log(classobj[classint])
//						classint++;
//				};
//			};
//		};
//		return classobj; //������ɵ����� 
//	};
//
//	var imgUrl=function(){
//		var a = getElementsObj('src', 'img');
//		console.log(a.length)
//	}
//	imgUrl();
}
