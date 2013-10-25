/*********************************************************************************************
/**	* Form validation plugin
 	* @author	mvd@cafecentral.fr
**#******************************************************************************************/
(function($)
{	
//	Here we go!
	$.infinitescroll = function(element, options, callbacks)
	{
	//	Use "plugin" to reference the current instance of the object
		var plugin = this;
	//	this will hold the merged default, and user-provided options
		plugin.settings = {}
		var $element = $(element), // reference to the jQuery version of DOM element
		element = element;	// reference to the actual DOM element
		
	//	Plugin's variables
		var vars = {
			round:0,
			range:options.limit,
			rangeFrom:'0',
			rangeTo:options.limit,
		}

	//	The "constructor"
		plugin.init = function()
		{
		//	the plugin's final properties are the merged default and user-provided options (if any)
			plugin.settings = $.extend({}, vars, options);
			
		//	Load more content
			$('.infiniteScrollWantsMore').on('click', function()
			{
				plugin.load();
			});
		
		//	Load the first content
			plugin.load();
		}
		
	//	Load content
		plugin.load = function()
		{
		//	Change range
			plugin.settings.rangeFrom = plugin.settings.range * plugin.settings.round;
			plugin.settings.rangeTo = plugin.settings.rangeFrom + plugin.settings.range;
			plugin.settings.limit = plugin.settings.rangeFrom+', '+plugin.settings.rangeTo;

		//	Load
			$.ajx(
				plugin.settings,
				{
					done:function(html)
					{
						$element.append(html);
					//	Execute callback (make sure the callback is a function)
						if ((typeof(callbacks) != 'undefined') && (typeof(callbacks) == "function")) callbacks.call(this, html);
					}
				},
				{mime:'html'}
			);
			
		//	Increment round of loading
			plugin.settings.round++;
		}

	//	Fire up the plugin!
		plugin.init();
	}

//	Add the plugin to the jQuery.fn object
	$.fn.infinitescroll = function(options, callbacks)
	{
		return this.each(function()
		{
			if (undefined == $(this).data('infinitescroll'))
			{
				var plugin = new $.infinitescroll(this, options, callbacks);
				$(this).data('infinitescroll', plugin);
			}
		});
	}
})(jQuery);