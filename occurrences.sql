#mysql -u root pbi_locality < occurrences.sql --default-character-set=latin1 > occurrencesna_pbi.tsv

SELECT
	

##########record-level terms#################
			concat('urn:uuid:',S1.UUID,'_RID') as 'idigbio:recordID', #UUID
			trim(Trailing '00:00:00' from IF(S1.UpdateDate is NULL, ifnull(S1.CreateDate,NOW()),S1.UpdateDate)) as 'dcterms:modified',
			IF(Institution.InstCode != '1111',ifnull(Institution.InstCode,''),'') as 'institutionCode',
			IF(Institution.InstCode != '1111',ifnull(Institution.InstName,''),'') as 'ownerInstitutionCode',
			concat('PreservedSpecimen') as 'basisOfRecord', #dwc_basisOfRecord
			ifnull(Projects.ProjTitle,'') as 'datasetName', #dwc_datasetname
			concat('urn:uuid:',Projects.UUID) as 'datasetID', #dwc_datasetID
			replace(ifnull(Projects.ProjCitation,''),'<download date>',CURDATE()) as 'dcterms:references', #dwc_references
	
##########occurance#################
			CASE #occurrenceID
			WHEN S1.PBIUSI LIKE '%OSACOSAC%' THEN REPLACE(REPLACE(S1.PBIUSI, 'OSACOSAC', 'urn:catalog:OSAC:OSAC:00'),' ','') #format should be OSACXXXXXXXXXX
			WHEN S1.PBIUSI LIKE '%EMECEMEC%' THEN CONCAT('urn:catalog:EMEC:EMEC:',RIGHT(S1.PBIUSI,6)) 
			ELSE CONCAT('urn:uuid:',S1.UUID)
			END as 'occurrenceID',
			
			CASE #catalogNumber
			WHEN S1.PBIUSI LIKE '%NCSUNCSU%' THEN CONCAT('NCSU', ' ', RIGHT(S1.PBIUSI,7)) #format should be NCSU XXXXXXX
			WHEN S1.PBIUSI LIKE '%OSACOSAC%' THEN REPLACE(S1.PBIUSI, 'OSACOSAC ', 'OSAC00') #format should be OSACXXXXXXXXXX
			WHEN S1.PBIUSI LIKE '%EMECEMEC%' THEN CONCAT('EMEC',RIGHT(S1.PBIUSI,6)) #format should be EMECXXXXXX
			WHEN S1.PBIUSI LIKE '%CASC_ENT%' THEN CONCAT('CASENT',RIGHT(S1.PBIUSI,7)) #format should be CASENTXXXXXXX
			WHEN S1.PBIUSI LIKE '%UCR_ENT%' THEN TRIM(S1.PBIUSI)
			WHEN S1.PBIUSI LIKE '%KUNHMENT%' THEN REPLACE(S1.PBIUSI, 'KUNHMENT', 'KUNHM-ENT')
			ELSE ifnull(S1.PBIUSI, '')
			END as 'catalogNumber',  
			
			ifnull(Collector.CollName,'') as 'recordedBy', #recordedBy
			S1.NumSpec as 'individualCount', #individualCount

			CASE #sex
			WHEN S1.Sex LIKE '%Female%' THEN 'Female'
			WHEN S1.Sex LIKE '%Male%' THEN 'Male'
			WHEN S1.Sex LIKE '%Mixed%' THEN 'Male and Female'
			ELSE ''
			END as 'sex',

			CASE #lifeStage
			WHEN S1.Sex LIKE '%Adult%' THEN 'Adult'
			WHEN S1.Sex LIKE '%Subadult%' THEN 'Juvenile'
			WHEN S1.Sex LIKE '%Juvenile%' THEN 'Juvenile'
			WHEN S1.Sex LIKE '%Egg%' THEN 'Egg'
			WHEN S1.Sex LIKE '%Nymph%' THEN 'Nymph'
			WHEN S1.Sex LIKE '%Nest%' THEN 'Nest'
			ELSE ''
			END as 'lifeStage',

		    
			CASE #associatedTaxa
			WHEN S1.HostF NOT LIKE '0' AND S1.FaunaHostF LIKE '0' THEN trim(concat(ifnull(S1.HostRel,''),IF(S1.HostRel IS NOT NULL,':',''),ifnull(F2.HostTaxName, ''),' ',ifnull(F3.HostTaxName, ''),' ',ifnull(F4.HostTaxName, '')))
			WHEN S1.HostF NOT LIKE '0' AND S1.FaunaHostF NOT LIKE '0' THEN trim(concat_ws(' ',ifnull(S1.HostRel,''),IF(S1.HostRel IS NOT NULL,':',''),ifnull(F2.HostTaxName, ''),ifnull(F3.HostTaxName, ''),ifnull(F4.HostTaxName, ''),';',ifnull(S1.FaunaHostRel,''),IF(S1.FaunaHostRel IS NOT NULL,':',''),ifnull(I1.TaxName, ''),ifnull(I2.TaxName, ''),ifnull(I3.TaxName, '')))
			WHEN S1.HostF LIKE '0' AND S1.FaunaHostF NOT LIKE '0' THEN trim(concat_ws(' ',ifnull(S1.FaunaHostRel,''),IF(S1.FaunaHostRel IS NOT NULL,':',''),ifnull(I1.TaxName, ''),ifnull(I2.TaxName, ''),ifnull(I3.TaxName, '')))
			ELSE '' 
			END as 'associatedTaxa',
			
			ifnull(S1.OrigUSI,'') as 'otherCatalogNumbers', #otherCatalogNumbers
			
##########colecting event information################
			ifnull(S1.CollMeth, '') as 'samplingProtocol', #samplingProtocol
			
			replace(if(colevent.DateEnd !='0000-00-00',concat(colevent.DateStart,'/',colevent.DateEnd),colevent.DateStart),'0000-00-00','') as 'eventDate', #eventDate
			IF(colevent.DateStart !='0000-00-00',Substring(DateStart,-10,4),'') as 'year', #year
			ifnull(colevent.DateVerbatim,'') as 'verbatimEventDate', 	#verbatimEventDate
			IF(H1.HabitatName != '' and H2.HabitatName != '',concat(ifnull(H1.HabitatName, ''),';',ifnull(H2.HabitatName, '')),concat(ifnull(H1.HabitatName, ''),ifnull(H2.HabitatName, ''))) as 'habitat', #habitat
			ifnull(colevent.ColEventCode,'') as 'eventID', 	#dwc_eventID
			
###########locality information#################
            IF(Country.Country = 'USA', 'UNITED STATES',Country.Country ) as 'country', #country
            ifnull(StateProv.StateProv, '') as 'stateProvince', #stateProvince
            SubDiv.SubDivStr as 'county', #county
            Locality.LocalityStr as 'locality', #locality
            IF(Locality.DLat !='0.00000',Locality.DLat,'') as 'decimalLatitude',#decimalLatitude
            IF(Locality.Dlong !='0.00000',Locality.Dlong,'') as 'decimalLongitude',#decimalLongitude
			Locality.LocAccuracy as 'coordinateUncertaintyInMeters', #coordinatePrecision
			Locality.NNotes as 'georeferenceRemarks', #georeferenceRemarks
			Locality.GeoRefMethod as 'locationAccordingTo', #locationAccordingTo
        	IF(Locality.ElevM != '0',concat (Locality.ElevM, ' m'),'') as 'verbatimElevation',#verbatimElevation

##########identification#################
			ifnull(S1.TypeStatus, '') as 'typeStatus', #typeStatus
			ifnull(S1.DetBy, '') as 'identifiedBy', #identifiedBy
			ifnull(S1.DetDate, '') as 'dateIdentified', #dateIdentified
			ifnull(S1.DeterminationVB, '') as 'previousIdentifications', #previousIdentifications
			
##########taxon name information#################
			trim(Trailing ';' from concat('Animalia;','Arthropoda;',T5.TaxName,';',(CASE WHEN T4.TaxName like '%none%' THEN REPLACE(T4.TaxName,T4.TaxName,'') WHEN T4.TaxName like '%unknown%' THEN REPLACE(T4.TaxName,T4.TaxName,'') WHEN T4.TaxName like '%\_%' THEN REPLACE(T4.TaxName,T4.TaxName,'') ELSE concat(T4.TaxName,';') END),(CASE WHEN T3.TaxName like '%none%' THEN REPLACE(T3.TaxName,T3.TaxName,'') WHEN T3.TaxName like '%unknown%' THEN REPLACE(T3.TaxName,T3.TaxName,'') WHEN T3.TaxName like '%\_%' THEN REPLACE(T3.TaxName,T3.TaxName,'') ELSE concat(T3.TaxName,';') END))) as 'higherClassification',
			
			trim(concat((CASE WHEN T1.TaxName like '%(%' THEN concat(Genus(T1.TaxName),' ')  WHEN T1.TaxName like '%nov. gen%' THEN '' WHEN T1.TaxName like '%unknown%' THEN '' WHEN T1.TaxName like '%\_%' THEN '' ELSE concat(T1.TaxName,' ') END),(CASE WHEN T1.TaxName like '%(%' THEN concat(SubGenus(T1.TaxName),' ')  WHEN T1.TaxName like '%nov. gen%' THEN '' WHEN T1.TaxName like'%unknown%' THEN '' WHEN T1.TaxName like '%\_%' THEN '' ELSE '' END),(CASE WHEN T2.TaxName='sp' THEN '' WHEN T2.TaxName like '%ssp.%' THEN Species(T2.TaxName) WHEN T2.TaxName='manuscript' THEN '' WHEN T2.TaxName like '%#%' THEN '' WHEN T2.TaxName like '%\(%' THEN '' WHEN T2.TaxName like '%sp.%' THEN '' WHEN T2.TaxName='unknown' THEN '' WHEN T2.TaxName like '%\_%' THEN '' WHEN T2.TaxName like '%spp.%' THEN '' WHEN T2.TaxName like 'nr.' THEN '' WHEN T1.TaxName like '%nov. gen%' THEN REPLACE(T2.TaxName,T2.TaxName,'') WHEN T1.TaxName like'%unknown%' THEN REPLACE(T2.TaxName,T2.TaxName,'') WHEN T1.TaxName like '%\_%' THEN REPLACE(T2.TaxName,T2.TaxName,'') ELSE concat(T2.TaxName,' ') END))) as 'scientificName',
			
			T5.TaxName as 'family', #family

			CASE #genus
			WHEN T1.TaxName like '%(%' THEN Genus(T1.TaxName)
			WHEN T1.TaxName like '%nov. gen%' THEN ''
			WHEN T1.TaxName like'%unknown%' THEN ''
			WHEN T1.TaxName like'%\_%' THEN ''
			ELSE T1.TaxName
			END as 'genus',

			CASE #subgenus
			WHEN T1.TaxName like '%(%' THEN SubGenus(T1.TaxName)
			WHEN T1.TaxName like '%nov. gen%' THEN ''
			WHEN T1.TaxName like'%unknown%' THEN ''
			WHEN T1.TaxName like'%\_%' THEN ''
			ELSE ''
			END as 'subgenus',

			CASE #specificEpithet
			WHEN T2.TaxName='sp' THEN ''
			WHEN T2.TaxName like '%ssp.%' THEN Species(T2.TaxName)
			WHEN T2.TaxName='manuscript' THEN ''
			WHEN T2.TaxName like '%#%' THEN ''
			WHEN T2.TaxName like '%\(%' THEN ''
			WHEN T2.TaxName like '%sp.%' THEN ''
			WHEN T2.TaxName='unknown' THEN ''
			WHEN T2.TaxName like'%\_%' THEN ''
			WHEN T2.TaxName like '%spp.%' THEN ''
			WHEN T2.TaxName like 'nr.' THEN ''
			WHEN T1.TaxName like '%nov. gen%' THEN REPLACE(T2.TaxName,T2.TaxName,'')
			WHEN T1.TaxName like'%unknown%' THEN REPLACE(T2.TaxName,T2.TaxName,'')
			WHEN T1.TaxName like'%\_%' THEN REPLACE(T2.TaxName,T2.TaxName,'')
			ELSE T2.TaxName
			END as 'specificEpithet',
			
			CASE #dwc_infraspecificEpithet
			WHEN T2.TaxName like '%ssp.%' THEN SubSpecies(T2.TaxName)
			WHEN T2.TaxName='manuscript' THEN ''
			WHEN T2.TaxName like '%#%' THEN ''
			WHEN T2.TaxName like '%\(%' THEN ''
			WHEN T2.TaxName like '%sp.%' THEN ''
			WHEN T2.TaxName='unknown' THEN ''
			WHEN T2.TaxName like'%\_%' THEN ''
			WHEN T2.TaxName like '%spp.%' THEN ''
			WHEN T2.TaxName like 'nr.' THEN ''
			ELSE ''
			END as 'infraspecificEpithet',

			IF(T2.AuthorName like '%\(%',REPLACE(T2.AuthorName, ')',concat(',', T2.AuthorDate,'\)')),IF(T2.AuthorName != '',concat(T2.AuthorName,',',T2.AuthorDate),'')) as 'scientificNameAuthorship' #scientificNameAuthorship for species
			
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
left join Projects on S1.ProjUID = Projects.ProjUID

where S1.ProjUID = '2' GROUP BY S1.PBIUSI HAVING S1.PBIUSI < 2;
#3=bee
#2=ttd
#9=na_pbi

#These are additional mysql functions that are necessary to run this query. Remember to set: delimiter $$ before adding these functions.

# CREATE FUNCTION SubGenus (TaxName TEXT)
# RETURNS TEXT
# BEGIN
# DECLARE var1  INT;
# DECLARE var2  INT;
# DECLARE var3  INT;
# DECLARE var4  INT;
# SELECT LOCATE('\(', TaxName) INTO @var1;
# SELECT LOCATE('\)', TaxName) INTO @var2;
# SET @var3 := @var1 + 1;
# SET @var4 := @var2 - @var3;
# RETURN SUBSTRING(TRIM(TaxName), @var3, @var4);
# END$$
# 
# 
# CREATE FUNCTION Genus (TaxName TEXT)
# RETURNS TEXT
# BEGIN
# DECLARE var1  INT;
# DECLARE var3  INT;
# SELECT LOCATE('\(', TaxName) INTO @var1;
# SET @var3 := @var1 - 1;
# RETURN SUBSTRING(TRIM(TaxName), 1, @var3);
# END$$
# 
# CREATE FUNCTION Species (TaxName TEXT)
# RETURNS TEXT
# BEGIN
# DECLARE var1  INT;
# DECLARE var3  INT;
# SELECT LOCATE('ssp.', TaxName) INTO @var1;
# SET @var3 := @var1 - 1;
# RETURN SUBSTRING(TRIM(TaxName), 1, @var3);
# END$$
# 
# CREATE FUNCTION SubSpecies (TaxName TEXT)
# RETURNS TEXT
# BEGIN
# DECLARE var1  INT;
# DECLARE var3  INT;
# SELECT LOCATE('ssp.', TaxName) INTO @var1;
# SET @var3 := @var1 + 4;
# RETURN TRIM(SUBSTRING(TRIM(TaxName), @var3));
# END$$