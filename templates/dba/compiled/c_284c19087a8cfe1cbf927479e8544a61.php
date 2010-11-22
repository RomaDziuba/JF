<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-22 08:54:37 EET */ ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv=Content-Type content="text/html; charset=<?php echo $this->_vars['info']['charset']; ?>
" >
    <title><?php echo $this->_vars['info']['title']; ?>
</title>

	<link rel="stylesheet" type="text/css" href="<?php echo constant('STYLES_HTTP_ROOT');  echo constant('ENGINE_STYLE'); ?>
.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo $this->_vars['info']['basehttp']; ?>
js/calendar/calendar.css">
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/calendar/calendar.js"></script>
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/calendar/lang/calendar-en.js"></script>
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/calendar/calendar_add.js"></script>

    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/jquery-ui-1.8.5.custom.min.js"></script>
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/jquery.qtip.js"></script>
	
	<?php $_templatelite_tpl_vars = $this->_vars;
echo $this->_fetch_compile_include($this->_vars['info']['style_header'], array());
$this->_vars = $_templatelite_tpl_vars;
unset($_templatelite_tpl_vars);
 ?>
	
    <?php if ($this->_vars['info']['css']): ?>
        <?php if (count((array)$this->_vars['info']['css'])): foreach ((array)$this->_vars['info']['css'] as $this->_vars['path']): ?>
            <link rel="stylesheet" type="text/css" href="<?php echo $this->_vars['info']['basehttp'];  echo $this->_vars['path']; ?>
" />
        <?php endforeach; endif; ?>
    <?php endif; ?>
    
    <?php echo '
    <script>
        var jimbo = {
        '; ?>

            mode: "<?php echo constant('JIMBO_POPUP_MODE'); ?>
",
            dialogWidth: 640,
            base: "<?php echo $this->_vars['info']['basehttp']; ?>
"
        <?php echo '
        };
    </script>
    '; ?>

    
    <script type="text/javascript" src="<?php echo $this->_vars['info']['basehttp']; ?>
js/jimbo.js"></script>

</head>
<body>

<div id="hld">
	<div class="wrapper">
		
		<?php if ($this->_vars['_user']['auth_login']): ?>
		<div id="header">
			<div class="hdrl"></div>
			<div class="hdrr"></div>
			<?php if ($this->_vars['_config']['site_caption']): ?>
				<h1><a href="<?php echo $this->_vars['info']['basehttp']; ?>
"><?php echo $this->_vars['_config']['site_caption']; ?>
</a></h1>
			<?php endif; ?>
			<?php echo $this->_vars['menu']; ?>

			<p class="user">Hello, <a href="#"><?php echo $this->_vars['_user']['auth_login']; ?>
</a> | <a href="<?php echo $this->_vars['info']['basehttp']; ?>
logout/">Logout</a></p>
		</div>
		<?php endif; ?>
		
		<div class="cc"><?php echo $this->_vars['content']; ?>
</div>	
		
	</div>
</div>

























</BODY></HTML>
