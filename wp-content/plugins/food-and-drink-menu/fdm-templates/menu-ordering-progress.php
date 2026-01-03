<?php 
	global $fdm_controller;

	$order_statuses = fdm_get_order_statuses();
	$id = $fdm_controller->orders->get_recent_order_id();
	$recent_status = $id ? get_post_status( $id ) : 'fdm_order_received';

?>
<div <?php echo fdm_format_classes( $this->classes ); ?> data-order_id='<?php echo esc_attr( $id ); ?>'>

	<h3 id='fdm-ordering-progress-header'>
		<?php echo esc_html( $this->get_label( 'label-order-progress' ) ); ?>
	</h3>

	<div id='fdm-ordering-progress-show-progress'>

		<div class='fdm-order-progress-status' data-value='<?php echo esc_attr( $order_statuses[ $recent_status ]['value'] ); ?>' ></div>

		<?php foreach ( $order_statuses as $status_name => $order_status ) { ?>

			<div class='fdm-order-progress-status-labels <?php echo $recent_status == $status_name ? 'fdm-order-progress-current-status' : ''; ?>' data-status='<?php echo esc_attr( htmlspecialchars( $status_name, ENT_QUOTES, 'UTF-8' ) ); ?>'><?php echo esc_html( $order_status['label'] ); ?></div>

		<?php }	?>

	</div>
</div>