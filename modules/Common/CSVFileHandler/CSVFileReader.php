<?php
namespace Modules\Common\CSVFileHandler;

class CSVFileReader
{
    public static function Read($target_file, $first_line_is_header = true) {
		$ctr = 0;
        $header = [];
        $listdata = [];

		// csv handler
		if (($open = fopen($target_file, "r")) !== FALSE) {
			// read csv with max column 1000
			try {
				while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
					if ($first_line_is_header && $ctr == 0) {
						$header = $data;
					} else {
						if (count($data) > 1) {
							$listdata[] = $data;
						}
					}				
					$ctr++;
				}
	
				fclose($open);
			} catch (\Exception $ex) {
				return [
					"header" => [], 
					"data" => [],
					"error" => $ex->getMessage()
				];
			}
			
		} else {
			return [
				"header" => [], 
				"data" => [],
				"error" => "cannot open file"
			];
		}

		return [
            "header" => $header, 
            "data" => $listdata
        ];
	}
}