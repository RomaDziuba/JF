<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-20 02:52:42 EET */ ?>

<script>
<?php echo '
jQuery(document).ready(function() { 
    initPopup();
});
'; ?>

</script>

<div id="dba_form">
    <iframe name="db_system" style="display:none" id="db_system"></iframe>
    <form method="post" style="display:inline" target="db_system" name="tbl_form" id="tbl_form" enctype="multipart/form-data" <?php if (constant('JIMBO_POPUP_MODE') != "popup"): ?>action="<?php echo $this->_vars['info']['url']; ?>
?action=<?php echo $_GET['action']; ?>
&ID=<?php echo $_GET['ID']; ?>
"<?php endif; ?>>
        <input type="hidden" name="picker" id="picker" />
         
        <table class="jform" cellpadding="0" cellspacing="0" border="0"  width="100%" align="center" bgcolor="#EDF4FF">
            <thead>
                <tr>
                    <td class="caption" align="left" width="100%" nowrap="nowrap"><b><?php echo $this->_vars['info']['caption']; ?>
</b></td>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td align="center" valign="top">
                
                    <table border="0" cellspacing="0" cellpadding="0" width="100%">
                        <?php if ($this->_vars['info']['hint']): ?>
                            <tr><td colspan="2" align="center"><br/><?php echo $this->_vars['info']['hint']; ?>
<br/><br/></td></tr>
                            <tr><td colspan="7" height="1" bgcolor="#e2e6eb"></td></tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="2" valign="top">
                                <div class="formRows">
                                    <table border="0" cellspacing="0" cellpadding="0" width="100%"  id="tblform" align="center" style="width:650px;">
                                        <?php if (count((array)$this->_vars['items'])): foreach ((array)$this->_vars['items'] as $this->_vars['item']): ?>  
                                            <tr class="ka_row">
                                                <td  style="padding-right:10px;" width="200px" align="right"><?php echo $this->_vars['item']['caption']; ?>
:</td>
                                                <td align="left"><?php echo $this->_vars['item']['input']; ?>

                                                    <?php if ($this->_vars['item']['required']): ?>
                                                        <img src="<?php echo $this->_vars['info']['httproot']; ?>
images/required.gif" />
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                </td>
            </tr>
            <tr id="form_actions" class="ka_odd">
                <td align="center"  >
                    <div>
                        <?php if ($this->_vars['info']['actionbutton'] != ''): ?>
                            <input type="submit" class="lbutton" value="<?php echo $this->_vars['info']['actionbutton']; ?>
"  />
                        <?php endif; ?>
                        <input type="button" class="lbutton" value="<?php echo $this->_vars['lang']['BUTTON_BACK']; ?>
" onClick="window.close()" />
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <?php if ($this->_vars['info']['token']): ?> 
            <input type="hidden" name="__token" value="<?php echo $this->_vars['info']['token']; ?>
" />
        <?php endif; ?>
    
        <input type="hidden" name="performPost" value="<?php echo $this->_vars['info']['action']; ?>
" />
    </form>
</div>


<?php echo '
<script>

$.fn.qtip.styles.mystyle = {
   width: 300,
   background: \'#CCCCCC\',
   color: \'black\',
   textAlign: \'center\',
   border: {
      width: 5,
      radius: 3,
      color: \'#CCCCCC\'
   },
   tip: \'bottomLeft\',
   name: \'dark\' // Inherit the rest of the attributes from the preset dark style
}

function doTip(el, tip) {
	$(\'#\'+el).qtip({
   content: tip,
   show: \'focus\',
   hide: \'blur\',
   position: {
   	 corner: {
         target: \'topLeft\',
         tooltip: \'bottomLeft\'
      }
   },
	style: { 
      name: \'mystyle\'
   }
});

}

'; ?>

<?php if (count((array)$this->_vars['qtips'])): foreach ((array)$this->_vars['qtips'] as $this->_vars['key'] => $this->_vars['value']): ?>
doTip('<?php echo $this->_vars['key']; ?>
', '<?php echo $this->_vars['value']; ?>
') 
<?php endforeach; endif; ?>
</script>
