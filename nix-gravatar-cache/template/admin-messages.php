	<?php
	foreach ( $messages as $type => $contents ) {?>
		<?php if( $type == 'error' ) : ?>

			<div class="<?php echo $type ?> fade">
				<?php foreach ( $contents as $content ) {?>
					<p>
						<strong><?php echo $nfgc->plugin_name ?>: </strong>
						<?php echo $content; ?>
					</p>
				<?php } ?>
			</div>

		 <?php elseif ( $type != 'error' && $nfgc->admin_help_notice() ) : ?>

			<div class="updated fade">
				<?php foreach ( $contents as $content ) {?>
					<p>
						<strong><?php echo ( $type == 'info' ) ? '' : ucfirst($type).': '; ?></strong>
						<?php echo $content; ?>
					</p>
				<?php } ?>
			</div>

		<?php endif ?>
	<?php }