/*********************************************************************************************
/**	* Form validation plugin
 	* @author	mvd@cafecentral.fr
**#******************************************************************************************/
(function($)
{	
//	Here we go!
	$.greenbutton = function(element, options)
	{
	//	Use "plugin" to reference the current instance of the object
		var plugin = this;
	//	this will hold the merged default, and user-provided options
		plugin.settings = {}
		var $element = $(element), // reference to the jQuery version of DOM element
		element = element;	// reference to the actual DOM element
		
	//	Plugin's variables
		var vars = {
		}

	//	The "constructor"
		plugin.init = function()
		{
		//	the plugin's final properties are the merged default and user-provided options (if any)
			plugin.settings = $.extend({}, vars, options);
	
		//	Load and change the labels
			$('#tabs li').click(function()
			{
			//	Some vars
				link = $(this).find('a');
				section = link.data('section');
			//	The different choices
				dflt = $('#section_'+section).data('greenbutton');

			//	If you have choices
				if (dflt)
				{
					label = dflt['title'][$('html').attr('lang')];
					$('#greenbutton-default')
						.html(label)
						.data('action', dflt['key']);
				}
			//	No choices
			//	else $('#greenbutton').hide();
				else console.log('No choices for green button, not good');
			});

		//	Trigger main action
			$(document).on('click', '#greenbutton-default', function()
			{
				$(this).toggleClass('on');
			});

		//	Show the minor actions
			$(document).on('click', '#greenbutton-or', function()
			{
				$(this).toggleClass('on');
			//	Fetch the right section
				sectionid = $('#adminContent section.active').attr('id').replace('section_', '');
			//	Open the context
				openContext(
				{
					app:'content',
					template:'master/snippet/greenbutton/greenbutton.context',
					sectionid:sectionid,
				});
			});

		//	A click a button triggers a method
			$(document).on('click', '#greenbutton-default, .greenbutton-choices a', function()
			{
				method = $(this).data('action');
			//	Create & execute the method
				var fn = plugin[method];
				fn();
			//	Save as the prefered method
				console.log('save '+method+' as prefered method !');
			});
			
		//	Prevent regular submit
			$('#adminContent>section').on('submit', 'form', function()
			{
				return false;
			});
		}
		
	//	New
		plugin.new = function()
		{
		//	Go to the form page
			document.location.href = ADMIN_URL+'/edit?item='+_GET['item'];
		}

	//	Save
		plugin.save = function(newStatus, callback)
		{
		//	Trigger regular submit for eventual plugin callbacks (like Sir Trevor)
			$('#adminContent section>form').submit();
		//	Id & status
			id = $('input[name="'+SITE_KEY+'_'+_GET['item']+'[id]"]');
			$oldStatus = $('input[name="'+SITE_KEY+'_'+_GET['item']+'[status]"]');
			$form = $('#adminContent section>form');
			
		//	Change status ?
			if (newStatus) 
			{
				$oldStatus.val(newStatus);
				status = newStatus;
			}
		//	Keep old status
			else if ($oldStatus.val()) status = $oldStatus.val();
		//	No status = draft
			else
			{
				$oldStatus.val('draft');
				status = 'draft';
			}

		//	Ajaxify forms
			$.ajax(
			{
				url: $form.attr('action'),
				type: $form.attr('method'),
				data: $form.serialize(),
				success: function(result)
				{
				//	DEBUG
					console.log(result);
				//	Ajax sends back the id
					if ($.isNumeric(result))
					{
					//	Bring it to the form
						id.val(result);
						$('#greenbutton-default').removeClass('on');
					//	Rewrite URL if changed
						if (!_GET['id'])
						{
							_GET['id'] = result;
							url = '?item='+_GET['item']+'&id='+_GET['id'];
							if (window.location.hash) url += window.location.hash;
							window.history.pushState('string', 'chose', url);
						}
					//	Pop alert
						popAlert(status, status, callback);
					};
				},
			});
		}

	//	Save and back
		plugin.save_back = function()
		{
			plugin.save(null, function()
			{
			//	Go to the list page
				document.location.href = ADMIN_URL+'/list?item='+_GET['item'];
			});
		}

	//	Save and reach
		plugin.save_reach = function()
		{
			plugin.save(null, function()
			{
			//	Reach
				document.location.href = CURRENTEDITED_URL;
			});
		}

	//	Go live
		plugin.live = function(callback)
		{
		//	Validate before go live
			$('#section_edit form').data('validate').now(
			{
				success:function(html)
				{
				//	Submit all forms	
					plugin.save('live', callback);
				},
				error:function()
				{			
				//	Pop alert
					popAlert('error', 'Couple of things missing.');
				//	console.log('error');
				},
				complete:function()
				{
				//	console.log('complete');
				},
			});
		}

	//	Go live and go back to the list
		plugin.live_back = function()
		{
			plugin.live(function()
			{
			//	Go to the list page
				document.location.href = ADMIN_URL+'/list?item='+_GET['item'];
			});
		}

	//	Go live and reach
		plugin.live_reach = function()
		{
			plugin.live(function()
			{
			//	Reach
				document.location.href = CURRENTEDITED_URL;
			});
		}

	//	Asleep
		plugin.asleep = function()
		{
		//	Submit all forms	
			plugin.save('asleep');
		}

	//	Asleep and go back to the list
		plugin.asleep_back = function()
		{
			plugin.save('asleep', function()
			{
			//	Go to the list page
				document.location.href = ADMIN_URL+'/list?item='+_GET['item'];
			});
		}

	//	Googlepreview
		plugin.googlepreview = function()
		{
		//	Save a draft
			plugin.save('draft', function()
			{
			//	Open preview
				openSite(CURRENTEDITED_URL);
			//	Open the context
				openContext(
				{
					app:'content',
					template:'master/snippet/googlepreview',
					item:'page',
					id:1,
				}, function()
				{	
					$site = $('#siteContent');
					$site.load(function()
					{
						title = $site.contents().find('title').html();
						descr = $site.contents().find('meta[name="description"]').attr('content');
						$preview = $('.adminContext[data-template="master/snippet/googlepreview"] .true');
						$preview.find('.title a').html(title);
						$preview.find('.descr a').html(descr);
					});
				});
			});
		}

	//	Save a copy
		plugin.save_copy = function()
		{
		//	Get rid of id and save
			$('input[name="'+SITE_KEY+'_'+_GET['item']+'[id]"]').val('');
			plugin.save();
		}

	//	Save and go back to the list
		plugin.save_back = function()
		{
			plugin.save(null, function()
			{
			//	Go to the list page
				document.location.href = ADMIN_URL+'/list?item='+_GET['item'];
			});
		}

	//	Save and start anew
		plugin.save_new = function()
		{
			plugin.live(function()
			{
			//	Go to the form page
				document.location.href = ADMIN_URL+'/edit?item='+_GET['item'];
			});
		}

	//	Trash
		plugin.trash = function()
		{
			plugin.save('trash');
		}

	//	Preview
		plugin.preview = function()
		{
			plugin.save('draft', function()
			{
				openSite(CURRENTEDITED_URL);
			});
		}

	//	Workflow
		plugin.workflow = function()
		{
			console.log('bon...');
		}

	//	New page
		plugin.newpage = function()
		{
			$('.lock').data('lock').unlock();
			$('#greenbutton-default').removeClass('on');
		}

	//	New workflow
		plugin.newworkflow = function()
		{
		//	Go to the form page
			document.location.href = ADMIN_URL+'/edit?item=workflow';
		}

	//	New system page
		plugin.newsystem = function()
		{
		//	Go to the form page
			document.location.href = ADMIN_URL+'/edit?item='+_GET['item']+'&fill[system]=1';
		}

	//	Fire up the plugin!
		plugin.init();
	}

//	Add the plugin to the jQuery.fn object
	$.fn.greenbutton = function(options)
	{
		return this.each(function()
		{
			if (undefined == $(this).data('greenbutton'))
			{
				var plugin = new $.greenbutton(this, options);
				$(this).data('greenbutton', plugin);
			}
		});
	}
})(jQuery);