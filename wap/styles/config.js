(function() {
	var headEl = document.getElementsByTagName('head')[0];
	//模块加载参数
	if(typeof(window.hostType) == "undefined") {
		window.hostType = 0;
	}
	//路径前缀
	if(typeof(window.host) == "undefined") {
		window.host = '';
	}
	//缓存变量
	if(typeof(window.versionsNum) == "undefined") {
		window.versionsNum = '?001';
	}
	var css = {
		0: [
			'css/mui.css',
			'styles/css/mbpublic.css',
			
		],
		1: ['']
	};
	var js = {
		0: [
			'styles/js/jquery1.11.0.min.js',
			'styles/js/mui.js',
			'styles/js/public.js'
		],
		1: ['']
	};

	for(var i = 0, j = css[window.hostType]; i < j.length; i++) {
		addTag('link', {
			rel: 'stylesheet',
			href: window.host + j[i] + window.versionsNum
		});
	}
	for(var q = 0, w = js[window.hostType]; q < w.length; q++) {
		addTag('script', {
			src: window.host + w[q] + window.versionsNum
		}, true);
	}

	function addTag(name, attributes, sync) {
		var el = document.createElement(name),
			attrName;

		for(attrName in attributes) {
			el.setAttribute(attrName, attributes[attrName]);
		}
		sync ? document.write(outerHTML(el)) : headEl.appendChild(el);
	}

	function outerHTML(node) {
		// if IE, Chrome take the internal method otherwise build one
		return node.outerHTML || (function(n) {
			var div = document.createElement('div'),
				h;
			div.appendChild(n);
			h = div.innerHTML;
			div = null;
			return h;
		})(node);
	}

})();
