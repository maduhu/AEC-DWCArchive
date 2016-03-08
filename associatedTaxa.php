<?php

function associatedFlora($ProjUID){
	global $DB;

	
	$sql = "SELECT
	
##########record-level terms and attribution#################
			concat('urn:uuid:',S1.UUID,'_RID') as 'dwc:coreid', #coreid 
			concat('LabelObservation') as 'dwc:basisOfRecord', #dwc:basisOfRecord as observation recorded on the label of another specimen
			'' as 'aec:associatedOccuranceID', #not good for us; really needs to be included in notes
##########occurance#################		
            replace(ifnull(F1.HostTaxName, ''),'Not Recorded','') as 'aec:associatedFamily',
			ifnull(F2.HostTaxName, '') as 'aec:associatedGenus',
			concat(ifnull(F3.HostTaxName, ''),' ',ifnull(F4.HostTaxName, '')) as 'aec:associatedSpecificEpithet',
			
			CASE
			WHEN S1.HostSSp NOT LIKE '0' THEN concat(ifnull(F2.HostTaxName, ''),' ',ifnull(F3.HostTaxName, ''),' ',ifnull(F4.HostTaxName, ''))
			WHEN S1.HostSp NOT LIKE '0' AND S1.HostSSp LIKE '0' THEN concat(ifnull(F2.HostTaxName, ''),' ',ifnull(F3.HostTaxName, ''))
			WHEN S1.HostG NOT LIKE '0' AND S1.HostSp LIKE '0' AND S1.HostSSp LIKE '0' THEN concat(ifnull(F2.HostTaxName, ''),' ',ifnull(F3.HostTaxName, ''))
			ELSE replace(F1.HostTaxName,'Not Recorded','')
			END as 'aec:associatedScientificName',

			CASE
			WHEN S1.HostSSp NOT LIKE '0' THEN ifnull(F3.HostAuthor, '')
			WHEN S1.HostSp NOT LIKE '0' THEN ifnull(F2.HostAuthor, '')
			ELSE ''
			END as 'aec:associatedAuthor',

			ifnull(HostCommonName.CommonName,'') as 'aec:associatedCommonName',
			ifnull(S1.HostRel, '') as 'aec:associatedRelationshipTerm',
			
			CASE #aec:associatedRelationshipURI
			WHEN S1.HostRel LIKE 'associated%with' THEN 'http://purl.obolibrary.org/obo/RO_0002437'
			WHEN S1.HostRel LIKE 'collected%in' THEN 'http://purl.obolibrary.org/obo/RO_0001025'
			WHEN S1.HostRel LIKE 'emerged%from' THEN 'http://eol.org/schema/terms/emergedFrom'
			WHEN S1.HostRel LIKE 'collected%on' THEN 'http://eol.org/schema/terms/emergedFrom'
			ELSE ''
			END as 'aec:associatedRelationshipURI',
			
			TRIM(BOTH ';' FROM concat(ifnull(S1.HostNotes, ''),';',ifnull(S1.HerbID, ''))) as 'aec:associatedNotes',
			ifnull(S1.HostDetBy, '') as 'aec:associatedDeterminedBy',
			ifnull(S1.Condition, '') as 'aec:associatedCondition',
			ifnull(S1.HostLoc, '') as 'aec:associatedLocationOnHost',
			'' as 'aec:associatedEmergenceVerbatimDate',
			'' as 'aec:associatedCollectionLocation',
			'Unknown' as 'aec:isCultivar'


			#TODO
			#host collecting event id add to notes
			#need to compare to globi associations
									
				
FROM Specimen S1

left join Flora_MNL F1 ON S1.HostF = F1.HostMNLUID
left join Flora_MNL F2  ON S1.HostG = F2.HostMNLUID
left join Flora_MNL F3  ON S1.HostSp = F3.HostMNLUID
left join Flora_MNL F4  ON S1.HostSSp = F4.HostMNLUID

left join MNL T1  ON S1.Genus = T1.MNLUID
left join MNL T2  ON S1.Species = T2.MNLUID
left join MNL T3  ON S1.Tribe = T3.MNLUID
left join MNL T4  ON S1.Subfamily = T4.MNLUID
left join MNL T5  ON T4.ParentID = T5.MNLUID

left join colevent on S1.ColEventUID = colevent.ColEventUID
left join Collector on colevent.Collector = Collector.CollectorUID

left join Locality on S1.Locality = Locality.LocalityUID
left join SubDiv on Locality.SubDivUID = SubDiv.SubDivUID
left join StateProv on SubDiv.StateProvUID = StateProv.StateProvUID
left join Country on StateProv.CountryUID = Country.UID
left join Institution on S1.InstUID = Institution.InstUID
left join HostCommonName on S1.HostCName = HostCommonName.CommonUID
left join Habitat H1 on S1.MacroUID = H1.HabitatUID
left join Habitat H2 on S1.MicroUID = H2.HabitatUID
left join UUser on S1.CreatedBy=UUser.UserName 

where S1.ProjUID = $ProjUID and S1.HostF != '0'";
	$results = $DB->query($sql);
    if (PEAR::isError($results)) { die("DB Error - Invalid query for associatedFlora" . $results->getMessage()); }
		return $results;
}



function associatedFauna($ProjUID){
	global $DB;

	
	$sql = 
"SELECT
	
##########record-level terms and attribution#################
			concat('urn:uuid:',S1.UUID,'_RID') as 'dwc:coreid', #coreid
			concat('LabelObservation') as 'dwc:basisOfRecord',
			concat('') as 'aec:associatedOccuranceID',
##########occurance#################
            replace(ifnull(I1.TaxName, ''),'Not Recorded','') as 'aec:associatedFamily',
			replace(IF(I2.TaxName IS NULL ,'',I2.TaxName),'Not Recorded','') as 'aec:associatedGenus',
			replace(IF(I3.TaxName IS NULL,'',I3.TaxName),'Not Recorded','') as 'aec:associatedSpecificEpithet',
			
			CASE
			WHEN S1.FaunaHostSp NOT LIKE '0' THEN concat(I2.TaxName,' ',I3.TaxName)
			WHEN S1.FaunaHostSp LIKE '0' AND S1.FaunaHostG NOT LIKE '0' THEN I2.TaxName
			WHEN S1.FaunaHostT NOT LIKE '0' AND S1.FaunaHostG LIKE '0' THEN I4.TaxName
			WHEN S1.FaunaHostSF NOT LIKE '0' AND S1.FaunaHostG LIKE '0' AND S1.FaunaHostT LIKE '0' THEN I5.TaxName
			ELSE replace(I1.TaxName,'Not Recorded','')
			END as 'aec:associatedScientificName',

			IF(I2.AuthorName like '%\(%',REPLACE(I2.AuthorName, ')',concat(',', I2.AuthorDate,'\)')),IF(I2.AuthorName != '',concat(I2.AuthorName,',',I2.AuthorDate),'')) as 'aec:associatedAuthor',
			
			'' as 'associatedCommonName',
			ifnull(S1.FaunaHostRel, '') as 'aec:associatedRelationshipTerm',
			
			CASE #aec:associatedRelationshipURI
			WHEN S1.FaunaHostRel LIKE 'associated%with' THEN 'http://purl.obolibrary.org/obo/RO_0002437'
			WHEN S1.FaunaHostRel LIKE 'collected%in' THEN 'http://purl.obolibrary.org/obo/RO_0001025'
			WHEN S1.FaunaHostRel LIKE 'emerged%from' THEN 'http://eol.org/schema/terms/emergedFrom'
			WHEN S1.FaunaHostRel LIKE 'collected%on' THEN 'http://purl.obolibrary.org/obo/RO_0002220'
			WHEN S1.FaunaHostRel LIKE 'reared%from' THEN 'http://eol.org/schema/terms/emergedFrom'
			WHEN S1.FaunaHostRel LIKE 'from' THEN 'http://eol.org/schema/terms/emergedFrom'
			WHEN S1.FaunaHostRel LIKE 'hyperparasitoid%of' THEN 'http://purl.obolibrary.org/obo/RO_0002444'
			ELSE ''
			END as 'aec:associatedRelationshipURI',
			
			TRIM(BOTH ';' FROM concat(ifnull(S1.FaunaHostNotes, '1'),';',ifnull(S1.FaunaID, '1'))) as 'aec:associatedNotes',
			ifnull(S1.FaunaHostDetBy, '') as 'aec:associatedDeterminedBy',
			ifnull(S1.FaunaCondition, '') as 'aec:associatedCondition',
			ifnull(S1.FaunaHostLoc, '') as 'aec:associatedLocationOnHost',


			ifnull(S1.FaunaHostEmergeDate, '') as 'aec:associatedEmergenceVerbatimDate',
			'' as 'aec:associatedCollectionLocation',
			'Unknown' as 'aec:isCultivar'
						
				
FROM Specimen S1

left join Flora_MNL F1 ON S1.HostF = F1.HostMNLUID
left join Flora_MNL F2  ON S1.HostG = F2.HostMNLUID
left join Flora_MNL F3  ON S1.HostSp = F3.HostMNLUID
left join Flora_MNL F4  ON S1.HostSSp = F4.HostMNLUID

left join MNL T1  ON S1.Genus = T1.MNLUID
left join MNL T2  ON S1.Species = T2.MNLUID
left join MNL T3  ON S1.Tribe = T3.MNLUID
left join MNL T4  ON S1.Subfamily = T4.MNLUID
left join MNL T5  ON T4.ParentID = T5.MNLUID

left join MNL I1 ON S1.FaunaHostF=I1.MNLUID 
left join MNL I2 ON S1.FaunaHostG=I2.MNLUID 
left join MNL I3 ON S1.FaunaHostSp=I3.MNLUID 
left join MNL I4 ON S1.FaunaHostT=I4.MNLUID
left join MNL I5 ON S1.FaunaHostSF=I5.MNLUID

left join colevent on S1.ColEventUID = colevent.ColEventUID
left join Collector on colevent.Collector = Collector.CollectorUID

left join Locality on S1.Locality = Locality.LocalityUID
left join SubDiv on Locality.SubDivUID = SubDiv.SubDivUID
left join StateProv on SubDiv.StateProvUID = StateProv.StateProvUID
left join Country on StateProv.CountryUID = Country.UID
left join Institution on S1.InstUID = Institution.InstUID
left join HostCommonName on S1.HostCName = HostCommonName.CommonUID
left join Habitat H1 on S1.MacroUID = H1.HabitatUID
left join Habitat H2 on S1.MicroUID = H2.HabitatUID
left join UUser on S1.CreatedBy=UUser.UserName 

where S1.ProjUID = '$ProjUID' and S1.FaunaHostF != '0'";
	$results = $DB->query($sql);
    if (PEAR::isError($results)) { die("DB Error - Invalid query for associatedFauna" . $results->getMessage()); }
		return $results;
}





