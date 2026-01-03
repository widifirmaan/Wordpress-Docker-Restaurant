<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( !class_exists( 'fdmOrdersTable' ) ) {
/**
 * Orders Table Class
 *
 * Extends WP_List_Table to display the list of orders in a format similar to
 * the default WordPress post tables.
 *
 * @h/t Easy Digital Downloads by Pippin: https://easydigitaldownloads.com/
 * @since 2.1.0
 */
class fdmOrdersTable extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $per_page = 3;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $base_url;

	/**
	 * Array of order counts by total and status
	 *
	 * @var array
	 * @since 2.1.0
	 */
	public $order_counts;

	/**
	 * Array of orders
	 *
	 * @var array
	 * @since 2.1.0
	 */
	public $orders;

	/**
	 * Current date filters
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $filter_start_date = null; // NEEDED?
	public $filter_end_date = null; // NEEDED?

	/**
	 * Current location filter
	 *
	 * @var int
	 * @since 1.6
	 */
	public $filter_location = 0; 

	/**
	 * Current query string
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $query_string;

	/**
	 * Results of a bulk or quick action
	 *
	 * @var array
	 * @since 2.1.0
	 */
	public $action_result = array();

	/**
	 * Type of bulk or quick action last performed
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $last_action = '';

	/**
	 * Stored reference to visible columns
	 *
	 * @var string
	 * @since 2.1.0
	 */
	public $visible_columns = array();

	/**
	 * Initialize the table and perform any requested actions
	 *
	 * @since 2.1.0
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __( 'Order', 'food-and-drink-menu' ),
			'plural'    => __( 'Orders', 'food-and-drink-menu' ),
			'ajax'      => false
		) );

		// Set the date filter
		$this->set_date_filter();

		// Strip unwanted query vars from the query string or ensure the correct
		// vars are used
		$this->query_string_maintenance();

		// Run any bulk action requests
		$this->process_bulk_action();

		// Run any quicklink requests
		$this->process_quicklink_action();

		// Retrieve a count of the number of orders by status
		$this->get_order_counts();

		// Retrieve orders data for the table
		$this->orders_data();

		$this->base_url = admin_url( 'admin.php?page=' . FDM_ORDER_POST_TYPE );

		// Add default items to the details column if they've been hidden
		add_filter( 'fdm_orders_table_column_details', array( $this, 'add_details_column_items' ), 10, 2 );
	}

	/**
	 * Set the correct date filter
	 *
	 * $_POST values should always overwrite $_GET values
	 *
	 * @since 2.1.0
	 */
	public function set_date_filter( $start_date = null, $end_date = null) {

		if ( !empty( $_GET['action'] ) && $_GET['action'] == 'clear_date_filters' ) {
			$this->filter_start_date = null;
			$this->filter_end_date = null;
		}

		$this->filter_start_date = $start_date;
		$this->filter_end_date = $end_date;

		if ( $start_date === null ) {
			$this->filter_start_date = ! empty( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : null;
			$this->filter_start_date = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : $this->filter_start_date;
		}

		if ( $end_date === null ) {
			$this->filter_end_date = ! empty( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : null;
			$this->filter_end_date = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : $this->filter_end_date;
		}
	}

	/**
	 * Get the current date range
	 *
	 * @since 2.1.0
	 */
	public function get_current_date_range() {

		$range = empty( $this->filter_start_date ) ? _x( '*', 'No date limit in a date range, eg 2014-* would mean any date from 2014 or after', 'food-and-drink-menu' ) : $this->filter_start_date;
		$range .= empty( $this->filter_start_date ) || empty( $this->filter_end_date ) ? '' : _x( '&mdash;', 'Separator between two dates in a date range', 'food-and-drink-menu' );
		$range .= empty( $this->filter_end_date ) ? _x( '*', 'No date limit in a date range, eg 2014-* would mean any date from 2014 or after', 'food-and-drink-menu' ) : $this->filter_end_date;

		return $range;
	}

	/**
	 * Strip unwanted query vars from the query string or ensure the correct
	 * vars are passed around and those we don't want to preserve are discarded.
	 *
	 * @since 2.1.0
	 */
	public function query_string_maintenance() {

		$this->query_string = remove_query_arg( array( 'action', 'start_date', 'end_date' ) );

		if ( $this->filter_start_date !== null ) {
			$this->query_string = add_query_arg( array( 'start_date' => $this->filter_start_date ), $this->query_string );
		}

		if ( $this->filter_end_date !== null ) {
			$this->query_string = add_query_arg( array( 'end_date' => $this->filter_end_date ), $this->query_string );
		}

		$this->filter_location = !isset( $_GET['location'] ) ? 0 : intval( $_GET['location'] );
		$this->filter_location = !isset( $_POST['location'] ) ? $this->filter_location : intval( $_POST['location'] );
		$this->query_string = remove_query_arg( 'location', $this->query_string );
		if ( !empty( $this->filter_location ) ) {
			$this->query_string = add_query_arg( array( 'location' => $this->filter_location ), $this->query_string );
		}

	}

	/**
	 * Show the time views, date filters and the search box
	 * @since 2.1.0
	 */
	public function advanced_filters() {

		// Show the date_range views (today, upcoming, all)
		if ( !empty( $_GET['date_range'] ) ) {
			$date_range = sanitize_text_field( $_GET['date_range'] );
		} else {
			$date_range = '';
		}

		// Use a custom date_range if a date range has been entered
		if ( $this->filter_start_date !== null || $this->filter_end_date !== null ) {
			$date_range = 'custom';
		}

		// Strip out existing date filters from the date_range view urls
		$date_range_query_string = remove_query_arg( array( 'date_range', 'start_date', 'end_date' ), $this->query_string );

		/*$views = array( // NEEDED?
			'upcoming'	=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'paged' => FALSE ), remove_query_arg( array( 'date_range' ), $date_range_query_string ) ) ), $date_range === '' ? ' class="current"' : '', __( 'Upcoming', 'food-and-drink-menu' ) ),
			'today'	    => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'date_range' => 'today', 'paged' => FALSE ), $date_range_query_string ) ), $date_range === 'today' ? ' class="current"' : '', __( 'Today', 'food-and-drink-menu' ) ),
			'past'	    => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'date_range' => 'past', 'paged' => FALSE ), $date_range_query_string ) ), $date_range === 'past' ? ' class="current"' : '', __( 'Past', 'food-and-drink-menu' ) ),
			'all'		=> sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'date_range' => 'all', 'paged' => FALSE ), $date_range_query_string ) ), $date_range == 'all' ? ' class="current"' : '', __( 'All', 'food-and-drink-menu' ) ),
		);

		if ( $date_range == 'custom' ) { // NEEDED? This whole section might be jettison-able
			$views['date'] = '<span class="date-filter-range current">' . $this->get_current_date_range() . '</span>';
			$views['date'] .= '<a id="rtb-date-filter-link" href="#"><span class="dashicons dashicons-calendar"></span> <span class="rtb-date-filter-label">Change date range</span></a>';
		} else {
			$views['date'] = '<a id="rtb-date-filter-link" href="#">' . esc_html__( 'Between dates', 'food-and-drink-menu' ) . '</a>';
		}

		$views = apply_filters( 'fdm_orders_table_views_date_range', $views ); */
		?>

		<div id="fdm-filters">
			<?php /*<ul class="subsubsub fdm-views-date_range">
				<li><?php echo join( ' | </li><li>', $views ); ?></li>
			</ul>

			<div class="date-filters">
				<label for="start-date" class="screen-reader-text"><?php _e( 'Start Date:', 'food-and-drink-menu' ); ?></label>
				<input type="text" id="start-date" name="start_date" class="datepicker" value="<?php echo esc_attr( $this->filter_start_date ); ?>" placeholder="<?php _e( 'Start Date', 'food-and-drink-menu' ); ?>" />
				<label for="end-date" class="screen-reader-text"><?php _e( 'End Date:', 'food-and-drink-menu' ); ?></label>
				<input type="text" id="end-date" name="end_date" class="datepicker" value="<?php echo esc_attr( $this->filter_end_date ); ?>" placeholder="<?php _e( 'End Date', 'food-and-drink-menu' ); ?>" />
				<input type="submit" class="button button-secondary" value="<?php _e( 'Apply', 'food-and-drink-menu' ); ?>"/>
				<?php if( !empty( $this->filter_start_date ) || !empty( $this->filter_end_date ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'clear_date_filters' ) ) ); ?>" class="button button-secondary"><?php _e( 'Clear Filter', 'food-and-drink-menu' ); ?></a>
				<?php endif; ?>
			</div> */ ?>

			<?php if( !empty( $_GET['status'] ) ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_text_field( $_GET['status'] ) ); ?>"/>
			<?php endif; ?>
		</div>

<?php
	}

	/**
	 * Retrieve the view types
	 * @since 2.1.0
	 */
	public function get_views() {

		$current = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		$views = array( 'all' => sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( array( 'status', 'paged' ), $this->query_string ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'food-and-drink-menu' ) . ' <span class="count">(' . $this->order_counts['total'] . ')</span>' ) );
			
		$order_statuses = fdm_get_order_statuses();
		foreach ( $order_statuses as $order_status => $status_object ) { 
			$views[ $order_status ] = sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'status' => $order_status, 'paged' => FALSE ), $this->query_string ) ), $current === $order_status ? ' class="current"' : '', $status_object['label'] . ' <span class="count">(' . $this->order_counts[ $order_status ] . ')</span>' );

		}

		$views['trash'] = sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( array( 'status' => 'trash', 'paged' => FALSE ), $this->query_string ) ), $current === 'trash' ? ' class="current"' : '', __( 'Trash', 'food-and-drink-menu' ) . ' <span class="count">(' . $this->order_counts['trash'] . ')</span>' );

		return apply_filters( 'fdm_orders_table_views_status', $views );
	}

	/**
	 * Generates content for a single row of the table
	 * @since 2.1.0
	 */
	public function single_row( $item ) {
		static $row_alternate_class = '';
		$row_alternate_class = ( $row_alternate_class == '' ? 'alternate' : '' );

		$row_classes = array( esc_attr( $item->post_status ) );

		if ( !empty( $row_alternate_class ) ) {
			$row_classes[] = $row_alternate_class;
		}

		$row_classes = apply_filters( 'fdm_admin_orders_list_row_classes', $row_classes, $item );

		echo '<tr class="' . implode( ' ', $row_classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 2.1.0
	 */
	public function get_columns() {
		global $fdm_controller;

		// Prevent the lookup from running over and over again on a single
		// page load
		if ( !empty( $this->visible_columns ) ) {
			return $this->visible_columns;
		}

		$all_default_columns = $this->get_all_default_columns();
		$all_columns = $this->get_all_columns();

		$visible_columns = $fdm_controller->settings->get_setting( 'orders-table-columns' );

		if ( empty( $visible_columns ) ) {
			$columns = $all_default_columns;
		} else {
			$columns = array();
			$columns['cb'] = $all_default_columns['cb'];
			$columns['date'] = $all_default_columns['date'];

			foreach( $all_columns as $key => $column ) {
				if ( in_array( $key, $visible_columns ) ) {
					$columns[$key] = $all_columns[$key];
				}
			}
			$columns['details'] = $all_default_columns['details'];
		}

		$this->visible_columns = apply_filters( 'fdm_orders_table_columns', $columns );

		return $this->visible_columns;
	}

	/**
	 * Retrieve all default columns
	 *
	 * @since 2.1.0
	 */
	public function get_all_default_columns() {
		global $fdm_controller;

		$columns = array(
			'cb'        	=> '<input type="checkbox" />', //Render a checkbox instead of text\
			'id' 			=> __( 'ID', 'food-and-drink-menu' ),
			'status' 		=> __( 'Status', 'food-and-drink-menu' ),
			'date'   		=> __( 'Date', 'food-and-drink-menu' ),
			'name'   		=> __( 'Name', 'food-and-drink-menu' ),
			'email'  		=> __( 'Email', 'food-and-drink-menu' ),
			'phone' 		=> __( 'Phone', 'food-and-drink-menu' ),
			'items'  		=> __( 'Items', 'food-and-drink-menu' ),
			'note'   		=> __( 'Note', 'food-and-drink-menu' ),
			'custom_fields' => __( 'Custom Fields', 'food-and-drink-menu' )
		);

		if ( $fdm_controller->settings->get_setting( 'enable-payment' ) ) {
			$columns['payment_amount'] = __( 'Payment Amount', 'food-and-drink-menu' );
		}

		return $columns;
	}

	/**
	 * Retrieve all available columns
	 *
	 * This is used to get all columns including those deactivated and filtered
	 * out via get_columns().
	 *
	 * @since 2.1.0
	 */
	public function get_all_columns() {
		$columns = $this->get_all_default_columns();

		return apply_filters( 'fdm_orders_all_table_columns', $columns );
	}

	/**
	 * Retrieve the table's sortable columns
	 * @since 2.1.0
	 */
	public function get_sortable_columns() {
		$columns = array(
			'id' 		=> array( 'ID', true ),
			'date' 		=> array( 'date', true ),
			'name' 		=> array( 'title', true )
		);
		return apply_filters( 'fdm_orders_table_sortable_columns', $columns );
	}

	/**
	 * This function renders most of the columns in the list table.
	 * @since 2.1.0
	 */
	public function column_default( $order, $column_name ) {
		global $fdm_controller;

		switch ( $column_name ) {

			case 'date' :

				$value = $order->format_date( $order->date );
				break;

			case 'id' :

				$value = $order->ID;

				if ( $order->post_status !== 'trash' ) {
					
					$value .= '<div class="actions">';
					$value .= '<a href="admin.php?page=fdm-add-edit-order&order_id=' . $order->id . '" data-id="' . esc_attr( $order->id ) . '">' . __( 'Edit', 'order-tracking' ) . '</a>';
					$value .= '</div>';
				}

				break;

			case 'phone' :

				$value = $order->phone;
				break;

			case 'name' :

				$value = esc_html( $order->name );
				break;

			case 'email' :

				$value = esc_html( $order->email ); // @to-do: allow emailing order customer from admin screen
//				$value .= '<div class="actions">';
//				$value .= '<a href="#" data-id="' . esc_attr( $order->ID ) . '" data-action="email" data-email="' . esc_attr( $order->email ) . '" data-name="' . esc_attr( $order->name ) . '">' . __( 'Send Email', 'food-and-drink-menu' ) . '</a>';
//				$value .= '</div>';
				break;

			case 'note' :

				$value = esc_html( $order->note );
				break;

			case 'custom_fields' :

				$value = '';

				$custom_fields = $fdm_controller->settings->get_ordering_custom_fields();

				foreach ( $custom_fields as $custom_field ) {

					if ( empty( $order->custom_fields[ $custom_field->slug ] ) ) { continue; }

					$value .= $custom_field->name . ': ' . ( is_array( $order->custom_fields[ $custom_field->slug ] ) ? implode( ',', $order->custom_fields[ $custom_field->slug ] ) : esc_html( $order->custom_fields[ $custom_field->slug ] ) ) . '<br />';
				}
				
				break;
			
			case 'payment_amount' :

				$value = esc_html( fdm_format_price( $order->payment_amount ) );

				$value .= ( isset( $order->stripe_payment_hold_status ) and $order->stripe_payment_hold_status == 'hold-placed' ) ? __( ' (on hold)', 'food-and-drink-menu' ) : '';
				
				break;				

			case 'items' :

				$order_items = $order->get_order_items();

				$order_total_price = 0;

				$value = '<div class="fdm-admin-order-items">';
				foreach ( $order_items as $order_item ) {
					$order_item->title = get_the_title( $order_item->id );

					$ordering_options = get_post_meta( $order_item->id, '_fdm_ordering_options', true );
					$ordering_options = is_array( $ordering_options ) ? $ordering_options : array();

					$item_price = fdm_calculate_admin_price( $order_item );
					$order_total_price += $item_price;

					$value .= '<div class="fdm-admin-order-item">';
					
					// Item Name
					$value .= '<span class="fdm-admin-order-item-name">' . $order_item->title . '</span>';

					//Item Options
					if ( ! empty( $order_item->selected_options ) ) { $value .= '<ul>'; }
					
					foreach ( $order_item->selected_options as $selected_option ) {
						
						if ( ! array_key_exists( $selected_option, $ordering_options ) ) { continue; }
						
						$value .= '<li>' . $ordering_options[ $selected_option ]['name'] . '</li>';
					}

					if ( ! empty( $order_item->selected_options ) ) { $value .= '</ul>'; }

					// Item Quantity
					if ( property_exists( $order_item, 'quantity' ) && ! empty( $order_item->quantity ) ) {
						$value .= "
							<div class='fdm-admin-order-item-qty-wrapper'>
								<span class='label'>" . __( 'Quantity:', 'food-and-drink-menu') . " </span><span class='value'>{$order_item->quantity}</span>
							</div>
						";
					}
					
					// Item Note
					if ( $order_item->note != '' ) { $value .= '<span class="fdm-admin-order-item-note">' . $order_item->note . '</span>'; }

					// Item Price
					$value .= '
						<div class="fdm-admin-order-item-price">
							<span class="label">' . __( 'Price:', 'food-and-drink-menu') . ' </span><span class="value">' . fdm_format_price( $item_price ) . '</span>
						</div>
					';
					
					$value .= '</div>';
				}

				$value .= '
					<div class="fdm-admin-order-total-price">
						<span class="label">' . __( 'Total:', 'food-and-drink-menu' ) . ' </span><span class="value">' . fdm_format_price( fdm_add_tax_to_price( $order_total_price ) ) . '</span>
					</div>
				';

				$value .= '</div>';
				break;

			case 'status' :

				$order_statuses = fdm_get_order_statuses();
				if ( !empty( $order_statuses[$order->post_status] ) ) {
					$value = $order_statuses[$order->post_status]['label'];
				} elseif ( $order->post_status == 'trash' ) {
					$value = _x( 'Trash', 'Status label for orders put in the trash', 'food-and-drink-menu' );
				} else {
					$value = $order->post_status;
				}
				break;

			/*case 'details' :

				$value = '';

				$details = array();
				if ( trim( $booking->message ) ) {
					$details[] = array(
						'label' => __( 'Message', 'food-and-drink-menu' ),
						'value' => esc_html( $booking->message ),
					);
				}

				$details = apply_filters( 'fdm_orders_table_column_details', $details, $booking );

				if ( !empty( $details ) ) {
					$value = '<a href="#" class="rtb-show-details" data-id="details-' . esc_attr( $booking->ID ) . '"><span class="dashicons dashicons-testimonial"></span></a>';
					$value .= '<div class="rtb-details-data"><ul class="details">';
					foreach( $details as $detail ) {
						$value .= '<li><div class="label">' . $detail['label'] . '</div><div class="value">' . $detail['value'] . '</div></li>';
					}
					$value .= '</ul></div>';
				}
				break;*/

			default:

				$value = isset( $order->$column_name ) ? $order->$column_name : '';
				break;

		}

		return apply_filters( 'fdm_orders_table_column', $value, $order, $column_name );
	}

	/**
	 * Render the checkbox column
	 * @since 2.1.0
	 */
	public function column_cb( $order ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'orders',
			$order->ID
		);
	}

	/**
	 * Add hidden columns values to the details column
	 *
	 * This only handles the default columns. Custom data needs to hook in and
	 * add it's own items to the $details array.
	 *
	 * @since 2.1.0
	 */
	/* public function add_details_column_items( $details, $order ) {
		global $rtb_controller;
		$visible_columns = $this->get_columns();
		$all_columns = $this->get_all_columns();

		$detail_columns = array_diff( $all_columns, $visible_columns );

		foreach( $detail_columns as $key => $label ) {

			$value = $this->column_default( $booking, $key );
			if ( empty( $value ) ) {
				continue;
			}

			$details[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $details;
	}*/

	/**
	 * Retrieve the bulk actions
	 * @since 2.1.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete'             	=> __( 'Delete',                'food-and-drink-menu' ),
			'set-status-received'	=> __( 'Set To Received',       'food-and-drink-menu' ),
			'set-status-accepted'	=> __( 'Set To Accepted',       'food-and-drink-menu' ),
			'set-status-preparing' 	=> __( 'Set To Preparing', 		'food-and-drink-menu' ),
			'set-status-ready' 		=> __( 'Set To Ready',      	'food-and-drink-menu' )
		);

		return apply_filters( 'fdm_orders_table_bulk_actions', $actions );
	}

	/**
	 * Process the bulk actions
	 * @since 2.1.0
	 */
	public function process_bulk_action() {
		global $fdm_controller;

		$ids    = isset( $_POST['orders'] ) ? $_POST['orders'] : false;
		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		array_walk( $ids, 'intval' );

		// Check bulk actions selector below the table
		$action = $action == '-1' && isset( $_POST['action2'] ) ? $_POST['action2'] : $action;

		if( empty( $action ) || $action == '-1' ) {
			return;
		}

		if ( ! current_user_can( 'manage_fdm_orders' ) ) {
			return;
		}

		$results = array();
		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$results[$id] = $fdm_controller->orders->delete_order( $id );
			}

			if ( 'set-status-received' === $action ) {
				$results[$id] = $fdm_controller->orders->update_order_status( $id, 'fdm_order_received' );
			}

			if ( 'set-status-accepted' === $action ) {
				$results[$id] = $fdm_controller->orders->update_order_status( $id, 'fdm_order_accepted' );
			}

			if ( 'set-status-preparing' === $action ) {
				$results[$id] = $fdm_controller->orders->update_order_status( $id, 'fdm_order_preparing' );
			}

			if ( 'set-status-ready' === $action ) {
				$results[$id] = $fdm_controller->orders->update_order_status( $id, 'fdm_order_ready' );
			}

			$results = apply_filters( 'fdm_orders_table_bulk_action', $results, $id, $action );
		}

		if( count( $results ) ) {
			$this->action_result = $results;
			$this->last_action = $action;
			add_action( 'fdm_orders_table_top', array( $this, 'admin_notice_bulk_actions' ) );
		}
	}

	/**
	 * Process quicklink actions sent out in notification emails
	 * @since 2.1.0
	 */
	public function process_quicklink_action() {
		global $fdm_controller;

		if ( empty( $_REQUEST['fdm-quicklink'] ) ) {
			return;
		}

		if ( !current_user_can( 'manage_fdm_orders' ) ) {
			return;
		}

		$results = array();

		$id = !empty( $_REQUEST['order'] ) ? intval( $_REQUEST['order'] ) : false;

		if ( $_REQUEST['fdm-quicklink'] == 'accept' ) {
			$results[$id] = $rtb_controller->orders->update_order_status( $id, 'fdm_order_accepted' );
			$this->last_action = 'set-status-confirmed';
		}

		if( count( $results ) ) {
			$this->action_result = $results;
			add_action( 'fdm_orders_table_top', array( $this, 'admin_notice_bulk_actions' ) );
		}
	}

	/**
	 * Display an admin notice when a bulk action is completed
	 * @since 2.1.0
	 */
	public function admin_notice_bulk_actions() {

		$success = 0;
		$failure = 0;
		foreach( $this->action_result as $id => $result ) {
			if ( $result === true || $result === null ) {
				$success++;
			} else {
				$failure++;
			}
		}

		if ( $success > 0 ) :
		?>

		<div id="fdm-admin-notice-bulk-<?php esc_attr( $this->last_action ); ?>" class="updated">

			<?php if ( $this->last_action == 'delete' ) : ?>
			<p><?php echo sprintf( _n( '%d order deleted successfully.', '%d orders deleted successfully.', $success, 'food-and-drink-menu' ), $success ); ?></p>

			<?php elseif ( $this->last_action == 'set-status-accepted' ) : ?>
			<p><?php echo sprintf( _n( '%d order accepted.', '%d orders accepted.', $success, 'food-and-drink-menu' ), $success ); ?></p>

			<?php elseif ( $this->last_action == 'set-status-preparing' ) : ?>
			<p><?php echo sprintf( _n( '%d order set to preparing.', '%d orders set to preparing.', $success, 'food-and-drink-menu' ), $success ); ?></p>

			<?php elseif ( $this->last_action == 'set-status-ready' ) : ?>
			<p><?php echo sprintf( _n( '%d order ready.', '%d orders ready.', $success, 'food-and-drink-menu' ), $success ); ?></p>

			<?php endif; ?>
		</div>

		<?php
		endif;

		if ( $failure > 0 ) :
		?>

		<div id="fdm-admin-notice-bulk-<?php esc_attr( $this->last_action ); ?>" class="error">
			<p><?php echo sprintf( _n( '%d order had errors and could not be processed.', '%d orders had errors and could not be processed.', $failure, 'food-and-drink-menu' ), $failure ); ?></p>
		</div>

		<?php
		endif;
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * This outputs a separate set of options above and below the table, in
	 * order to make room for the locations.
	 *
	 * @since 1.6
	 */
	public function display_tablenav( $which ) {

		global $fdm_controller;

		// Just call the parent method if locations aren't activated
		if ( 'top' === $which && empty( $fdm_controller->locations->post_type ) ) {
			$this->add_notification();
			parent::display_tablenav( $which );
			return;
		}

		// Just call the parent method for the bottom nav
		if ( 'bottom' == $which ) {
			parent::display_tablenav( $which );
			return;
		}

/*
		$locations = $rtb_controller->locations->get_location_options();
		$all_locations = $rtb_controller->locations->get_location_options( false );
		$inactive_locations = array_diff( $all_locations, $locations );
		?>

		<div class="tablenav top rtb-top-actions-wrapper">
			<?php wp_nonce_field( 'bulk-' . $this->args['plural'] ); ?>
			<?php $this->extra_tablenav( $which ); ?>
		</div>

		<?php $this->add_notification(); ?>

		<div class="rtb-table-header-controls">
			<?php if ( $this->has_items() ) : ?>
				<div class="actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif; ?>
			<ul class="rtb-locations">
				<li<?php if ( empty( $this->filter_location ) ) : ?> class="current"<?php endif; ?>>
					<a href="<?php echo esc_url( remove_query_arg( 'location', $this->query_string ) ); ?>"><?php esc_html_e( 'All Locations', 'food-and-drink-menu' ); ?></a>
				</li>
				<?php
					$i = 0;
					foreach( $locations as $term_id => $name ) :
						if ( $i > 15 ) {
							break;
						} else {
							$i++;
						}
						?>

						<li<?php if ( $this->filter_location == $term_id ) : ?> class="current"<?php endif; ?>>
							<a href="<?php echo esc_url( add_query_arg( 'location', $term_id, $this->query_string ) ); ?>">
								<?php esc_html_e( $name ); ?>
							</a>
						</li>
				<?php endforeach; ?>
			</ul>
			<div class="rtb-location-switch">
				<select name="location">
					<option><?php esc_attr_e( 'All Locations', 'food-and-drink-menu' ); ?></option>
					<?php foreach( $locations as $term_id => $name ) : ?>
						<option value="<?php esc_attr_e( $term_id ); ?>"<?php if ( $this->filter_location == $term_id ) : ?> selected="selected"<?php endif; ?>>
							<?php esc_attr_e( $name ); ?>
						</option>
					<?php endforeach; ?>
					<?php if ( !empty( $inactive_locations ) ) : ?>
						<optgroup label="<?php esc_attr_e( 'Inactive Locations' ); ?>">
							<?php foreach( $inactive_locations as $term_id => $name ) : ?>
								<option value="<?php esc_attr_e( $term_id ); ?>"<?php if ( $this->filter_location == $term_id ) : ?> selected="selected"<?php endif; ?>>
									<?php esc_attr_e( $name ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
				</select>
				<input type="submit" class="button rtb-locations-button" value="<?php esc_attr_e( 'Switch', 'food-and-drink-menu' ); ?>">
			</div>
		</div> 

		<?php */
	} 

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string pos Position of this tablenav: `top` or `btm`
	 * @since 2.1.0
	 */
	public function extra_tablenav( $pos ) {
		do_action( 'fdm_orders_table_actions', $pos );
	}

	/**
	 * Add notifications above the table to indicate which orders are
	 * being shown.
	 * @since 2.1.0
	 */
	public function add_notification() {

		$notifications = array();

		$order_statuses = fdm_get_order_statuses();

		$status = '';
		if ( !empty( $_GET['status'] ) ) {
			$status = sanitize_text_field( $_GET['status'] );
			if ( $status == 'trash' ) {
				$notifications['status'] = __( "You're viewing orders that have been moved to the trash.", 'food-and-drink-menu' );
			} elseif ( !empty( $order_statuses[ $status ] ) ) {
				$notifications['status'] = sprintf( _x( "You're viewing orders that have been marked as %s.", 'Indicates which order status is currently being filtered in the list of orders.', 'food-and-drink-menu' ), $order_statuses[ $status ]['label'] );
			}
		}

		/*if ( !empty( $this->filter_start_date ) || !empty( $this->filter_end_date ) ) {
			$notifications['date'] = sprintf( _x( 'Only orders from %s are being shown.', 'Notification of order date range, eg - orders from 2021-12-02-2021-12-05', 'food-and-drink-menu' ), $this->get_current_date_range() );
		} elseif ( !empty( $_GET['date_range'] ) && $_GET['date_range'] == 'today' ) {
			$notifications['date'] = __( "Only today's orders are being shown.", 'food-and-drink-menu' );
		} elseif ( empty( $_GET['date_range'] ) ) {
			$notifications['date'] = __( 'Only upcoming orders are being shown.', 'food-and-drink-menu' );
		}*/

		$notifications = apply_filters( 'fdm_admin_orders_table_filter_notifications', $notifications );

		if ( !empty( $notifications ) ) :
		?>

			<div class="fdm-notice <?php echo esc_attr( $status ); ?>">
				<?php echo join( ' ', $notifications ); ?>
			</div>

		<?php
		endif;
	}

	/**
	 * Retrieve the counts of orders
	 * @since 2.1.0
	 */
	public function get_order_counts() {

		global $wpdb;

		$where = "WHERE p.post_type = '" . FDM_ORDER_POST_TYPE . "' AND p.post_status != 'draft'";

		$join = '';
		if ( $this->filter_location ) {
			$join .= " LEFT JOIN $wpdb->term_relationships t ON (t.object_id=p.ID)";
			$where .= " AND t.term_taxonomy_id=" . absint( $this->filter_location );
		}

		$query = "SELECT p.post_status,count( * ) AS num_posts
			FROM $wpdb->posts p
			$join
			$where
			GROUP BY p.post_status
		";

		$count = $wpdb->get_results( $query, ARRAY_A );

		$this->order_counts = array();
		foreach ( get_post_stati() as $state ) {
			$this->order_counts[$state] = 0;
		}

		$this->order_counts['total'] = 0;
		foreach ( (array) $count as $row ) {
			$this->order_counts[$row['post_status']] = $row['num_posts'];
			$this->order_counts['total'] += $row['num_posts'];
		}

	}

	/**
	 * Retrieve all the data for all the orders
	 * @since 2.1.0
	 */
	public function orders_data() {

		$order_statuses = fdm_get_order_statuses();

		if ( ! empty( $_GET['status'] ) && in_array( $_GET['status'], array_keys( $order_statuses ) ) ) {

			$status = sanitize_text_field( $_GET['status'] );
		}
		else {
			$status = array_keys( $order_statuses );
		}

		$args = array(
			'post_type'			=> FDM_ORDER_POST_TYPE,
			'posts_per_page'	=> $this->per_page,
			'orderby'			=> ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'date',
			'order'				=> ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC',
			'paged'				=> max( ! empty( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1, 1 ),
			'post_status'		=> $status
		);

		$query = new WP_Query( $args );

		$query->args = apply_filters( 'fdm_orders_table_query_args', $query->args ); 

		$this->orders = array();

		foreach ( $query->get_posts() as $order_post ) {

			$order_item = new fdmOrderItem();
			$order_item->load( $order_post );

			$this->orders[] = $order_item;
		}
	}

	/**
	 * Setup the final data for the table
	 * @since 2.1.0
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->orders;

		$total_items   = empty( $_GET['status'] ) ? $this->order_counts['total'] : intval( $this->order_counts[$_GET['status']] );

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page )
			)
		);
	}

}
} // endif;
