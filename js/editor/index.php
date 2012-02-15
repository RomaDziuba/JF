<?php
include "../config.php";


if (!empty($_POST)) {
	$content = trim($_POST['FCKeditor1']);
	$content = get_magic_quotes_gpc() ? stripslashes($_POST['FCKeditor1']) : $_POST['FCKeditor1'];

	// this will allow use & char in plugin call from article when edit in FCKeditor
	$content = preg_replace_callback('/(\{\%[^:]+:[^\?\}]+[\?]{0,1})([^\}]*)(\})/', create_function('$matches', 'return $matches[1].str_replace(\'&amp;\', \'&\', $matches[2]). $matches[3];'), $content);
	
	
	if ( (substr($content, 0, 3) == '<p>') && (substr($content, -4, 4) == '</p>') && 
	(strpos($content, '<p>', 3) == false) ) {
		// У нас лишний первый и последний <p>
		$content = substr($content, 3, -4);
	}
		
	$sql = "update ".mysql_escape_string($_GET['table'])." set body='".mysql_escape_string($content)."' where id=".(int)$_GET['ID'];
	$db->query($sql);

}

$info = $db->getRow("select * from ".mysql_escape_string($_GET['table'])." where id=".(int)$_GET['ID']);
if (empty($info['id'])) {
	echo "error";
}

$content = htmlspecialchars($info['body'], ENT_COMPAT, 'utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<title>XHTML Document</title>
	<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
	<style type="text/css">
    html,body{
    height:100%;
    }
    .myclass {
    	color: red;
    }
    </style>
	<script src="/admin/editor/fckeditor.js" type="application/x-javascript"></script>
</head>
<body style="margin:0px; padding:0px;">
	<form  method="post" target="_self">
	<script type="text/javascript">
	var oFCKeditor = new FCKeditor('FCKeditor1');
	//oFCKeditor.EditorAreaCSS = '/static/css/main.css';
	oFCKeditor.BasePath = '/admin/editor/' ;
	</script>
	<div>
		<input id="FCKeditor1" name="FCKeditor1" value="<?php echo $content; ?>" style="display: none;" type="hidden" />
		<iframe id="FCKeditor1___Frame" src="/admin/editor/editor/fckeditor.html?InstanceName=FCKeditor1&amp;Toolbar=Default" frameborder="0" height="500px" scrolling="no" width="100%" onload="this.height=window.top.document.body.clientHeight-5"></iframe>
	</div>
	<!--<input value="Submit" type="submit" />-->
</body>
</html>
