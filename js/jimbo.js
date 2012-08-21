var Jimbo = {
	growlCreate: growl_create,
	updateGrowls: growl_update,
	growlTimer: growl_timer,

	responseIframe: setIframeResponse,
	response: jsonResponse,
	showLoadingBar: system_showLoadingBar,
	hideLoadingBar: system_hideLoadingBar
};


function jsonResponse(data)
{
	if (data['eval'] != undefined) {
		eval(data['eval']);
	}

	if (data['notifications'] != undefined) {
		  jQuery.each(data['notifications'], function(index, msg) {
			  Jimbo.growlCreate('Уведомление', msg, false);
		  });
    }

	switch(data['type']) {
		case 'error':
			dbaUpdateError(data);
			break;

		case 'success':
			dbaUpdateSuccess(data);
			break;

		case 'redirect':
			if (data['url'] != undefined) {
				setTimeout("document.location.replace('"+data['url']+"')", 1200);
			}
			break;

		case 'alert':
			var messages = !data['message'] ? data['messages'] : data['message'];
			showMessages(data['title'], messages);
			if(data['url'] != undefined) {
				setTimeout("document.location.replace('"+data['url']+"')", 1200);
			}
			break;
	}

	return true;
} // end jsonResponse

function dbaUpdateError(data)
{
	if(typeof(jimbo) == undefined || jimbo.mode != 'jquery') {
		/*text = $('<textarea class="errorlog" style="margin:0px;" readonly="readonly"></textarea>');
		$('#form_actions>td').append(text);*/
	}

	$('.errorlog').html(data['message']);
} // end dbaUpdateError

function dbaUpdateSuccess(data)
{
	if(typeof(jimbo) != undefined && jimbo.mode == 'jquery') {
		$('.ui-dialog-buttonpane').html('<p style="color: green;"><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em; "></span>'+data['message']+'</p>');
		if(data['url'] != undefined) {
			setTimeout("document.location.replace('"+data['url']+"')", 1200);
		}
	} else {
		$('#form_actions>td').html('<div class="success_messages">'+data['message']+'</div>');

		if(data['isPoupMode'] == 1) {
			if (typeof(parent.window.opener) != "undefined") {
				parent.window.opener.location.reload(false);
			}

			if (typeof(dialogArguments) != "undefined") {
				dialogArguments.window.location.reload(false);
			}

			setTimeout("parent.close();", 1200);
		} else {
			// for compatibility with the old versions
			setTimeout("document.location.replace('"+data['url']+"')", 1200);
		}
	}
} // end dbaUpdateSuccess

function setIframeResponse(jsonStr)
{
	hideLoadingBar();
	var data = eval('(' + jsonStr + ')');
	jsonResponse(data);
} // end setIframeResponse

function showMessages(title, messages, height, width)
{
	widthDialog = width == undefined ? 400 : width;
	heightDialog = height == undefined ? 280 : height;

	obj = $("#dialog-message");
	if(obj.length == 0) {
		$('body').append('<div id="dialog-message"></div>');
	}
	obj = $("#dialog-message");

	$(obj).attr('title', title);
	// FIXME: add template
	$(obj).html(messages);

	$(obj).dialog({
		modal: true,
		width: widthDialog,
		height:heightDialog,
		buttons: {
			Ok: function() {
				$(this).dialog('close');
			}
		}
	});
} // end showMessages

function openJqueryPopup(id)
{
	obj = $("#" + id);

	$(obj).css('padding', '0px');

	title = $(obj).find('.jform>thead>tr>td>b').html();

	// buttons
	buttons = {};
	submitCaption = false;
	cancelCaption = false;
	$(obj).find('#form_actions>td>div>input').each(function() {
		if($(this).attr('type') == 'submit') {
			submitCaption = $(this).val();
		} else {
			cancelCaption = $(this).val();
		}
	});

	if(submitCaption) {
		buttons.submit = function() {
			$(obj).find('form').submit();
        };
	}

	if(cancelCaption) {
		buttons.cancel = function() {
            $(this).dialog('destroy');
            $('#' + id).remove();
        };
	}

	$(obj).find('.jform>thead').remove();
	$(obj).find('#form_actions').remove();
	$(obj).find('.formRows').removeClass('formRows');

	widthDelta = 10;
	objHeight = $(obj).outerHeight() + 10;
	height = objHeight + 100;
	viewHeight = $.getViewHeight() - 50;

	if(viewHeight < height) {
		height = viewHeight;
		widthDelta += 50;
	}

	if(jimbo.dialogWidth == undefined) {
		jimbo.dialogWidth = 640;
	}

	obj.dialog({
		modal: true,
        resizable: true,
        width: jimbo.dialogWidth + widthDelta,
        height:height,
        title: title,
        buttons:buttons,
        closeOnEscape: false,
        open: function(event, ui) {
        	$(".ui-dialog-titlebar-close").hide();
        	//$("#" + id).height(objHeight);
        }
    });

	$('.ui-dialog-buttonpane>.ui-dialog-buttonset>button').each(function() {
		caption = $(this).find('span').html();
		value =  caption == 'submit' ? submitCaption : cancelCaption;
		$(this).find('span').html(value);
	});

	text = $('<textarea class="errorlog" readonly="readonly"></textarea>');

	$('.ui-dialog-buttonpane').append(text);

} // end openJqueryPopup

function openWindow(uri)
{
	mode = (jimbo.mode == undefined) ? 'popup' : jimbo.mode;

	switch(mode) {
		case 'jquery':
			obj = $("#loader");
			if(obj.length == 0) {
				$('body').append('<div id="loader"></div>');
			}
			obj = $("#loader");

			showLoadingBar();

			$("#loader").load(uri, function() {
				hideLoadingBar();
				openJqueryPopup('dba_form');
			});
			break;

		default:
			openPopup(uri);
	}
} // end openWindow

function openPopup(uri)
{
	defaultWidth = 650;
	defaultHeight = 575;

	posY = (screen.height - defaultHeight) / 2;
	posX = (screen.width - defaultWidth) / 2;

	if (navigator.appName == 'Microsoft Internet Explorer') {
		window.showModelessDialog(uri, window, "dialogWidth=" + defaultWidth + "px;dialogHeight=" + defaultHeight + "px;left=" + posX + "px;top=" +posY + "px;resizable=no;scroll=no;status=no;toolbar=no;menubar=no;location=no;titlebar=no;directories=no");
	} else {
		window.open(uri, '', "width=" + defaultWidth + ",height=" + defaultHeight +",left=" + posX + "px,top=" +posY + "px,status=no,toolbar=no,menubar=no,location=no,titlebar=no,resizable=yes,directories=no,scroll=no");
    }
}

function initPopup(custome)
{
	$("#tblform>tbody>tr:nth-child(odd)").addClass("ka_odd");

	if(custome == undefined) {
		mode = (jimbo.mode == undefined) ? 'popup' : jimbo.mode;
	} else {
		mode = custome;
	}

	if(mode != 'popup') {
		return false;
	}

	height = $('.jform').outerHeight();

	if(window.dialogHeight == undefined) {

		deltaHeight = window.outerHeight - window.innerHeight;
		realHeight = height + deltaHeight;
		deltaWidth = 0;

		if(realHeight >= screen.height) {
			realHeight = screen.height - deltaHeight - 50;
			deltaWidth += 20;
		}

		window.resizeTo(window.outerWidth + deltaWidth, realHeight);

		if(deltaWidth > 0) {
			// TODO: Fix height in Safari
			viewHeight = getViewHeight() - $('#form_actions>td').outerHeight()- $('.caption').outerHeight();
			$('.formRows').css('height', viewHeight + 'px');
		}

		posY = (screen.height - window.outerHeight) / 2;
		posX = (screen.width - window.outerWidth) / 2;

		window.moveTo(posX, posY);
	} else {
		// for IE
		height += $('#form_actions>td').outerHeight() + 5;
		window.dialogHeight = height + 'px';
	    realHeight = getViewHeight() + 120;

	    if(realHeight >= screen.height) {
	    	viewHeight = getViewHeight() - $('#form_actions>td').outerHeight()- $('.caption').outerHeight();
			$('.formRows').css('height', viewHeight + 'px');
			width = parseInt(window.dialogWidth) + 20;
			window.dialogWidth = width + 'px';
	    }

	    posY = (screen.height - parseInt(window.dialogHeight)) / 2;
		posX = (screen.width - parseInt(window.dialogWidth)) / 2;

	    window.dialogTop = posY + 'px';
	    window.dialogLeft = posX + 'px';
	}

	$('.jform').css('height', '100%');

} // end initPopup

function dbaListActions(select)
{
	option = select.options[select.selectedIndex];
    if(option == undefined) {
        return false;
    }

    if(option.getAttribute('popup') == 1) {
        openWindow(option.value + "&popup=true");
    }  else {
        window.location = option.value;
    }

    select.selectedIndex = 0;
} // end dbaListActions

function loadContent(url, id, callback)
{
	id = (id == undefined) ? 'loader' : id;

	obj = $("#" + id);
	if(obj.length == 0) {
		$('body').append('<div id="'+id+'"></div>');
	}
	obj = $("#" + id);

	obj.load(url, function() {
		if (callback) {
			callback(data);
		}
	});
} // end loadContent

$.getViewHeight = function() {
	 var viewportheight;

	 if (typeof window.innerWidth != 'undefined') {
		 viewportheight = window.innerHeight
	 } else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0) {
		 viewportheight = document.documentElement.clientHeight
	 } else {
		 viewportheight = document.getElementsByTagName('body')[0].clientHeight
	 }

	 return viewportheight;
}; // end getViewHeight

function showLoadingBar()
{
	base = jimbo.base == undefined ? '/' : jimbo.base;
	obj = $("#loadingBar");
	if(obj.length == 0) {
		$('body').append('<div id="loadingBar" align="center"><img src="' + base + 'images/load.gif" /></div>');
	}
	obj = $("#loadingBar");

	$("#loadingBar").dialog({
	    width:50,
	    height:90,
	    modal: true,
	    draggable:false,
	    resizable:false,
	    closeOnEscape: false,
	    open: function(event, ui) {
	    	$("#loadingBar").prev().hide();
	    	height = $("#loadingBar").outerHeight();
	    	$("#loadingBar").height(height);
	    }
	});

}

function hideLoadingBar()
{
	$("#loadingBar").dialog("destroy");
}

function doSelectTo(obj, id) {
	document.getElementById(id).value = obj.value;
}

function tbl_check_all(namef, status)
{
	elements = $('input[name^=' + namef + ']');
	if(elements.length == 0) {
		return false;
	}

	for (i = 0; i < elements.length; i++) {
		el = elements[i];
		if (el.name.substr(0, namef.length) == namef) {
			el.checked = status;
		}
	}
} // end tbl_check_all

function dbaForeignKeyLoad(f1, f2, val) {
	var url = '?action=foreignKeyLoad&ajaxChild=' + f1 + '&ajaxParent=' + f2 + '&value=' + escape(val);
	document.getElementById('db_system').src = url;
}

function gorupSubmit() {
	value = $("#gSelect option:selected").val();
	if(value == 0) {
		return false;
	}

	document.getElementById('dba_list_form').action = value;
	document.getElementById('dba_list_form').submit();
}

function addPager(pager) {

    var path = document.location.href;
    if (path.indexOf('?') != -1) {
        path = path.replace(new RegExp("pager=[0-9]+", "g"), "");
        path = path + '&pager=' + pager;
        path = path.replace(new RegExp("order=[a-z]+", "g"), "");
        path = path.replace(new RegExp("&+", "g"), "&");
        document.location.href = path;
    } else {
        document.location.href = path + '?pager='+pager;
    }
}

////////////////////////////////////////
// Growl
////////////////////////////////////////

function growl_timer()
{
	var api = $(this).data('qtip'),
    lifespan = 3000; // 5 second lifespan

	// If persistent is set to true, don't do anything.
	if (api.get('show.persistent') === true) { return; }

	// Otherwise, start/clear the timer depending on event type
	//clearTimeout(api.timer);
	//try {
	//	if (typeof(event.type) != 'undefined' && event.type !== 'mouseover') {
		    api.timer = setTimeout(api.hide, lifespan);
	//	}
	//} catch (e) {
	//}
} // end system_timer

function growl_update()
{
	// Loop over each jGrowl qTip
    var each = $('.qtip.jgrowl'),
        width = each.outerWidth(),
        height = each.outerHeight(),
        gap = each.eq(0).qtip('option', 'position.adjust.y'),
        pos;

    each.each(function(i) {
        var api = $(this).data('qtip');

        // Set target to window for first or calculate manually for subsequent growls
        api.options.position.target = !i ? $(window) : [
            pos.left + width, pos.top + (height * i) + Math.abs(gap * (i-1))
        ];
        api.set('position.at', 'top right');

        // If this is the first element, store its finak animation position
        // so we can calculate the position of subsequent growls above
        if(!i) { pos = api.cache.finalPos; }
    });
} // end system_updateGrowls


function growl_create(caption, desc, persistent)
{
	// Use the last visible jGrowl qtip as our positioning target
    var target = $('.qtip.jgrowl:visible:last');

    // Create your jGrowl qTip...
    $(document.body).qtip({
        // Any content config you want here really.... go wild!
        content: {
            text: desc,
            title: {
                text: caption,
                button: true
            }
        },
        position: {
            my: 'top right',
            // Not really important...
            at: (target.length ? 'bottom' : 'top') + ' right',
            // If target is window use 'top right' instead of 'bottom right'
            target: target.length ? target : $(window),
            // Use our target declared above
            adjust: { y: 5},
            effect: function(api, newPos) {
                // Animate as usual if the window element is the target
                $(this).animate(newPos, {
                    duration: 200,
                    queue: false
                });

                // Store the final animate position
                api.cache.finalPos = newPos;
            }
        },
        show: {
            event: false,
            // Don't show it on a regular event
            ready: true,
            // Show it when ready (rendered)
            effect: function() {
                $(this).stop(0, 1).fadeIn(400);
            },
            // Matches the hide effect
            delay: 0,
            // Needed to prevent positioning issues
            // Custom option for use with the .get()/.set() API, awesome!
            persistent: persistent
        },
        hide: {
            event: false,
            // Don't hide it on a regular event
            effect: function(api) {
                // Do a regular fadeOut, but add some spice!
                $(this).stop(0, 1).fadeOut(400).queue(function() {
                    // Destroy this tooltip after fading out
                    api.destroy();

                    // Update positions
                    Jimbo.updateGrowls();
                })
            }
        },
        style: {
            classes: 'jgrowl ui-tooltip-dark ui-tooltip-rounded',
            // Some nice visual classes
            tip: false // No tips for this one (optional ofcourse)
        },
        events: {
            render: function(event, api) {
                // Trigger the timer (below) on render
            	Jimbo.growlTimer.call(api.elements.tooltip, event);
            }
        }
    }).removeData('qtip');
} // end system_createGrowl

function system_showLoadingBar(selector, options)
{
	var defaults = {
		className: 'loading',
		zindex: 5000,
		hPos: 'center',
   		vPos: 'center',
	}

	var options =  $.extend(defaults, options);

	var container = jQuery(selector);
	if (container.length == 0) {
		return false;
	}

	var loaderID = jQuery(container).attr('id');

	var loadingDiv = jQuery('<div></div>');

	jQuery(loadingDiv).addClass(options.className);
	jQuery(loadingDiv).addClass('jimbo-loader-' + loaderID);

	jQuery(loadingDiv).css('display', 'none');
	jQuery(document.body).append(loadingDiv);

	jQuery(loadingDiv).css('position', 'absolute');
	jQuery(loadingDiv).css('z-index', options.zindex);

	var border_top_width = jQuery(container).css('border-top-width');
	var border_left_width = jQuery(container).css('border-left-width');

	//
	// IE will return values like 'medium' as the default border,
	// but we need a number
	//
	border_top_width = isNaN(parseInt(border_top_width)) ? 0 : border_top_width;
	border_left_width = isNaN(parseInt(border_left_width)) ? 0 : border_left_width;

	var indicatorLeft = jQuery(container).offset().left +  parseInt(border_left_width);
	var indicatorTop = jQuery(container).offset().top + parseInt(border_top_width);

	var overlay_width = parseInt(jQuery(container).width()) + parseInt(jQuery(container).css('padding-right')) + parseInt(jQuery(container).css('padding-left'));
	var overlay_height = parseInt(jQuery(container).height()) + parseInt(jQuery(container).css('padding-top')) + parseInt(jQuery(container).css('padding-bottom'));


	if ( options.hPos.toString().toLowerCase() == 'center' ) {
		jQuery(loadingDiv).css('left', (indicatorLeft + ((overlay_width - parseInt(jQuery(loadingDiv).width())) / 2)).toString()  + 'px');
	} else if ( options.hPos.toString().toLowerCase() == 'left' ) {
		jQuery(loadingDiv).css('left', (indicatorLeft + parseInt(jQuery(container).css('margin-left'))).toString() + 'px');
	} else if ( options.hPos.toString().toLowerCase() == 'right' ) {
		jQuery(loadingDiv).css('left', (indicatorLeft + (overlay_width - parseInt(jQuery(loadingDiv).width()))).toString()  + 'px');
	} else {
		jQuery(loadingDiv).css('left', (indicatorLeft + parseInt(options.hPos)).toString() + 'px');
	}

	if ( options.vPos.toString().toLowerCase() == 'center' ) {
		jQuery(loadingDiv).css('top', (indicatorTop + ((overlay_height - parseInt(jQuery(loadingDiv).height())) / 2)).toString()  + 'px');
	} else if ( options.vPos.toString().toLowerCase() == 'top' ) {
		jQuery(loadingDiv).css('top', indicatorTop.toString() + 'px');
	} else if ( options.vPos.toString().toLowerCase() == 'bottom' ) {
		jQuery(loadingDiv).css('top', (indicatorTop + (overlay_height - parseInt(jQuery(loadingDiv).height()))).toString()  + 'px');
	} else {
		jQuery(loadingDiv).css('top', (indicatorTop + parseInt(options.vPos)).toString() + 'px' );
	}

	jQuery(loadingDiv).show();

} // end system_showLoadingBar

function system_hideLoadingBar(selector)
{
	var loaderID = jQuery(selector).attr('id');
	jQuery('.jimbo-loader-' + loaderID).remove();
} // end system_hideLoadingBar


$(document).ready(function() {
	$(document).delegate('.qtip.jgrowl', 'mouseover mouseout', Jimbo.growlTimer);
});






