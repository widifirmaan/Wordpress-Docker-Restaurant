<?php

/**
 * Class to handle everything related to the walk-through that runs on plugin activation
 */

if ( !defined( 'ABSPATH' ) )
	exit;

if (!class_exists('ComposerAutoloaderInit4618f5c41cf5e27cc7908556f031e4d4')) {require_once FDM_PLUGIN_DIR . '/lib/PHPSpreadsheet/vendor/autoload.php';}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
class fdmExport {

	public function __construct() {
		add_action( 'admin_menu', array($this, 'register_install_screen' ));

		if ( isset( $_POST['fdmExport'] ) ) { add_action( 'admin_menu', array($this, 'export_menu_items' )); }
	}

	public function register_install_screen() {
		add_submenu_page( 
			'edit.php?post_type=fdm-menu', 
			'Export Menu', 
			'Export', 
			'manage_options', 
			'fdm-export', 
			array($this, 'display_export_screen') 
		);
	}

	public function display_export_screen() {
		global $fdm_controller;

		$export_permission = $fdm_controller->permissions->check_permission("export");

		?>
		<div class='wrap'>
			<h2>Export</h2>
			<?php if ( $export_permission ) { ?> 
				<form method='post'>
					<input type='submit' name='fdmExport' value='Export to Spreadsheet' class='button button-primary' />
				</form>
			<?php } else { ?>
				<div class='fdm-premium-locked'>
					<a href="https://www.fivestarplugins.com/license-payment/?Selected=FDM&Quantity=1&utm_source=fdm_admin_export_page" target="_blank">Upgrade</a> to the premium version to use this feature
				</div>
			<?php } ?>
		</div>
	<?php }

	public function export_menu_items() {
		global $fdm_controller;

		$fields = $fdm_controller->settings->get_menu_item_custom_fields();

		// Instantiate a new PHPExcel object
		$spreadsheet = new Spreadsheet();
		// Set the active Excel worksheet to sheet 0
		$spreadsheet->setActiveSheetIndex(0);

		// Print out the regular order field labels
		$spreadsheet->getActiveSheet()->setCellValue("A1", "ID");
		$spreadsheet->getActiveSheet()->setCellValue("B1", "Title");
		$spreadsheet->getActiveSheet()->setCellValue("C1", "Description");
		$spreadsheet->getActiveSheet()->setCellValue("D1", "Price");
		$spreadsheet->getActiveSheet()->setCellValue("E1", "Sections");

		$column = 'F';
		foreach ($fields as $field) {
			if ( $field->type != 'section' ) :
     			$spreadsheet->getActiveSheet()->setCellValue($column."1", $field->name);
    			$column++;
    		endif;
		}  

		//start while loop to get data
		$row_count = 2;

		$params = array(
			'posts_per_page' => -1,
			'post_type' => 'fdm-menu-item'
		);

		$menu_items = get_posts($params);

		foreach ( $menu_items as $menu_item ) {

    	 	$values = get_post_meta( $menu_item->ID, '_fdm_menu_item_custom_fields', true );
			if ( ! is_array($values ) ) { $values = array(); }

    	 	$prices = (array) get_post_meta( $menu_item->ID, 'fdm_item_price' );

			if ( empty( $prices ) ) {
				$prices = array( '' );
			}

			$prices_string = implode( ';', $prices );

    	 	$sections = get_the_terms($menu_item->ID, "fdm-menu-section");

			$sections_string = '';

			if ( is_array( $sections ) ) {

    	 		foreach ( $sections  as $section ) {

    	 			$sections_string .= $section->name . ",";
    	 		}

    	 		$sections_string = trim($sections_string, ",");
    	 	}
    	 	else { $sections_string = ""; }

    	 	$spreadsheet->getActiveSheet()->setCellValue("A" . $row_count, $menu_item->ID);
			$spreadsheet->getActiveSheet()->setCellValue("B" . $row_count, $menu_item->post_title);
			$spreadsheet->getActiveSheet()->setCellValue("C" . $row_count, $menu_item->post_content);
			$spreadsheet->getActiveSheet()->setCellValue("D" . $row_count, $prices_string);
			$spreadsheet->getActiveSheet()->setCellValue("E" . $row_count, $sections_string);

			$column = 'F';

			foreach ($fields as $field) {

				if ( $field->type != 'section' ) {

     				if ( isset( $values[ $field->slug ] ) ) {

     					$spreadsheet->getActiveSheet()->setCellValue( $column . $row_count, ( is_array( $values[$field->slug] ) ? implode( ',', $values[ $field->slug ] ) : $values[ $field->slug ] ) );
     				}
     				
     				$column++;
    			}
			}  

    		$row_count++;

    		unset($prices_string);
    		unset($sections_string);
		}

		// Redirect output to a client’s web browser (Excel5)
		if (!isset($format_type) == "csv") {
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="menu_items_export.csv"');
			header('Cache-Control: max-age=0');
			$objWriter = new Csv($spreadsheet);
			$objWriter->save('php://output');
			die();
		}
		else {echo 'Printing spreadsheet<br><br><br><br>';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="menu_items_export.xls"');
			header('Cache-Control: max-age=0');
			$objWriter = new Xls($spreadsheet);
			$objWriter->save('php://output');
			die();
		}
	}

}


