<div class="fdm-menu-sidebar-section-title" id="<?php echo esc_attr( $this->current_section->slug ); ?>"><?php echo esc_html( $this->current_section->title ); ?></div>
<div class="fdm-menu-sidebar-section-description fdm-hidden" id="<?php echo esc_attr( $this->current_section->slug ); ?>"><?php echo wp_kses_post( $this->current_section->description ); ?></div>
