<?php
#coreid,identifier,accessURI,rights,Owner,UsageTerms,WebStatement,caption,comments,providerManagedID,MetadataDate,associatedSpecimenReference,type,subtype,format,metadataLanguage

ini_set('include_path', '../../includes' . PATH_SEPARATOR . ini_get("include_path"));
require_once("init.php");
require_once("MDB2.php");

// === Main ===
$DB =& MDB2::connect($CONFIG['root']['DB']['dsn']);
if (PEAR::isError($DB)) { handleError($DB->getMessage()); }

#create one list of all image file names with paths
#compare to list to image list
#build file for each project
#"coreid" . "\t" . "identifier" . "\t" . "format" . "\t" . "accessURI" . "\t" . "rights" . "\t" . "Owner" . "\t" . "creator" . "\t" . "type";
$file = fopen("images-AEC-AMNH-pbi.txt", "w");

			
if (($handle = fopen("images/all_images.txt", "r")) !== FALSE) {
#echo "File Name,Family,Scientific Name,Photograph Attribution,Creator,License"; 			
#echo "<br />";

    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
		print $data[0];
		$imageFileName = $data[2];
		$imageFile = $data[1];
		$imageID = trim($data[0]);
		$imageData = imageIdentifiersFromProject($imageID,$imageFileName,$imageFile);

			fwrite($file, $imageData);
	} #close while
} #close if statement


#change project id for file
	function imageIdentifiersFromProject($imageID,$imageFileName,$imageFile){
		global $DB;
		$sql = "Select Projects.UUID, concat('urn:uuid:',Specimen.UUID,'_RID'), Institution.InstName, Projects.ProjTitle, Specimen.ProjUID, Specimen.PBIUSI from Specimen left join Projects on Specimen.ProjUID=Projects.ProjUID left join Institution on Specimen.InstUID=Institution.InstUID where Specimen.PBIUSI='$imageID' and Specimen.ProjUID='9'";
		$results = $DB->query($sql);
	    if (PEAR::isError($results)) { die("DB Error - Invalid query for imageIdentifiersFromProject" . $results->getMessage()); }
		#return $results;
		$imageData = "";
		while ($row =& $results->fetchRow()){
			$SpecUUID = $row[1];
			$projUUID = $row[0];
			$instution = $row[2];
			$projectName = $row[3];
			$projectNumber = $row[4];
			$PBIUSI = $row[5];
			$imagePathURI = "https://research.amnh.org/pbi/specimen/specimen/". $imageFile . "/" . $imageFileName;
			$imageData .= "\t" . $SpecUUID . "\t" . "image/jpg" . "\t" . $imagePathURI . "\t" . "http://creativecommons.org/licenses/by/4.0/" . "\t" . $instution .  "\t" . $instution . "\t" . "StillImage" . "\t" . $projUUID . "\t" . $projectName . "\t" . "$PBIUSI" . "\n";
			#3=bee
			#2=ttd
			#9=na_pbi
			
		} #close while
		return $imageData;
	}

?>