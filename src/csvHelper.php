<?php
	class csvHelper {

		//Cleans up a text field for proper csv export
		public static function cleanCsvString($text) {
			return "\"" . str_replace("\"", "\"\"", $text) . "\"";
		}

		public static function createCsv($projectList) {
			$returnString = "Target, Project, Portfolio, Level, Type, Phase, Lead, Modified, Health, Timeline\r\n";
			foreach ($projectList as $proj) {
				$returnString .= csvHelper::cleanCsvString($proj['target']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['name']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['master_name']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['unit_abbr']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['classification_name']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['phase']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['current_manager']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['modified']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['overall']['status_name']) . ",";
				$returnString .= csvHelper::cleanCsvString($proj['overall']['trend_name']) . "\r\n";
			}
			return $returnString;
		}
	}
?>