function jsonResponse(data) 
{
	if(data['eval'] != undefined) {
		eval(data['eval']);
	}
	
	switch(data['type']) {
		case 'error':
			dbaUpdateError(data);
			break;
		
		case 'success':
			dbaUpdateSuccess(data);
			break;
			
		case 'alert':
			showMessages(data['title'], data['message']);
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
	height = $(obj).outerHeight() + 90;
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
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
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

function loadContent(url, id) 
{
	id = (id == undefined) ? 'loader' : id;
	
	obj = $("#" + id);
	if(obj.length == 0) {
		$('body').append('<div id="'+id+'"></div>');
	}
	obj = $("#" + id);
	
	obj.load(url, function() {
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
	    open: function(event, ui) { $("#loadingBar").prev().hide();}
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






