<?php

ini_set('include_path', '../includes' . PATH_SEPARATOR . ini_get("include_path"));
require_once("init.php");
require_once("MDB2.php");
include_once ("associatedTaxa.php");
ini_set('memory_limit', '-1');

// === Main ===
$DB =& MDB2::connect($CONFIG['root']['DB']['dsn']);
if (PEAR::isError($DB)) { handleError($DB->getMessage()); }

// === set these variables globally for the installation of AEC  ===

$path_to_archivesServer = "http://amnh.begoniasociety.org/dwc/";
$path_to_archives = "../public/idigbio/dwc/";
$path_to_database = "http://research.amnh.org/pbi/locality/";
$institutionName = "American Museum of Natural History";

#set a blank variable for the rss feed
$RssText = '';

$specimenCount = specimenCount();
print $specimenCount;


#identify series of values for each project in AEC; these are included in .eml file
 	$value = numberProjects();
		while ($row =& $value->fetchRow()){

			$ProjUID = $row[0];
			$ProjCode = $row[1];
			$ProjTitle = $row[2];
			$ProjDesc = $row[3];
			$ProjLicense = $row[4];
			$ProjCitation = $row[5];
			$ProjLink = $row[6];
			$ProjNote = $row[7];
			$ProjContactFirstName = $row[8];
			$ProjContactLastName = $row[9];
			$ProjEmail = $row[10];
			$ProjType = $row[11];
			$UUID = $row[16];
			$date = date("Ymd");
			$rssdate = date("D, d M Y H:i:s");
			$citationdate = date("d M Y");
			$name = makeDwCDirectory($ProjCode,$date,$path_to_archives);
			$ModifiedProjCitation = str_replace("<download date>", $citationdate, $ProjCitation);

#create EML			
	printEML($ProjUID,$ProjCode,$ProjTitle,$ProjDesc,$ProjLicense,$ModifiedProjCitation,$ProjLink,$ProjNote,$ProjContactFirstName,$ProjContactLastName,$ProjEmail,$ProjType,$UUID,$date,$rssdate,$name,$path_to_archives,$path_to_database,$institutionName,$path_to_archivesServer);


#create RSS feed			
	$RssText.=appendRSS($ProjUID,$ProjCode,$ProjTitle,$ProjDesc,$ProjLicense,$ModifiedProjCitation,$ProjLink,$ProjNote,$ProjContactFirstName,$ProjContactLastName,$ProjEmail,$ProjType,$UUID,$date,$rssdate,$name,$path_to_archives,$path_to_archivesServer);

#create associatedTaxa.tsv extension in archive; uses associatedTaxa.php file
associatedTaxa($ProjUID,$path_to_archives,$name,$specimenCount);

	}#close while loop
	
#============================================
#finish RSS feed	
	echo "archive written on " . $rssdate;		
	$rssName = $path_to_archives . "rss.xml";
	$file = fopen($rssName, "w");
	$allRssText =  <<<EOD
<rss version="2.0">
	<channel>
		<title>Arthropod Easy Capture (AMNH)</title>
		<link>http://research.amnh.org/pbi/locality/</link>
		<description>Arthropod Easy Capture rss feed</description>
		<language>en-us</language>$RssText
	</channel>
</rss>
EOD;
	
	fwrite($file, $allRssText);

#============================================
#
#Functions below for DwC-A
#	
#============================================	
#============================================

##get total count of specimen table for file construction
function specimenCount(){
	global $DB;
	$sql = "Select SpecimenUID from Specimen order by SpecimenUID desc limit 1";
	$results = $DB->query($sql);
    if (PEAR::isError($results)) { die("DB Error - Invalid query for specimenCount" . $results->getMessage()); }
	$row =& $results->fetchRow();
    	return $row[0];	
}


#TODO iterate slowly or in increments? 
function associatedTaxa($ProjUID,$path_to_archives,$name,$specimenCount){
	$associatedTaxaData = '';
	$associatedTaxaFilePath = $path_to_archives . $name."/associatedTaxa.tsv";
	$associatedTaxaFile = fopen($associatedTaxaFilePath, "w");
			$associatedTaxaData .= "dwc:coreid" . "\t" . "dwc:basisOfRecord" . "\t" . "aec:associatedOccuranceID" . "\t" . "aec:associatedFamily" ."\t" . "aec:associatedGenus" ."\t" . "aec:associatedSpecificEpithet" ."\t" . "aec:associatedScientificName" ."\t" . "aec:associatedAuthor" ."\t" . "aec:associatedCommonName" ."\t" . "aec:associatedRelationshipTerm" . "\t" . "aec:associatedRelationshipURI" . "\t" . "aec:associatedNotes" . "\t" . "aec:associatedDeterminedBy" . "\t" . "aec:associatedCondition" . "\t" . "aec:associatedLocationOnHost" . "\t" . "aec:associatedEmergenceVerbatimDate" . "\t" . "aec:associatedCollectionLocation" . "\t" . "aec:isCultivar" ."\n";

		$Flora = associatedFlora($ProjUID);
			while($FloraValue = $Flora->fetchRow()){
			$URIvalue = $FloraValue[9];
			$associatedRelationshipURI = URIAssociateRelationsMatch($URIvalue);
			$associatedTaxaData .= trim($FloraValue[0]) . "\t" . trim($FloraValue[1]) . "\t" . trim($FloraValue[2]) . "\t" . trim($FloraValue[3]) . "\t" . trim($FloraValue[4]) . "\t" . trim($FloraValue[5]) . "\t" . trim($FloraValue[6]) . "\t" . trim($FloraValue[7]) . "\t" . trim($FloraValue[8]) . "\t" . trim($FloraValue[9]) . "\t" . trim($FloraValue[10]) . "\t" . trim($FloraValue[11]) . "\t" . trim($FloraValue[12]) . "\t" . trim($FloraValue[13]) . "\t" . trim($FloraValue[14]) . "\t" . trim($FloraValue[15]) . "\t" . trim($FloraValue[16]) . "\t" . trim($FloraValue[17]) . "\n";
			}
			
	$Fauna = associatedFauna($ProjUID);
		while($FaunaValue = $Fauna->fetchRow()){
			$URIvalue = $FaunaValue[9];
			$associatedRelationshipURI = URIAssociateRelationsMatch($URIvalue);
			$associatedTaxaData .= trim($FaunaValue[0]) . "\t" . trim($FaunaValue[1]) . "\t" . trim($FaunaValue[2]) . "\t" . trim($FaunaValue[3]) . "\t" . trim($FaunaValue[4]) . "\t" . trim($FaunaValue[5]) . "\t" . trim($FaunaValue[6]) . "\t" . trim($FaunaValue[7]) . "\t" . trim($FaunaValue[8]) . "\t" . trim($FaunaValue[9]) . "\t" . trim($FaunaValue[10]) . "\t" . trim($FaunaValue[11]) . "\t" . trim($FaunaValue[12]) . "\t" . trim($FaunaValue[13]) . "\t" . trim($FaunaValue[14]) . "\t" . trim($FaunaValue[15]) . "\t" . trim($FaunaValue[16]) . "\t" . trim($FaunaValue[17]) . "\n";
		}
	
	fwrite($associatedTaxaFile, $associatedTaxaData);	

}

#============================================
#this RSS feed only includes the new files to export; old DwC-A files are deleted. 
function appendRSS($ProjUID,$ProjCode,$ProjTitle,$ProjDesc,$ProjLicense,$ModifiedProjCitation,$ProjLink,$ProjNote,$ProjContactFirstName,$ProjContactLastName,$ProjEmail,$ProjType,$UUID,$date,$rssdate,$name,$path_to_archives,$path_to_archivesServer){
	$text = <<<EOD
		<item ProjUID="$ProjUID">
			<title>
				$ProjTitle
			</title>
			<description>
				$ModifiedProjCitation
			</description>
			<guid>
				urn:uuid:$UUID
			</guid>
			<emllink>
				$path_to_archivesServer$name.eml
			</emllink>
			<type>DWCA</type>
			<recordType>DWCA</recordType>
			<link>
				$path_to_archivesServer$name.zip
			</link>
			<pubDate>$rssdate</pubDate>
		</item>
			
EOD;
return $text;
}


#============================================
#adds new folders
 function makeDwCDirectory($ProjCode,$date,$path_to_archives){
 	$name = 'AEC-' . $ProjCode . '_DwC-A' . $date;
 	$fullpath = $path_to_archives . $name;
 	$meta = "meta.xml";
 		IF(!file_exists($fullpath)) {
 			mkdir($fullpath, 0777);
 			copy($meta,$fullpath."/" . $meta);
 			}
 
 	return $name;
 }

#============================================
#creates EML files

function printEML($ProjUID,$ProjCode,$ProjTitle,$ProjDesc,$ProjLicense,$ModifiedProjCitation,$ProjLink,$ProjNote,$ProjContactFirstName,$ProjContactLastName,$ProjEmail,$ProjType,$UUID,$date,$rssdate,$name,$path_to_archives,$path_to_database,$institutionName,$path_to_archivesServer){
	$filename = $name . ".eml";
	$fullPath = $path_to_archives . $filename;
	$myfile = fopen($fullPath, "w");
	$text = <<<EOD
<?xml version="1.0" encoding="iso-8859-1"?>
<eml:eml packageId="725781a1-d6e4-4ad0-8a92-1e0a997596d5" system="http://sourceforge.net/projects/arthropodeasy/" xmlns:eml="eml://ecoinformatics.org/eml-2.1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="eml://ecoinformatics.org/eml-2.1.1 eml.xsd">
<dataset>
<title>$ProjTitle</title>
<creator>
	<organizationName>Arthropod Easy Capture ($institutionName)</organizationName>
</creator>
<pubDate>$date</pubDate>
<language>eng</language>
<intellectualRights>
	<para>$ProjLicense</para>
</intellectualRights>
<distribution>
	<online>
		<url>http://research.amnh.org/pbi/locality/</url>
	</online>
</distribution>
<contact>
	<individualName> 
		<givenName>$ProjContactFirstName</givenName>
		<surName>$ProjContactLastName</surName>
	</individualName>
	<electronicMailAddress>$ProjEmail</electronicMailAddress>
</contact>
<publisher>
	<organizationName>Arthropod Easy Capture</organizationName>
</publisher>
</dataset>
<additionalMetadata>
<metadata>
	<abstract>
		<para>$ProjDesc</para>
	</abstract>
</metadata>
</additionalMetadata>
<additionalMetadata>
<metadata>
	<formattedCitation>$ModifiedProjCitation</formattedCitation>
</metadata>
</additionalMetadata>
</eml:eml>
EOD;

$fullpath = $path_to_archives . $name;
fwrite($myfile, $text);
copy($path_to_archives . $filename,$fullpath."/" . $filename);

}

#============================================
#function that counts number of projects
function numberProjects(){
	global $DB;
	$sql = "Select * from Projects where ProjUID != '1' and ProjType='1'";
	$results = $DB->query($sql);
    if (PEAR::isError($results)) { die("DB Error - Invalid query for numberProjects" . $results->getMessage()); }
	return $results;	
}

#============================================
#function that defines the relation URI for terms in associated taxa

function URIAssociateRelationsMatch($associatedRelationString){
		if($associatedRelationString == "emerged_from"){
			$associatedRelationURI = "http://eol.org/schema/terms/emergedFrom";
		}elseif($associatedRelationString == "emerged from"){
			$associatedRelationURI = "http://eol.org/schema/terms/emergedFrom";
		}elseif($associatedRelationString == "reared from"){
			$associatedRelationURI = "http://eol.org/schema/terms/emergedFrom";
		}elseif($associatedRelationString == "from"){
			$associatedRelationURI = "http://eol.org/schema/terms/emergedFrom";
		}elseif($associatedRelationString == "collected on"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002220";
		}elseif($associatedRelationString == "on"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002220";
		}elseif($associatedRelationString == "visitor"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002220";			
		}elseif($associatedRelationString == "collected in"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0001025";
		}elseif($associatedRelationString == "in"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0001025";			
		}elseif($associatedRelationString == "parasitoid of"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002444";
		}elseif($associatedRelationString == "parasite of"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002444";
		}elseif($associatedRelationString == "hyperparasite of"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002444";
		}elseif($associatedRelationString == "has parasitoid"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002445";
		}elseif($associatedRelationString == "has parasite"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002445";
		}elseif($associatedRelationString == "has hyperparasite"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002445";
		}elseif($associatedRelationString == "hyperparasitoid of"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002444";
		}elseif($associatedRelationString == "associated_with"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002437";
		}elseif($associatedRelationString == "associated with"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002437";
		}elseif($associatedRelationString == "associates with"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002437";
		}elseif($associatedRelationString == "interacts with"){
			$associatedRelationURI = "http://purl.obolibrary.org/obo/RO_0002437";
		}elseif($associatedRelationString == "near"){
			$associatedRelationURI = "http://eol.org/schema/terms/foundNear";
		}else{
			
			$associatedRelationURI = "";
		}	
		
		return $associatedRelationURI;
}

?>