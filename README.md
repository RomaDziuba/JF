For translation file we recommend use http://www.poedit.net/download.php

Database Table Xml
================================

foreignKey
-------------------------
``` xml
<field  type="foreignKey"
		name="id_city"
		caption="City"
		width="15%"
		foreignTable="cities"
		foreignKeyField="id"
		foreignValueField="caption"
		join="true" />
```

datetime
-------------------------
``` xml
<field 	type="datetime"
		name="date_order"
		caption="Order Date"
		width="20%"
		sorting="true" />
```
Specific attributes:
*default* - default date value,
*format* - date format, [see strftime](http://php.net/manual/ru/function.strftime.php),
*length* - deprecated, better use attribute format

wysiwyg
-------------------------
``` xml
<field 	type="wysiwyg"
		name="body"
		caption="Content"
		required="true"
		hide="true" />
```

file
-------------------------
``` xml
<field 	type="file"
		name="file"
		caption="Images"
		thumb="120x80"
		fileName="wphid__ID__.jpg" />
```
Specific attributes:
*thumb* - dimension of image thumb, used only if field upload images,
*fileName* - template for file name, __ID__ will be change to row id


Jimbo JavaScript Functions
================================

Display Growl Message
-------------------------

``` html
<script>
$(document).ready(function() {
	Jimbo.growlCreate("Warning", "Test growl", true);
});
</script>
```

Show loading bar in html element
-------------------------

``` js
Jimbo.showLoadingBar('#container');
Jimbo.showLoadingBar($('#container'));
Jimbo.showLoadingBar('#container', {
	className: 'loading',
	zindex: 5000,
	hPos: 'center',
	vPos: 'center'
});

Jimbo.hideLoadingBar('#container');
```
``` css
.loading {
	height: 80px;
	width: 80px;
	background: url('/images/loading.gif');
	background-repeat: no-repeat;
	background-position: center center;
}
```
