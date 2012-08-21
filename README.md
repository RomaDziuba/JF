For translation file we recommend use http://www.poedit.net/download.php

Jimbo JavaScript functions
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
