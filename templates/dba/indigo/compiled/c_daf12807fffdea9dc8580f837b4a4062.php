<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-22 08:54:37 EET */ ?>

<?php echo '

<script>
jQuery(document).ready(function() { 
'; ?>

	 $('#<?php echo $this->_vars['name']; ?>
').tabs();
	 
	 $('#mainmenu').clickMenu();
	 <?php if (count((array)$this->_vars['items'])): foreach ((array)$this->_vars['items'] as $this->_vars['key'] => $this->_vars['item']): ?>
	 	<?php if (! empty ( $this->_vars['item']['items'] )): ?>
		 	$('#<?php echo $this->_vars['name']; ?>
Menu<?php echo $this->_vars['key']; ?>
').clickMenu();
		<?php endif; ?>
	<?php endforeach; endif; ?>
	
	$('a[href$="<?php echo $this->_vars['currentItem']; ?>
"]').css('font-weight', 'bold');
	$('a[href$="<?php echo $this->_vars['currentItem2']; ?>
"]').css('font-weight', 'bold');
	$('a[href$="<?php echo $this->_vars['currentItem']; ?>
"]').parents().parents().parent("li").children("a").css('font-weight', 'bold');
	$('a[href$="<?php echo $this->_vars['currentItem2']; ?>
"]').parents().parents().parent("li").children("a").css('font-weight', 'bold');	

	<?php echo '
$(\'#support_contacts\').click(function() {
	$(\'#support_contacts_details\').dialog({
		height: 200,
		width: 320,
		resizable: false,
		position: [\'right\',30]
	});
});

});
</script>
'; ?>


<table width="100%" height="53px" cellpadding="0" cellspacing="0">
<tr valign="top">
<td>
<div id="<?php echo $this->_vars['name']; ?>
" class="fixerr" >
	<ul class="tabs-nav">
	<?php if (count((array)$this->_vars['items'])): foreach ((array)$this->_vars['items'] as $this->_vars['key'] => $this->_vars['item']): ?>
		<li><div><div style="border-left:1px solid #A5A2A5; border-right:1px solid #414142;"><a href="#<?php echo $this->_vars['name'];  echo $this->_vars['key']; ?>
" <?php if (! empty ( $this->_vars['item']['href'] )): ?>topage="<?php echo $this->_vars['item']['href']; ?>
"<?php endif; ?>><span><?php echo $this->_vars['item']['caption']; ?>
</span></a></div></div></li>
	<?php endforeach; endif; ?>
	</ul>
	
	<?php if (count((array)$this->_vars['items'])): foreach ((array)$this->_vars['items'] as $this->_vars['key'] => $this->_vars['item']): ?>
		<div class="tabs-container tabs-hide" id="<?php echo $this->_vars['name'];  echo $this->_vars['key']; ?>
">
		<?php if (! empty ( $this->_vars['item']['items'] )): ?>
		<ul id="<?php echo $this->_vars['name']; ?>
Menu<?php echo $this->_vars['key']; ?>
"><?php $this->_vars['cnt'] = count($this->_vars['item']['items']) - 1; ?>
		<?php if (count((array)$this->_vars['item']['items'])): foreach ((array)$this->_vars['item']['items'] as $this->_vars['index'] => $this->_vars['menuItem']): ?>
		<li><a href="<?php if (! empty ( $this->_vars['menuItem']['href'] )):  echo $this->_vars['menuItem']['href'];  else: ?>#<?php endif; ?>"><?php echo $this->_vars['menuItem']['caption']; ?>
</a><?php if ($this->_vars['cnt'] != $this->_vars['index']): ?>&nbsp;&nbsp;&nbsp;&nbsp;|<?php endif; ?>
		<?php if (! empty ( $this->_vars['menuItem']['items'] )): ?><ul>
				<?php if (count((array)$this->_vars['menuItem']['items'])): foreach ((array)$this->_vars['menuItem']['items'] as $this->_vars['subItem']): ?>
				<li><?php if (! empty ( $this->_vars['subItem']['href'] )): ?><a href="<?php echo $this->_vars['subItem']['href']; ?>
" ><?php echo $this->_vars['subItem']['caption']; ?>
</a><?php else:  echo $this->_vars['subItem']['caption'];  endif; ?></li>
				<?php endforeach; endif; ?>
			</ul><?php endif; ?>
		</li>
		<?php endforeach; endif; ?>
		</ul><?php endif; ?>
		</div>
	<?php endforeach; endif; ?>
	</div>
</td>	

</tr>

</table>

