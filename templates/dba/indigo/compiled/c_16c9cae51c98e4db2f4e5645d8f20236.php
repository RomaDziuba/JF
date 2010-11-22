<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-22 08:54:37 EET */ ?>

<?php echo '
<script>

jQuery(document).ready(function() { 

	var selectedColor = \'#FEE5FC\';
	$("#tbllist>tbody>tr:nth-child(odd)").addClass("ka_odd");
	$("#filters>tbody>tr:nth-child(odd)").addClass("ka_odd");
	$("#picker").css(\'background-color\', selectedColor);


$("#tbllist>tbody>tr").click(function() {             
	if ($(this).css(\'background-color\') != $("#picker").css(\'background-color\')) {
		$(this).css(\'background-color\', selectedColor);
	} else {
		$(this).css(\'background-color\', $(this).css(\'xxx-background-color\'));
	}
});

$("#tbllist tr").hover(
	function() {
		if ($(this).css(\'background-color\') != $("#picker").css(\'background-color\')) {
			$(this).css(\'xxx-background-color\', $(this).css(\'background-color\'));
			$(this).css(\'background-color\', \'#FFF8E9\');
		}
	},
	function() {
		if ($(this).css(\'background-color\') != $("#picker").css(\'background-color\')) {
			$(this).css(\'background-color\', $(this).css(\'xxx-background-color\'));
		}
	}
);

$(\'a[rel="actionbutton"]\').click(function() { 
	return false;
});

$(\'#filtersButton\').click(function() {
	var filterContainer = document.getElementById(\'filters\');
	filterContainer.style.display = (filterContainer.style.display == \'\') ? \'none\' : \'\';
	
	return false;
});

});


</script>
'; ?>



<div id="statusMessage" style="padding:0px; margin:0px;"></div>
<?php if ($this->_vars['info']['filter'] == 'top' && ! empty ( $this->_vars['filters'] )): ?>
<table class="filters" style="display:none;" id="filters" cellpadding="3" align="center">
	<thead>
		<tr>
			<td class="caption" colspan="2"><?php echo $this->_vars['lang']['FILTERS']; ?>
</td>
		</tr>
	</thead>
	<tbody>
	<?php if (count((array)$this->_vars['filters'])): foreach ((array)$this->_vars['filters'] as $this->_vars['key'] => $this->_vars['item']): ?>
		<?php if (! empty ( $this->_vars['item'] )): ?>
		<tr class="ka_row">
			<td width="140" align="right"><?php echo $this->_vars['info']['fields'][$this->_vars['key']]['caption']; ?>
:</td>
			<td><?php echo $this->_vars['item']; ?>
</td>
		</tr>
		<?php endif; ?>
	<?php endforeach; endif; ?>
	<tr class="ka_row">
		<td colspan="2" align="center" >
			<input type="submit" class="sbutton" value="OK" style="vertical-align: middle" />
		</td>
	</tr>
	</tbody>
</table>
<br />
<?php endif; ?>

<div class="menucontainertop"> 	

<?php if ($this->_vars['info']['insert'] != ''): ?>
		
		<div class="action">
		<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/add.png" width="16" height="16"  border="0" hspace="3" />	
		<a href="#" onClick="openWindow('?action=insert&popup=true')" class="db_link"><?php echo $this->_vars['info']['insert']; ?>
</a>
		</div>
	<?php endif; ?>
	<?php if ($this->_vars['info']['excel'] != ''): ?>
		<div class="action">
		<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/excel.png" width="16" height="16"  border="0" hspace="3" />	
		<a href="?action=excel" class="db_link"><?php echo $this->_vars['info']['excel']; ?>
</a>
		</div>
	<?php endif; ?>
	<?php if ($this->_vars['info']['parent'] != ''): ?>
		<div class="action">
		<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_top.gif" hspace="3" border="0" />
		<a href="?action=parent" class="db_link"><?php echo $this->_vars['info']['parent']; ?>
</a>
		</div>
	<?php endif; ?>
	
</div>

<div class="breadcrumbs"><b><?php echo $this->_vars['info']['caption']; ?>
</b></div>
<table id="tbllist" class="ka_table" border="0" width="100%"  cellpadding="0" cellspacing="1">
<form style="display:inline;" method="post" name="test">

	<input type="hidden" name="picker" id="picker" />
	<input type="hidden" name="filter_wtd" id="filter_wtd" value="apply" />

<thead>
		<tr>
		<?php if ($this->_vars['info']['grouped']): ?>
			<td width="15px" class="jcol">&nbsp;</td>
		<?php endif; ?>
		<?php if (count((array)$this->_vars['info']['fields'])): foreach ((array)$this->_vars['info']['fields'] as $this->_vars['field']): ?>
			<td class="jcol" width="<?php echo $this->_vars['field']['width']; ?>
" <?php if ($this->_vars['field']['align']): ?>align="<?php echo $this->_vars['field']['align']; ?>
"<?php endif; ?> nowrap>
				<A HREF="?<?php echo $this->_vars['info']['query']; ?>
&order=<?php echo $this->_vars['field']['name']; ?>
&direction=<?php if ($this->_vars['info']['sorting']['field'] == $this->_vars['field']['name']):  if ($this->_vars['info']['sorting']['direction'] == 'ASC'): ?>DESC<?php else: ?>ASC<?php endif;  else: ?>ASC<?php endif; ?>" class="db_link"><?php echo $this->_vars['field']['caption']; ?>
</A>
				<?php if (( $this->_vars['info']['sorting']['field'] == $this->_vars['field']['name'] )): ?>
					&nbsp;<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_sort_<?php if ($this->_vars['info']['sorting']['direction'] == 'ASC'): ?>az<?php else: ?>za<?php endif; ?>.gif">
				<?php endif; ?>
			</td>
		<?php endforeach; endif; ?>
		
		<?php if ($this->_vars['data']['0']['actions'] || ! empty ( $this->_vars['filters'] )): ?>
		<td class="jcol" style="padding:0pc; margin:0px;" align="center">
			<?php if ($this->_vars['info']['filter'] == 'top' && ! empty ( $this->_vars['filters'] )): ?>
				<input id="filtersButton" type="button" class="sbutton" value="<?php echo $this->_vars['lang']['FILTERS']; ?>
" style="vertical-align: middle;" />
			<?php endif; ?>
		</td>
		<?php endif; ?>
		
		
		<?php if ($this->_vars['info']['filter'] != 'top' && ! empty ( $this->_vars['filters'] )): ?>	
			<TR height="25" valign="top" class="ka_row">
				
				<?php if ($this->_vars['info']['grouped']): ?>
				<td width="15px">&nbsp;</td>
				<?php endif; ?>
				
				<?php if (count((array)$this->_vars['filters'])): foreach ((array)$this->_vars['filters'] as $this->_vars['item']): ?>
						<TD ALIGN="LEFT"><?php echo $this->_vars['item']; ?>
</TD>
				<?php endforeach; endif; ?>
				<td align="center"><input type="submit" class="sbutton" value="OK" style="vertical-align: middle"></td>
			</TR>
		<?php endif; ?>
		

	</thead>
	</form>
	
	<form method="POST" target="_systemfr">
	<input type="hidden" name="performPost" value="insert">
	<?php if ($this->_vars['info']['token']): ?> 
		<input type="hidden" name="__token" value="<?php echo $this->_vars['info']['token']; ?>
">
	<?php endif; ?>

	<tbody>
		<?php if ($this->_vars['info']['fastAdd']): ?>
			<TR height="25" valign="top" class="ka_row">
				
				<?php if ($this->_vars['info']['grouped']): ?>
				<td width="15px">&nbsp;</td>
				<?php endif; ?>
				
				<?php if (count((array)$this->_vars['info']['fieldInputs'])): foreach ((array)$this->_vars['info']['fieldInputs'] as $this->_vars['item']): ?>
						<TD align="left"><?php echo $this->_vars['item']; ?>
</TD>
				<?php endforeach; endif; ?>
				<td align="center"><input type="submit" class="sbutton" value="+" style="vertical-align: middle"></td>
			</TR>
		<?php endif; ?>
		
		<?php if (count((array)$this->_vars['data'])): foreach ((array)$this->_vars['data'] as $this->_vars['line']): ?>
			<?php if ($this->_vars['line']['_group_caption']): ?>
				<?php if ($this->_vars['line']['_group_total']): ?>
					<tr class="ka_row">
						<?php if ($this->_vars['info']['grouped']): ?>
							<TD>&nbsp;</TD>
						<?php endif; ?>
			
						<?php if (count((array)$this->_vars['line']['_group_total'])): foreach ((array)$this->_vars['line']['_group_total'] as $this->_vars['sitem']): ?>
							<td align="right"><b><?php echo $this->_vars['sitem']; ?>
</b></td>
						<?php endforeach; endif; ?>
						
						<?php if ($this->_vars['data']['0']['actions'] || $this->_vars['filters']): ?>
							<TD>&nbsp;</TD>
						<?php endif; ?>
				<?php endif; ?>

			<tr class="ka_row">
				<td colspan="50"><b><?php echo $this->_vars['line']['_group_caption']; ?>
</b></td>
			</tr>
			<?php endif; ?>
			<tr class="ka_row">
				<?php if ($this->_vars['info']['grouped']): ?>
					<TD><input type="checkbox" name="grouped_cb[]" value="<?php echo $this->_vars['line']['id']; ?>
"></TD>
				<?php endif; ?>

				<?php if (count((array)$this->_vars['line']['data'])): foreach ((array)$this->_vars['line']['data'] as $this->_vars['item']): ?>
					<td <?php if ($this->_vars['item']['align']): ?>align="<?php echo $this->_vars['item']['align']; ?>
"<?php endif; ?>><?php echo $this->_vars['item']['value']; ?>
</td>
				<?php endforeach; endif; ?>
				
				<?php if (! empty ( $this->_vars['line']['actions'] )): ?>
					<TD align="center" nowrap>
					
					   <?php if (! empty ( $this->_vars['line']['action_lists'] )): ?>
                            <select onchange="dbaListActions(this);">
                                <option></option>
                            <?php if (count((array)$this->_vars['line']['action_lists'])): foreach ((array)$this->_vars['line']['action_lists'] as $this->_vars['action']): ?>
                                <option value="<?php echo $this->_vars['action']['href']; ?>
" <?php if ($this->_vars['action']['popup']): ?>popup="1"<?php endif; ?>><?php echo $this->_vars['action']['alt']; ?>
</option>
                            <?php endforeach; endif; ?>
                            </select>
                        <?php endif; ?>
					
						<?php if (count((array)$this->_vars['line']['actions'])): foreach ((array)$this->_vars['line']['actions'] as $this->_vars['action']): ?>
							<?php if ($this->_vars['action']['popup']): ?>
								&nbsp;<A href="#" onClick='<?php if ($this->_vars['action']['js']):  echo $this->_vars['action']['js'];  else:  echo $this->_vars['action']['popupFunction'];  endif; ?>("<?php echo $this->_vars['action']['href']; ?>
&popup=true"); return false;' title="<?php echo $this->_vars['action']['alt']; ?>
" rel="actionbutton"><IMG SRC="<?php echo $this->_vars['action']['src']; ?>
" ALT="<?php echo $this->_vars['action']['alt']; ?>
" BORDER="0"></A>&nbsp;
							<?php else: ?>
								&nbsp;<A href="<?php echo $this->_vars['action']['href']; ?>
" <?php if ($this->_vars['action']['js']): ?>onClick='<?php echo $this->_vars['action']['js']; ?>
("<?php echo $this->_vars['action']['href']; ?>
"); return false;'<?php endif; ?> title="<?php echo $this->_vars['action']['alt']; ?>
" <?php echo $this->_vars['action']['addon']; ?>
 <?php echo $this->_vars['action']['target']; ?>
><IMG SRC="<?php echo $this->_vars['action']['src']; ?>
" ALT="<?php echo $this->_vars['action']['alt']; ?>
" BORDER="0" rel="actionbutton"></A>&nbsp;
							<?php endif; ?>
    					<?php endforeach; endif; ?>
    					
					</TD>
				<?php elseif ($this->_vars['filters']): ?>
					<td>&nbsp;</td>
				<?php endif; ?>
				
			</tr>
		<?php endforeach; endif; ?>
		
		<?php if ($this->_vars['info']['subtotals']): ?>
			<tr class="ka_row">
				<?php if ($this->_vars['info']['grouped']): ?>
					<TD>&nbsp;</TD>
				<?php endif; ?>

				<?php if (count((array)$this->_vars['info']['subtotals'])): foreach ((array)$this->_vars['info']['subtotals'] as $this->_vars['item']): ?>
					<td align="right"><b><?php echo $this->_vars['item']; ?>
</b></td>
				<?php endforeach; endif; ?>
				
				<?php if ($this->_vars['data']['0']['actions'] || $this->_vars['filters']): ?>
					<TD>&nbsp;</TD>
				<?php endif; ?>
		<?php endif; ?>
	</tbody>
	</form>
</table>

<div class="menucontainerbottom">
  
  <?php if ($this->_vars['info']['grouped']): ?>
  	<div class="backlink" style="width:300px;"><?php echo $this->_vars['info']['grouped']; ?>
</div>
  <?php endif; ?>
  
  <?php if ($this->_vars['info']['backlink'] != ''): ?>
  	<div class="backlink" >
  	<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_back.gif" border="0" hspace="2" style="vertical-align: bottom" />
  	<a href="<?php echo $this->_vars['info']['backlink']; ?>
" class="db_link"><?php echo $this->_vars['lang']['BUTTON_BACK']; ?>
</a></div>
  <?php endif; ?>

  
  <?php if (isset ( $this->_vars['info']['pager'] )): ?>
  <div class="pager">
  	<span><?php echo $this->_vars['info']['pager']; ?>
</span>
  	<span><span>[<?php echo $this->_vars['lang']['TOTAL_ITEMS']; ?>
: <?php echo $this->_vars['info']['totalRows']; ?>
]</span></span>
 	</div>
 <?php endif; ?>

 <div class="perpage">
 <span>
  <?php echo $this->_vars['lang']['ITEMSOERPAGE']; ?>
: <select OnChange="addPager(this.value)">
  <?php if (count((array)$this->_vars['info']['limitOptions'])): foreach ((array)$this->_vars['info']['limitOptions'] as $this->_vars['key'] => $this->_vars['value']): ?>
    <option value="<?php echo $this->_vars['key']; ?>
" <?php if ($this->_vars['key'] == $this->_vars['info']['rowsPerPage']): ?>selected<?php endif; ?>><?php echo $this->_vars['value']; ?>
</option>
  <?php endforeach; endif; ?>
  </select>
  </span>
  </div>

</div>


</FORM>

<script>
<?php if ($this->_vars['info']['highlight']): ?>
        <?php if (count((array)$this->_vars['info']['highlight'])): foreach ((array)$this->_vars['info']['highlight'] as $this->_vars['value']): ?>
                <?php echo '$'; ?>
("#TR_<?php echo $this->_vars['value']; ?>
").css('background-color', '#FFF0CF');
        <?php endforeach; endif;  endif; ?>
</script>

<br/><br/>

<iframe name="_systemfr" style="display:none"></iframe>