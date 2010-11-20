<?php /* V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL. 2010-11-20 02:42:46 EET */ ?>

<ul id="nav">
	<?php if (count((array)$this->_vars['items'])): foreach ((array)$this->_vars['items'] as $this->_vars['first']): ?>
		<li><a <?php if ($this->_vars['first']['href']): ?>href="<?php echo $this->_vars['first']['href']; ?>
"<?php else: ?> href="#" onclick="return false;"<?php endif; ?>"><?php echo $this->_vars['first']['caption']; ?>
</a>
		<?php if (! empty ( $this->_vars['first']['items'] )): ?>
			<ul>
			<?php if (count((array)$this->_vars['first']['items'])): foreach ((array)$this->_vars['first']['items'] as $this->_vars['second']): ?>
				<li><a <?php if ($this->_vars['second']['href']): ?>href="<?php echo $this->_vars['second']['href']; ?>
"<?php else: ?> href="#" onclick="return false;"<?php endif; ?>"><?php echo $this->_vars['second']['caption']; ?>
</a>
				<?php if (! empty ( $this->_vars['second']['items'] )): ?>
        			<ul>
        			<?php if (count((array)$this->_vars['second']['items'])): foreach ((array)$this->_vars['second']['items'] as $this->_vars['last']): ?>
        				<li><a <?php if ($this->_vars['last']['href']): ?>href="<?php echo $this->_vars['last']['href']; ?>
"<?php else: ?> href="#" onclick="return false;"<?php endif; ?>"><?php echo $this->_vars['last']['caption']; ?>
</a></li>
        			<?php endforeach; endif; ?>
        			</ul>
        		<?php endif; ?>
				</li>
			<?php endforeach; endif; ?>
			</ul>
		<?php endif; ?>
		</li>
	<?php endforeach; endif; ?>
</ul>