

(function( $ ) {
	$.fn.cookiechecker = function(options) {

		//maintain chainability
		return this.each(function() {

			// Create some defaults, extending them with any options that were provided
			var settings = $.extend( {
				"notice" : "Like virtually all websites we use cookies. These are required for the site to work properly. If you continue we'll assume you are happy to use cookies on our site.",
				"policy" : "Click here to read our privacy statement.",
				"policyurl" : "/privacy/",
				"accept" : "Click here to remove this notice.",
				"theme" : "black",
				"position" : "bottom"
			}, options);

			var notice = settings.notice;
			var policy = settings.policy;
			var policyurl = settings.policyurl;
			var accept = settings.accept;
			var theme = settings.theme;
			var position = settings.position;

			//check for the cookie
			function readcookie(name) {
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				for(var i=0;i < ca.length;i++) {
					var c = ca[i];
					while (c.charAt(0)==' ') c = c.substring(1,c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
				}
				return null;
			}

			if (readcookie('zigcookie') == '1') {
				// ok, cookie is present
			} else {
				var out;
				out  = '<div id="zigcookie">';
				out += '<div class="outer ' + theme + ' ' + position + '">';
				out += '<div class="inner">';
				out += '<p>' + notice + '</p>';
				out += '<p>';
				out += ' <a href="' + policyurl+ '">' + policy + '</a> ';
				out += ' <span id="zigcookieaccept">' + accept + '</span> ';
				out += '</p>';
				out += '</div><!--/.inner-->';
				out += '</div><!--/.outer-->';
				out += '</div><!--/#zigcookie-->';
				$('body').prepend($(out));
				$('#zigcookie').fadeIn('slow', function(){
					$('#zigcookieaccept').on('click', function(){
						// create one year duration cookie
						var theDate = new Date();
						var oneYearLater = new Date(theDate.getTime() + (31536000000));
						var expiryDate = oneYearLater.toGMTString();
						document.cookie = 'zigcookie=1;expires=' + expiryDate + ';path=/';
						$('#zigcookie').fadeOut('slow', function(){
							$('#zigcookie').empty().remove();
						});
					});
				});
			}; // if

		// end chainability
		});

	// end cookiechecker
	}

// end plugin
})( jQuery );


// EOF
