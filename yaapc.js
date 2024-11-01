function yaapc_replace(data)
{
	var response = jQuery("<div></div>").html(data);
	if (response.find("#yaapc-comments")[0])
	{
		jQuery(".yaapc-pagenav a").unbind('click');	
		jQuery("#yaapc-comments").replaceWith(response.find("#yaapc-comments")[0]);
		jQuery(".yaapc-pagenav a").bind('click', yaapc_load_comments);
	}
	else
	{
		var selector = '.commentlist,#commentlist';
		if (response.find(selector)[0])
		{
			if (jQuery(selector)[0])
			{
				jQuery(selector).replaceWith(response.find(selector)[0]);
			}
			else
			{
				jQuery("#respond").before(response.find(selector)[0]);
			}

		}
		if (response.find(".yaapc-pagenav")[0])
		{
			jQuery(".yaapc-pagenav a").unbind('click');			
			if (jQuery(".yaapc-pagenav")[0])
			{
				jQuery(".yaapc-pagenav").replaceWith(response.find(".yaapc-pagenav")[0]);
			}
			else
			{
				jQuery(selector).before(response.find(".yaapc-pagenav")[0]);
				jQuery(selector).after(response.find(".yaapc-pagenav")[0]);
			}

			jQuery(".yaapc-pagenav a").bind('click', yaapc_load_comments);
		}
		if (response.find("#comments")[0])
		{
			if (jQuery("#comments")[0])
			{
				jQuery("#comments").replaceWith(response.find("#comments")[0]);
			}
		}
	}
}

function yaapc_load_comments()
{
	var url = jQuery(this).attr('href');
	var tmp = url.indexOf('#');
	var hasq = url.indexOf('?');
	var param = hasq ? '&yaapc=1' : '?yaapc=1';
	var newurl;
	if (tmp != -1)
	{
		newurl = url.substring(0, tmp) + param + url.substring(tmp);
	}
	else
	{
		newurl = url + param;
	}

	jQuery(".commentlist,#commentlist").html('<div id="yaapc-comments-loading"></div>');
	jQuery.ajax({
		type: "GET",
		url : newurl,
		dataType: 'html',
		success: function(data){
			yaapc_replace(data);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown){
			jQuery(".commentlist,#commentlist").replaceWith("Failed to get comments:" + textStatus);
		}
	});				

	return false;
}

function yaapc_init_comment_form(){
	var form = jQuery('#commentform');
	form.before('<div id="yaapc_message"></div>');
	var msg = jQuery('#yaapc_message');
	form.ajaxForm(
	{
		beforeSubmit: function() {
			if(form.find('#author')[0]) {
				if(form.find('#author').val() == '') {
					msg.html('<div class="notice">'+'Please enter your name.'+'</div>');
					return false;
				} 
				var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				if(!filter.test(form.find('#email').val())) {
					msg.html('<div class="notice">'+'Please enter a valid email address.'+'</div>');
					return false;
				} 
			} 

			if(form.find('#comment').val() == '') {
				msg.html('<div class="notice">'+'Please enter your comment.' +'</div>');
				return false;
			} 
			this.url = this.url + "?yaapc=1";
			msg.html('<div id="yaapc-comments-loading"></div>');
			form.hide();
			jQuery('#submit').attr('disabled','disabled');
		}, 
		error: function(request){
			msg.empty();
			form.show();
			if (request.responseText.search(/<title>WordPress &rsaquo; Error<\/title>/) != -1) {
				var data = request.responseText.match(/<p>(.*)<\/p>/);
				msg.html('<div class="error">'+ data[1] +'</div>');
			} else {
				var data = request.responseText;
				msg.html('<div class="error">'+ data[1] +'</div>');
			}
			jQuery('#submit').removeAttr("disabled");
			return false;
		}, 	
		success: function(data, status) {
			try {
				msg.html('<div class="success">Your comment has been added.</div>');
				yaapc_replace(data);
				form.fadeIn(1500);
				jQuery('#submit').removeAttr("disabled");
				jQuery('#comment').val('');
			} catch (e) {
				msg.empty();
				form.fadeIn(1500);
				jQuery('#submit').removeAttr("disabled");
				var errorinfo = 'Unknown error';
				if ( e.message )
				{
					errorinfo = e.message;
				}
				else if ( typeof e == 'string' || typeof e == 'String' )
				{
					errorinfo = e;
				}
				alert('error:' + errorinfo);
			}
		} 
	}); 
}

jQuery(function($) {
	$(".yaapc-pagenav > a").bind('click', yaapc_load_comments);
	yaapc_init_comment_form();
});
