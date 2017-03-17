var Page = {
	xhr: {},
	redirect: function(url){
		window.location = url;
	},
	ajaxCall: function(url, dataStr, success){
		var _s = this;
		$.ajaxSetup({
			timeout: 10000,
			success: success,
			error: function(xhr, status){
				console.log('Could not load data. Error: '+status+'.');
			},
			cache: false,
			method: "post",
			dataType: "json",
		});
		_s.xhr = $.ajax({
			url : "ajax/"+url+".php",
			data: dataStr,
		});
	},
	templateCall: function(obj){
		var source = obj.html,
			template = Handlebars.compile(source),
			html = template(obj.data);
		return html;
	},
};