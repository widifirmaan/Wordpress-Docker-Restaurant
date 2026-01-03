<?php

/**
 * Class to handle everything related to the walk-through that runs on plugin activation
 */

if ( !defined( 'ABSPATH' ) )
	exit;

if (!class_exists('ComposerAutoloaderInit4618f5c41cf5e27cc7908556f031e4d4')) {require_once FDM_PLUGIN_DIR . '/lib/PHPSpreadsheet/vendor/autoload.php';}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class fdmImport {

	public $status;
	public $message;

	public function __construct() {
		add_action( 'admin_menu', array($this, 'register_install_screen' ));

		if ( isset( $_POST['fdmImport'] ) ) { add_action( 'admin_menu', array($this, 'import_menu_items' )); }
	}

	public function register_install_screen() {
		
		add_submenu_page( 
			'edit.php?post_type=fdm-menu', 
			'Import Menu', 
			'Import', 
			'manage_options', 
			'fdm-import', 
			array($this, 'display_import_screen') 
		);
	}

	public function display_import_screen() {
		global $fdm_controller;

		$import_permission = $fdm_controller->permissions->check_permission( 'import' );
		?>
		<div class='wrap'>
			<h2>Import</h2>
			<?php if ( $import_permission ) { ?> 
				<form method='post' enctype="multipart/form-data">
					<p>
						<label for="fdm_menu_items_spreadsheet"><?php _e( 'Spreadsheet Containing Menu Items', 'food-and-drink-menu' ) ?></label><br />
						<input name="fdm_menu_items_spreadsheet" type="file" value=""/>
					</p>
					<input type='submit' name='fdmImport' value='Import Menu Items' class='button button-primary' />
				</form>
			<?php } else { ?>
				<div class='fdm-premium-locked'>
					<a href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_admin_import_page" target="_blank">Upgrade</a> to the premium version to use this feature
				</div>
			<?php } ?>
		</div>
	<?php }

	public function import_menu_items() {
		global $fdm_controller;

		if ( ! current_user_can( 'edit_posts' ) ) { return; }

		$fields = $fdm_controller->settings->get_menu_item_custom_fields();

		$update = $this->handle_spreadsheet_upload();

		if ( $update['message_type'] != 'Success' ) :
			$this->status = false;
			$this->message =  $update['message'];

			add_action( 'admin_notices', array( $this, 'display_notice' ) );

			return;
		endif;

		$excel_url = FDM_PLUGIN_DIR . '/user-sheets/' . $update['filename'];

	    // Build the workbook object out of the uploaded spreadsheet
	    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $excel_url );
	
	    // Create a worksheet object out of the product sheet in the workbook
	    $sheet = $spreadsheet->getActiveSheet();
	
	    $allowable_custom_fields = array();
	    foreach ( $fields as $field ) { $allowable_custom_fields[] = $field->name; }
	    //List of fields that can be accepted via upload
	    $allowed_fields = array( 'ID', 'Title', 'Description', 'Price', 'Sections' );
	
	
	    // Get column names
	    $highest_column = $sheet->getHighestColumn();
	    $highest_column_index = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highest_column);
	    for ( $column = 1; $column <= $highest_column_index; $column++ ) {
	        if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == 'ID' ) { $ID_column = $column; }
	        if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == 'Title' ) { $title_column = $column; }
	        if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == 'Description' ) { $description_column = $column; }
	        if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == 'Price' ) { $price_column = $column; }
	        if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == 'Sections' ) { $sections_column = $column; }
	
	        foreach ( $fields as $key => $field ) {
	            if ( trim( $sheet->getCellByColumnAndRow( $column, 1 )->getValue() ) == $field->name ) { $field->column = $column; }
	        }
	    }
	
	
	    // Put the spreadsheet data into a multi-dimensional array to facilitate processing
	    $highest_row = $sheet->getHighestRow();
	    for ( $row = 2; $row <= $highest_row; $row++ ) {
	        for ( $column = 1; $column <= $highest_column_index; $column++ ) {
	            $data[$row][$column] = $sheet->getCellByColumnAndRow( $column, $row )->getValue();
	        }
	    }
	
	    // Create the query to insert the products one at a time into the database and then run it
	    foreach ( $data as $menu_item ) {
	        // Create an array of the values that are being inserted for each order,
	        // edit if it's a current order, otherwise add it
	        foreach ( $menu_item as $col_index => $value ) {
	            if (	isset( $ID_column ) and $col_index == $ID_column and $ID_column !== null ) { $post['ID'] = esc_sql( $value ); }
	            if (	$col_index == $title_column and $title_column !== null ) { $post['post_title'] = esc_sql( $value ); }
	            if (	$col_index == $description_column and $description_column !== null ) { $post['post_content'] = esc_sql( $value ); }
	            if (	$col_index == $price_column and $price_column !== null ) {$post_prices = explode( ';', esc_sql( $value ) ); }
	            if (	isset( $sections_column ) and $col_index == $sections_column and $sections_column !== null ) { $post_sections = explode( ',', esc_sql( $value ) ); }
	        }
	
	        if ( ! is_array( $post_prices ) ) { $post_prices = array(); }
	        if ( ! is_array( $post_sections ) ) { $post_sections = array(); }
	
	        if ( $post['post_title'] == '' ) { continue; }
	
	        $post['post_status'] = 'publish';
	        $post['post_type'] = 'fdm-menu-item';
		
			if ( isset( $post['ID'] ) and $post['ID'] != '') { $post_id = wp_update_post( $post ); }
	        else { $post_id = wp_insert_post( $post ); }
	        
	        if ( $post_id != 0 ) {

	            foreach ( $post_sections as $section ) {
	                $menu_section = term_exists( $section, 'fdm-menu-section' );
	                if ( $menu_section !== 0 && $menu_section !== null ) { $menu_section_ids[] = (int) $menu_section['term_id']; }
	            }
	            if ( isset($menu_section_ids) and is_array($menu_section_ids) ) { wp_set_object_terms( $post_id, $menu_section_ids, 'fdm-menu-section' ); }

				if ( ! empty( $post_prices ) ) {

					delete_post_meta( $post_id, 'fdm_item_price' );

					foreach ( $post_prices as $post_price ) {
						
						add_post_meta( $post_id, 'fdm_item_price', $post_price );
					}
				}
	
	            $field_values = array();
	            foreach ( $fields as $field ) {
	                if ( isset($field->column) and isset( $menu_item[$field->column] ) ) {
	                    
	                    $field_values[ $field->slug ] = $field->type == 'checkbox' ? explode( ',', esc_sql( $menu_item[ $field->column ] ) ) : esc_sql( $menu_item[ $field->column ] );
	                }
	            }
	            update_post_meta( $post_id, '_fdm_menu_item_custom_fields', $field_values );
	        }
	
	        unset( $post );
	        unset( $post_sections );
	        unset( $menu_section_ids );
	        unset( $post_prices );
	        unset( $field_values );
	    }

	    $this->status = true;
		$this->message = __("Menu items added successfully.", 'food-and-drink-menu');

		add_action( 'admin_notices', array( $this, 'display_notice' ) );
	}

	function handle_spreadsheet_upload() {
		  /* Test if there is an error with the uploaded spreadsheet and return that error if there is */
        if (!empty($_FILES['fdm_menu_items_spreadsheet']['error']))
        {
                switch($_FILES['fdm_menu_items_spreadsheet']['error'])
                {

                case '1':
                        $error = __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'food-and-drink-menu');
                        break;
                case '2':
                        $error = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'food-and-drink-menu');
                        break;
                case '3':
                        $error = __('The uploaded file was only partially uploaded', 'food-and-drink-menu');
                        break;
                case '4':
                        $error = __('No file was uploaded.', 'food-and-drink-menu');
                        break;

                case '6':
                        $error = __('Missing a temporary folder', 'food-and-drink-menu');
                        break;
                case '7':
                        $error = __('Failed to write file to disk', 'food-and-drink-menu');
                        break;
                case '8':
                        $error = __('File upload stopped by extension', 'food-and-drink-menu');
                        break;
                case '999':
                        default:
                        $error = __('No error code avaiable', 'food-and-drink-menu');
                }
        }
        /* Make sure that the file exists */
        elseif (empty($_FILES['fdm_menu_items_spreadsheet']['tmp_name']) || $_FILES['fdm_menu_items_spreadsheet']['tmp_name'] == 'none') {
                $error = __('No file was uploaded here..', 'food-and-drink-menu');
        }
        /* Move the file and store the URL to pass it onwards*/
        /* Check that it is a .xls or .xlsx file */ 
        if (!isset($_FILES['fdm_menu_items_spreadsheet']['name']) or (!preg_match("/\.(xls.?)$/", $_FILES['fdm_menu_items_spreadsheet']['name']) and !preg_match("/\.(csv.?)$/", $_FILES['fdm_menu_items_spreadsheet']['name']))) {
            
            $error = __('File must be .csv, .xls or .xlsx', 'food-and-drink-menu');
        }
        else {
                        
                        $filename = basename( $_FILES['fdm_menu_items_spreadsheet']['name'] );
                        $filename = mb_ereg_replace( "([^\w\s\d\-_~,;\[\]\(\).])", '', $filename );
                        $filename = mb_ereg_replace( "([\.]{2,})", '', $filename );

                        //for security reason, we force to remove all uploaded file
                        $target_path = FDM_PLUGIN_DIR . "/user-sheets/";

                        $target_path = $target_path . $filename;

                        if (!move_uploaded_file($_FILES['fdm_menu_items_spreadsheet']['tmp_name'], $target_path)) {
                              $error .= "There was an error uploading the file, please try again!";
                        }
                        else {
                                $excel_file_name = $filename;
                        }
        }

        /* Pass the data to the appropriate function in Update_Admin_Databases.php to create the products */
        if (!isset($error)) {
                $update = array("message_type" => "Success", "filename" => $excel_file_name);
        }
        else {
                $update = array("message_type" => "Error", "message" => $error);
        }
        return $update;
	}

	public function display_notice() {
		if ( $this->status ) {
			echo "<div class='updated'><p>" . $this->message . "</p></div>";
		}
		else {
			echo "<div class='error'><p>" . $this->message . "</p></div>";
		}
	}

}


