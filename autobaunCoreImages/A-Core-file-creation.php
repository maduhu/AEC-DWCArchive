<?php
#coreid,identifier,accessURI,thumbnailAccessURI,goodQualityAccessURI,rights,Owner,UsageTerms,WebStatement,caption,comments,providerManagedID,MetadataDate,associatedSpecimenReference,type,subtype,format,metadataLanguage

ini_set('include_path', '../../includes' . PATH_SEPARATOR . ini_get("include_path"));
require_once("init.php");
require_once("MDB2.php");

// === Main ===
$DB =& MDB2::connect($CONFIG['root']['DB']['dsn']);
if (PEAR::isError($DB)) { handleError($DB->getMessage()); }

#create one list of all image file names with paths
#compare to list to image list
#build file for each project

if (($handle = fopen("images/all_images-test.txt", "r")) !== FALSE) {
#echo "File Name,Family,Scientific Name,Photograph Attribution,Creator,License"; 			
#echo "<br />";
    $imageData = '';
	$file = fopen("images.txt", "w");
    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
		#print $data[0];
		$imageFileName = $data[2];
		$imageFile = $data[1];
		$imageID = trim($data[0]);
		$value = imageIdentifiersFromProject($imageID);
			while ($row =& $value->fetchRow()){
				$uuid = $row[1];
				$projUID = $row[0];
				$imageData .= $imageID . "\t" . $uuid . "\t" . $projUID . "\t" . $imageID . "\n";
				#3=bee
				#2=ttd
				#9=na_pbi
				
			} #close while
				fwrite($file, $imageData);
	} #close while
} #close if statement


	function imageIdentifiersFromProject($imageID){
		global $DB;
		$sql = "Select ProjUID, UUID from Specimen where ImageFilePrefix='$imageID';";
		$results = $DB->query($sql);
	    if (PEAR::isError($results)) { die("DB Error - Invalid query for imageIdentifiersFromProject" . $results->getMessage()); }
		return $results;
	
	}

?>