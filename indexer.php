<?php
class indexator {
	private $db_operator;

	public function __construct($db){
		$this->db_operator = $db;
		unset($db);
	}

	// Update and write new indexes
	private function write_to_index($contents, $section_data){
		$current_index_rows = $this->db_operator->get_data("SELECT from_section, unit_name FROM rm_index LIMIT 1000");
		$current_date = date('Y-m-d H:i:s');
		$report = array();

		foreach($contents as $key => $unit){
			$update = false;
			// Update.
			foreach($current_index_rows as $row => $index){
				if($index['unit_name'] == $unit['unit_name'] and $index['from_section'] == $section_data['from_section']){
					$result = $this->db_operator->write_data("
						UPDATE rm_index SET
								categories = '".addslashes($unit['categories'])."',
								unit_title = '".addslashes($unit['unit_title'])."',
								description = '".addslashes($unit['unit_descr'])."',
								last_update = '".$current_date."',
								status_code = '".$unit['status_code']."'
							WHERE unit_name ='".$index['unit_name']."'
							AND from_section = '".$index['from_section']."'
							LIMIT 1"
						);
					if($result === true){
						$report[$unit['unit_name']] = 'is writed!';
					}else{
						$report[$unit['unit_name']] = $result;
					}
					$update = true;
					break;
				}
			}
			// New.
			if($update == false){
				$preview_image = intval(file_exists(BASE_DIR.IMAGE_DIR.'/'.$section_data['section_name'].'/preview/'.$unit['unit_name'].'.jpg'));	
				$result = $this->db_operator->write_data("
						INSERT INTO rm_index VALUES (
								'',
								'".$section_data['section_name']."',
								'".addslashes($unit['unit_title'])."',
								'".addslashes($unit['unit_descr'])."',
								'".$current_date."',
								".$preview_image.",
								'".$unit['status_code']."'
							)
						");
				if($result === true){
					$report[$unit['unit_name']] = 'is writed!';
				}else{
					$report[$unit['unit_name']] = $result;
				}
			}
		}
		
		return $report;
	}

		
	// Get content from indexible section.
	private function get_content($section){
		$section_data = $this->db_operator->get_data("SELECT * FROM sections WHERE section_name = '".$section."' AND rm_indexable = '1' LIMIT 1");
		if(empty($section_data)){
			echo 'Указанный раздел не найден или не допущен к индексации материалов!';
			exit;	
		}
		$units_data = $this->db_operator->get_data("
					SELECT unit_id AS unit_identifier, unit_name, unit_title, (
						SELECT GROUP_CONCAT(CONCAT_WS('|', category_name, category_title) SEPARATOR ';')
						FROM ".$section_data['section_name']."_categories WHERE category_id IN (
							SELECT category_id
							FROM ".$section_data['section_name']."_units_relativity
							WHERE unit_id = unit_identifier
						)
					) AS categories, unit_descr, status_code
					FROM ".$section_data['section_name']."_units");

		return array(
					'section_data' => $section_data,
					'units_data' => $units_data
				); 
	}
	

	// Prepare
	public function updateIndex($section){
		if(!preg_match('/^[a-z]{4,25}$/', $section)){
			echo 'Передано недопустимое значение раздела!';
			exit;
		}
		$section_content_data = $this->get_content($section);
		return $this->write_to_index($section_content_data['units_data'], $section_content_data['section_data']);
	}
}
?>
