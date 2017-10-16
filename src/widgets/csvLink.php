<?php
/**
* CsvLink Class
*
* @package phpmanage
**/

/**
* Widget creates a link and associated JS. Clicking the link
* will create a Comma-separated File containing Project List data.
*
* @package phpmanage
**/


class csvLink extends widget {

	function cleanCsvString($text) {
		return "\"" . str_replace("\"", "\"\"", $text) . "\"";
	}

	function createCsv($projectList) {
		static $setUpOnce = false;
		if (!$setUpOnce) {
			$doc = doc::getdoc();
			$doc->includeJS('!FileSaver.js');
			$setUpOnce = true;
		}

		$returnString = "Target, Project, Portfolio, Level, Type, Phase, Lead, Modified, Risk, Timeline\r\n";
		foreach ($projectList as $proj) {
			$returnString .= csvLink::cleanCsvString($proj['target']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['name']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['master_name']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['unit_abbr']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['classification_name']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['phase']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['current_manager']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['modified']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['overall']['status_name']) . ",";
			$returnString .= csvLink::cleanCsvString($proj['overall']['trend_name']) . "\r\n";
		}
		return $returnString;
	}

	function create($parent, $settings = null) {
		$lnk = new link($parent, '#', 'Export CSV');
		$lnk->addJS('onclick', "exportFile();");
		if ($settings['projectList']) { 
			csvLink::populate($settings); 
		}
	}


	// Useful if you need to create the csvLink on the page before you have the 
	// project data ready with which to populate it.
	public function populate($settings) {
		$doc = doc::getdoc();
		$projectCsvString = csvLink::createCsv($settings['projectList']);
		$csvFileName = $settings['filename'] ? $settings['filename'] : 'MarionetteExport.csv';
		$doc->addJS(
			"function exportFile() {
				var myText = " . json_encode($projectCsvString) . "; 
				saveTextAs(myText, '" . $csvFileName . "');
			}"
		);
	}

	
}

?>