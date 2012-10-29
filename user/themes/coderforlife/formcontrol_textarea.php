<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<div<?php echo( isset( $id ) ? " id=\"$id\"" : '' ); ?>>
<?php if (isset($label_title)) { echo '<p>',$label_title,'</p>'; } ?>
<!--<label<?php if ( isset( $label_title ) ) { ?> title="<?php echo $label_title; ?>"<?php $label_title = ''; } else { echo ( isset( $title ) ? "  title=\"$title\"" : '' ); } ?> for="<?php echo $field; ?>"><?php echo $this->caption; ?></label>-->
<textarea name="<?php echo $field; ?>" id="<?php echo $field; ?>"<?php echo ( isset( $class ) ? " class=\"$class\"" : '' ) . 
	" rows=\"" . ( isset( $rows ) ? $rows : 8 ) . "\" cols=\"" . ( isset( $cols ) ? $cols : 85 );
	if ( isset( $control_title ) ) { echo "\" title=\"$control_title\""; } else { echo ( isset( $title ) ? "\" title=\"$title\"" : '"' ); } if ( isset( $tabindex ) ) { ?> tabindex="<?php echo $tabindex; ?>"<?php } ?>><?php 
echo Utils::htmlspecialchars( $value ); ?></textarea>
<?php if ( $message != '' ) : ?>
	<p class="error"><?php echo $message; ?></p>
<?php endif; ?>
</div>
