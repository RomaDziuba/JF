<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-20 02:42:46 EET */ ?>

<?php echo '
<script>

jQuery(document).ready(function() { 
	$(\'#tbllist tr\').each(function(index) {
		className = (index % 2 == 0) ? \'odd\' : \'even\';
		$(this).attr(\'class\', className);
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


<div class="block">
	
	<div class="block_head">
		<div class="bheadl"></div>
		<div class="bheadr"></div>
		<h2><?php echo $this->_vars['info']['caption']; ?>
</h2>
					
		<ul>
			<?php if ($this->_vars['info']['generalActions']): ?>
				<?php if (count((array)$this->_vars['info']['generalActions'])): foreach ((array)$this->_vars['info']['generalActions'] as $this->_vars['type'] => $this->_vars['action']): ?>
					<li><a class="dba_action_<?php echo $this->_vars['type']; ?>
" <?php if ($this->_vars['action']['js']): ?>href="#" onclick='<?php echo $this->_vars['action']['js']; ?>
("<?php echo $this->_vars['action']['href']; ?>
"); return false;'<?php else: ?>href="<?php echo $this->_vars['action']['href']; ?>
"<?php endif; ?> ><?php echo $this->_vars['action']['caption']; ?>
</a></li>
				<?php endforeach; endif; ?>
			<?php endif; ?>
			
			<?php if ($this->_vars['info']['excel'] != ''): ?>
				<li><a style="background-image:url('<?php echo $this->_vars['info']['baseurl']; ?>
images/excel.png'); background-repeat:no-repeat; padding-left:19px; font-size:13px;" href="?action=excel" ><?php echo $this->_vars['info']['excel']; ?>
</a></li>
			<?php endif; ?>
			<?php if ($this->_vars['info']['parent'] != ''): ?>
				<li><a style="background-image:url('<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_top.gif'); background-repeat:no-repeat; padding-left:19px; font-size:13px;" href="?action=parent" ><?php echo $this->_vars['info']['parent']; ?>
</a></li>
			<?php endif; ?>
			<?php if ($this->_vars['info']['insert'] != ''): ?>
				<li><a style="background-image:url('<?php echo $this->_vars['info']['baseurl']; ?>
images/add.png'); background-repeat:no-repeat; padding-left:19px; font-size:13px;" href="#" onClick="openWindow('?action=insert&popup=true'); return false;"><?php echo $this->_vars['info']['insert']; ?>
</a></li>
			<?php endif; ?>
		</ul>
	</div> 
				
				
	<div class="block_content">
		
		<table id="tbllist" width="100%" cellspacing="0" cellpadding="0" class="sortable">
			<form method="post">
				<input type="hidden" name="picker" id="picker" />
				<input type="hidden" name="filter_wtd" id="filter_wtd" value="apply" />
				
				<thead>
					<tr>
    					<?php if ($this->_vars['info']['grouped']): ?>
    						<th width="10">
								<input type="checkbox" title="Select all items" onClick="tbl_check_all('grouped_cb', this.checked)" />
    						</th>
    					<?php endif; ?>
    					
    					<?php if (count((array)$this->_vars['info']['fields'])): foreach ((array)$this->_vars['info']['fields'] as $this->_vars['field']): ?>
    						<th class="header" width="<?php echo $this->_vars['field']['width']; ?>
" style="cursor: pointer;" nowrap="nowrap">
    							<a href="?<?php echo $this->_vars['info']['query']; ?>
&order=<?php echo $this->_vars['field']['name']; ?>
&direction=<?php if ($this->_vars['info']['sorting']['field'] == $this->_vars['field']['name']):  if ($this->_vars['info']['sorting']['direction'] == 'ASC'): ?>DESC<?php else: ?>ASC<?php endif;  else: ?>ASC<?php endif; ?>"><?php echo $this->_vars['field']['caption']; ?>
</a>
    							<?php if (( $this->_vars['info']['sorting']['field'] == $this->_vars['field']['name'] )): ?>
    							&nbsp;<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_sort_<?php if ($this->_vars['info']['sorting']['direction'] == 'ASC'): ?>az<?php else: ?>za<?php endif; ?>.gif">
    							<?php endif; ?>
    						</th>
    					<?php endforeach; endif; ?>
    					
    					<?php if ($this->_vars['data']['0']['actions'] || ! empty ( $this->_vars['filters'] )): ?>
    						<th>
    							<?php if ($this->_vars['info']['filter'] == 'top' && ! empty ( $this->_vars['filters'] )): ?>
    								<input id="filtersButton" type="button" class="submit small_tiny" value="<?php echo $this->_vars['lang']['FILTERS']; ?>
" />
								<?php else: ?>
								&nbsp;
    							<?php endif; ?>
    						</th>
						<?php endif; ?>
					</tr>
					
					
					<?php if ($this->_vars['info']['filter'] != 'top' && ! empty ( $this->_vars['filters'] )): ?>	
						<tr class="even">
							<?php if ($this->_vars['info']['grouped']): ?>
								<td>&nbsp;</td>
							<?php endif; ?>
							<?php if (count((array)$this->_vars['filters'])): foreach ((array)$this->_vars['filters'] as $this->_vars['item']): ?>
								<td><?php if ($this->_vars['item']):  echo $this->_vars['item'];  else: ?>&nbsp;<?php endif; ?></td>
							<?php endforeach; endif; ?>
							<td align="center"><input type="submit" class="submit small_tiny" value="OK" style="vertical-align: middle"></td>
						</tr>
					<?php endif; ?>
					
				</thead>
			</form>
			
			<form id="dba_list_form" method="post" target="_systemfr">
				<input type="hidden" name="performPost" value="insert" />
				<?php if ($this->_vars['info']['token']): ?> 
					<input type="hidden" name="__token" value="<?php echo $this->_vars['info']['token']; ?>
" />
				<?php endif; ?>
				
				<tbody>
					<?php if ($this->_vars['info']['fastAdd']): ?>
    					<tr class="odd">
    					<?php if ($this->_vars['info']['grouped']): ?>
    						<td>&nbsp;</td>
    					<?php endif; ?>
    					<?php if (count((array)$this->_vars['info']['fieldInputs'])): foreach ((array)$this->_vars['info']['fieldInputs'] as $this->_vars['item']): ?>
    						<td><?php echo $this->_vars['item']; ?>
</td>
    					<?php endforeach; endif; ?>
    					<td align="center"><input type="submit" class="submit small_tiny" value="+" /></td>
    					</tr>
					<?php endif; ?>
		
					<?php if (count((array)$this->_vars['data'])): foreach ((array)$this->_vars['data'] as $this->_vars['line']): ?>
						<?php if ($this->_vars['line']['_group_caption']): ?>
							<?php if ($this->_vars['line']['_group_total']): ?>
								<tr class="odd">
									<?php if ($this->_vars['info']['grouped']): ?>
										<td>&nbsp;</td>
									<?php endif; ?>
			
									<?php if (count((array)$this->_vars['line']['_group_total'])): foreach ((array)$this->_vars['line']['_group_total'] as $this->_vars['sitem']): ?>
										<td align="right"><b><?php echo $this->_vars['sitem']; ?>
</b></td>
									<?php endforeach; endif; ?>
						
									<?php if ($this->_vars['data']['0']['actions'] || $this->_vars['filters']): ?>
										<td>&nbsp;</td>
									<?php endif; ?>
								</tr>
							<?php endif; ?>
							<tr class="odd">
								<td colspan="50"><b><?php echo $this->_vars['line']['_group_caption']; ?>
</b></td>
							</tr>
						<?php endif; ?>
						<tr class="odd">
							<?php if ($this->_vars['info']['grouped']): ?>
								<td><input type="checkbox" name="grouped_cb[]" value="<?php echo $this->_vars['line']['id']; ?>
" /></td>
							<?php endif; ?>

							<?php if (count((array)$this->_vars['line']['data'])): foreach ((array)$this->_vars['line']['data'] as $this->_vars['item']): ?>
								<td <?php if ($this->_vars['item']['align']): ?>align="<?php echo $this->_vars['item']['align']; ?>
"<?php endif; ?>><?php if ($this->_vars['item']['value']):  echo $this->_vars['item']['value'];  else: ?>&nbsp;<?php endif; ?></td>
							<?php endforeach; endif; ?>
				
							<?php if (! empty ( $this->_vars['line']['actions'] )): ?>
								<td align="center" nowrap="nowrap">
									
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
								</td>
							<?php elseif ($this->_vars['filters']): ?>
								<td>&nbsp;</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</form>
		</table>
						
		
		<?php if ($this->_vars['info']['grouped']): ?>
			<div class="tableactions"><?php echo $this->_vars['info']['grouped']; ?>
</div>
		<?php endif; ?>
						
		<?php if (isset ( $this->_vars['info']['pager'] )): ?>
    		<div class="pagination right">
    			<?php echo $this->_vars['info']['pager']; ?>
 <span>[<?php echo $this->_vars['lang']['TOTAL_ITEMS']; ?>
: <?php echo $this->_vars['info']['totalRows']; ?>
]</span>
    		</div>
		<?php endif; ?>
		
		<div class="pagination right">
			<?php echo $this->_vars['lang']['ITEMSOERPAGE']; ?>
: 
			<select OnChange="addPager(this.value)">
				<?php if (count((array)$this->_vars['info']['limitOptions'])): foreach ((array)$this->_vars['info']['limitOptions'] as $this->_vars['key'] => $this->_vars['value']): ?>
    				<option value="<?php echo $this->_vars['key']; ?>
" <?php if ($this->_vars['key'] == $this->_vars['info']['rowsPerPage']): ?>selected<?php endif; ?>><?php echo $this->_vars['value']; ?>
</option>
  				<?php endforeach; endif; ?>
  			</select>
		</div>
	
	</div> 
				
	<div class="bendl"></div>
	<div class="bendr"></div>
</div>

<iframe name="_systemfr" style="display:none"></iframe>
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			










<table id="tbllist" class="ka_table" border="0" width="100%"  cellpadding="0" cellspacing="1">

	
	



<div class="menucontainerbottom">
  
  
  
  <?php if ($this->_vars['info']['backlink'] != ''): ?>
  	<div class="backlink" >
  	<img src="<?php echo $this->_vars['info']['baseurl']; ?>
images/dbadmin_back.gif" border="0" hspace="2" style="vertical-align: bottom" />
  	<a href="<?php echo $this->_vars['info']['backlink']; ?>
" class="db_link"><?php echo $this->_vars['lang']['BUTTON_BACK']; ?>
</a></div>
  <?php endif; ?>

  
  

 

</div>


</FORM>



