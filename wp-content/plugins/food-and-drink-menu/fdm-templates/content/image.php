<?php
global $fdm_controller;

echo ( $fdm_controller->settings->get_setting('fdm-pro-style') == 'image' ? '<div class="fdm-image-style-image-wrapper">' : '');
?>
<img class="fdm-item-image" src="<?php echo esc_attr( $this->image ); ?>" title="<?php echo esc_attr( $this->title ); ?>" alt="<?php echo esc_attr( $this->title ); ?>">
<?php
echo ( $fdm_controller->settings->get_setting('fdm-pro-style') == 'image' ? '</div>' : '');

