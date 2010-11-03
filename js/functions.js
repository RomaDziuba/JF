function opnewWin(s, w, h, is_centered, pars)
{
	var winank;
	if (navigator.appName == 'Microsoft Internet Explorer') {
		window.showModelessDialog(s, "popup", "dialogWidth="+w+"px;dialogHeight="+h+"px;resizable=no;status=no;toolbar=no;menubar=no;location=no;titlebar=no;directories=no");
	}
	else {
		if (is_centered == 1)
		{
			var coord = new Array();
			coord = get_center_window_coord(h, w);
			left_pos = coord["x"];
			top_pos = coord["y"];
			winank = window.open(s, "popup", "height=" + h + ",width=" + w + ",,,,,,top=" + top_pos + ",left=" + left_pos + pars);
		}
		else
		{
			winank = window.open(s, "popup", "width= " + w + ", height= " + h + pars);
		}
	}
}

function get_center_window_coord(h_desc, w_desc)
{
	var coord = new Array("x", "y");
	var swidth=0;
	var sheight=0;
	var left_pos, top_pos;
	h_desc = h_desc || 500;
	w_desc = w_desc || 600;
	if (self.screen) { // for NN4 and IE4
		swidth = screen.width;
		sheight = screen.height
	} else if (self.java) { // for NN3 with enabled Java
		var jkit = java.awt.Toolkit.getDefaultToolkit();
		var scrsize = jkit.getScreenSize();
		swidth = scrsize.width;
		sheight = scrsize.height;
	}
	coord["x"] = (swidth/2) - (w_desc/2);
	coord["y"] = (sheight/2) - (h_desc/2);
	return coord;
}

function tinyEditor(textarea_elements) {
	if (typeof tinyMCE != "undefined")
	tinyMCE.init({

		mode : "textareas",
		theme : "advanced",
		plugins : "pagebreak,layer,table,save,advhr,advimage,advlink,inlinepopups,insertdatetime,media,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,imagemanager,filemanager",



		// Theme options
		theme_advanced_buttons1 : "save,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code",
		theme_advanced_buttons2 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,forecolor,backcolor,|,fontselect,fontsizeselect",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",

		file_browser_callback : "ajaxfilemanager",

		// Example content CSS (should be your site CSS)
		content_css : "/css/dbadmin.css"
	});
}

function ajaxfilemanager(field_name, url, type, win) {
	var ajaxfilemanagerurl = "/editor/ajaxfilemanager/ajaxfilemanager/ajaxfilemanager.php?editor=tinymce";
	switch (type) {
		case "image":
		break;
		case "media":
		break;
		case "flash":
		break;
		case "file":
		break;
		default:
		return false;
	}
	tinyMCE.activeEditor.windowManager.open({
		url: "/editor/ajaxfilemanager/ajaxfilemanager/ajaxfilemanager.php?editor=tinymce",
		width: 782,
		height: 440,
		inline : "yes",
		close_previous : "no"
	},{
		window : win,
		input : field_name
	});

}

var Browser = {
	Version: function() {
		var version = 999; // we assume a sane browser
		if (navigator.appVersion.indexOf("MSIE") != -1)
		// bah, IE again, lets downgrade version number
		version = parseFloat(navigator.appVersion.split("MSIE")[1]);
		return version;
	}
}

function getScrollWidth() {
	document.body.style.overflow = 'hidden';
	var width = document.body.clientWidth;
	document.body.style.overflow = 'scroll';
	width -= document.body.clientWidth;
	if(!width) width = document.body.offsetWidth - document.body.clientWidth;
	document.body.style.overflow = '';
	return width;
}

var isFixedScrollBar = false;

function fixTableByHeader(headerId, tableId, headerCellType, mainWidthType) {
	var headerTable = document.getElementById(headerId);
	var headerBody = headerTable.getElementsByTagName("tbody")[0];
	var headerRow = headerBody.getElementsByTagName("tr")[0];
	var headerCells = headerRow.getElementsByTagName(headerCellType);

	var dataTableObj = document.getElementById(tableId);
	var dataBody = dataTableObj.getElementsByTagName("tbody")[0];
	var dataRow = dataBody.getElementsByTagName("tr")[0];
	var dataCells = dataRow.getElementsByTagName("td");

	var browserVersion = Browser.Version();

	// "-1" - skip last specific column for scrollbar
	for (i = 0; i < headerCells.length - 1; i++) {
		if (mainWidthType == 'header') {
			if (headerCells[i].width != "" || headerCells[i].style.width != "") {
				if (browserVersion == 7) {
					if (i < headerCells.length - 2) {
						dataCells[i].width = headerCells[i].clientWidth+"px";
					}
					else {
						dataCells[i].width = headerCells[i].clientWidth + headerCells[i+1].clientWidth+1 + "px";
					}
				}
				else {
					dataCells[i].style.width = headerCells[i].clientWidth+"px";
					//alert(dataCells[i].innerHTML + " -> " +  headerCells[i].innerHTML + " -> " + dataCells[i].clientWidth+"px" + " -> " + headerCells[i].style.width);

				}
			}
		}
		else {
			//alert(dataCells[i].innerHTML + " -> " +  headerCells[i].innerHTML + " -> " + dataCells[i].clientWidth+"px" + " -> " + headerCells[i].style.width);
			if (browserVersion == 7) {
				dataTableObj.width = "100%";
				if (i < headerCells.length - 2) {
					headerCells[i].style.width = dataCells[i].clientWidth+"px";
				}
				else {
					headerCells[i].style.width = dataCells[i].clientWidth - (headerCells[i+1].clientWidth) +"px";
				}
				//alert(dataCells[i].innerHTML + " -> " +  headerCells[i].innerHTML + " -> " + dataCells[i].clientWidth+"px" + " -> " + headerCells[i].style.width);
			}
			else {
				headerCells[i].style.width = dataCells[i].clientWidth+"px";
			}
		}
	}

	//normalize header for current scrollbar (needed only in FF)
	if (browserVersion != 7 && !isFixedScrollBar) {
		var baseScrollWidth = 17;
		var curScrollWidth = getScrollWidth();
		var scrollWidthDiff = curScrollWidth - baseScrollWidth;
		curHeaderScrollCellWidth = parseInt(headerCells[headerCells.length - 1].style.width);
		if (isNaN(curHeaderScrollCellWidth) || curHeaderScrollCellWidth == 0) {
			curHeaderScrollCellWidth = parseInt(headerCells[headerCells.length - 1].width);
		}
		headerCells[headerCells.length - 1].style.width = curHeaderScrollCellWidth + scrollWidthDiff + "px";
		headerCells[headerCells.length - 1].width = curHeaderScrollCellWidth + scrollWidthDiff + "px";
		isFixedScrollBar = true;
	}
	document.getElementById('data_table_container').className = 'data_table_container';	
	document.getElementById('data_table_container').style.maxHeight = (screen.height - 410) + "px";
}

function openFullWindow(uri) {

    if (navigator.appName == 'Microsoft Internet Explorer') {
        //window.showModelessDialog(uri, window, "dialogWidth=600px;fullscreen=1;dialogHeight=570px;resizable=no;scroll=no;status=no;toolbar=no;menubar=no;location=no;titlebar=no;directories=no");
        window.open(uri, '_blank', 'fullscreen=1, toolbar=0, titlebar =0');
    } else {
        var popW = parseInt(screen.width * 0.95);
        var popH = parseInt(screen.height * 0.9);
        var left = parseInt(screen.width * 0.025);
        var top = parseInt(screen.height * 0.05);
        window.open(uri, '', "width="+popW+",height="+popH+",top="+top+",left="+left+",status=no,toolbar=no,menubar=no,location=no,titlebar=no,resizable=no,directories=no,scroll=no");
    }
}

function doMassAction(sel) {
    document.groupf.action = '/external/' + sel.value;
    document.groupf.submit();
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
function gorupSubmit() {
    if (document.getElementById('gSelect').value) {
        document.getElementById('tbl_form').action = document.getElementById('gSelect').value;
        document.getElementById('tbl_form').submit();
    }
}

function displayJsLoader() {
	var loaderStyle = document.getElementById('js_loader_block').style;	
	var posL = parseInt(screen.width/2);
	var posT = parseInt(screen.height/2);	
	loaderStyle.left = posL+"px";
	loaderStyle.top = posT+"px";
	loaderStyle.position = 'absolute';	
	loaderStyle.display = 'block';	
}

function chooseCorrespondingProduct(sku_code) {	
	var correspondingProduct = document.getElementById('corresponding_products_'+sku_code).value;
	if (correspondingProduct != '') {
		document.getElementById('status_image_'+sku_code).src = "/images/tick.png";
		document.getElementById('row_'+sku_code).style.backgroundColor = "#D5EFC2";
		document.getElementById('status_'+sku_code).value = 1;
		
	}
	else {
		document.getElementById('status_image_'+sku_code).src = "/images/dbadmin_remove.gif";
		document.getElementById('row_'+sku_code).style.backgroundColor = "white";
		document.getElementById('status_'+sku_code).value = 0;
	}
}

function checkImportProductionForm(formObj) {
	var distr = document.getElementById('group').value;
	if (distr == "") {
		alert("�������� �������������");
		return false;
	}
	return true;
}
