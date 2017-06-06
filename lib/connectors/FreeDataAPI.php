<?php
namespace php_active_record;
/* connector: [freedata_xxx] */
class FreeDataAPI
{
    /*
    const VARIABLE_NAME = "string value";
    */
    
    function __construct($folder = null)
    {
        $this->download_options = array('cache' => 1, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 2592000); //expires in a month
        $this->destination['reef life survey'] = CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/observations.txt";
        $this->fields['reef life survey'] = array("id", "occurrenceID", "eventDate", "decimalLatitude", "decimalLongitude", "scientificName", "taxonRank", "kingdom", "phylum", "class", "family");
        $this->destination['eMammal'] = CONTENT_RESOURCE_LOCAL_PATH . "eMammal/observations.txt";
        $this->fields['eMammal'] = array("id", "occurrenceID", "eventDate", "decimalLatitude", "decimalLongitude", "scientificName", "taxonRank", "kingdom", "phylum", "class", "family");
        
        $this->destination['USGS'] = CONTENT_RESOURCE_LOCAL_PATH . "usgs_nonindigenous_aquatic_species/observations.txt"; //Nonindigenous Aquatic Species
        $this->fields['USGS'] = array("id", "occurrenceID", "eventDate", "decimalLatitude", "decimalLongitude", "scientificName", "taxonRank", "kingdom", "family", "basisOfRecord", "group", "genus", "species", "vernacularName", "stateProvince", "county", "locality", "date", "year", "month", "day", "catalogNumber");
        $this->service['USGS']['occurrences'] = "https://nas.er.usgs.gov/api/v1/occurrence/search"; //https://nas.er.usgs.gov/api/v1/occurrence/search?genus=Zizania&species=palustris&offset=0

        $this->ctr = 0; //for "reef life survey" and "eMammal"
        $this->debug = array();
    }

    //start for USGS ==============================================================================================================
    /* These are the unique list of groups:
                [Fishes] =>                 Animalia
                [Plants] =>                 Plantae
                [Amphibians-Frogs] =>       Animalia
                [Reptiles-Snakes] =>        Animalia
                [Mollusks-Bivalves] =>      Animalia
                [Reptiles-Crocodilians] =>  Animalia
                [Amphibians-Salamanders] => Animalia
                [Reptiles-Turtles] =>       Animalia
                [Crustaceans-Amphipods] =>  Animalia 
                [Crustaceans-Copepods] =>   Animalia
                [Crustaceans-Isopods] =>    Animalia
                [Annelids-Hirundinea] =>    Animalia
                [Mollusks-Gastropods] =>    Animalia
                [Coelenterates-Hydrozoans] =>   Animalia
                [Crustaceans-Cladocerans] =>    Animalia
                [Rotifers] =>                   Animalia
                [Crustaceans-Crayfish] =>       Animalia
                [Crustaceans-Shrimp] =>         Animalia
                [Mammals] =>                    Animalia
                [Crustaceans-Crabs] =>          Animalia
                [Crustaceans-Mysids] =>     Animalia
                [Bryozoans] =>              Animalia
                [Mollusks-Cephalopods] =>   Animalia
                [Annelids-Oligochaetes] =>  Animalia
                [Entoprocts] =>             Animalia
    From the API, we can get the FAMILY of the species belonging to each of the groups.
    Do we need to fill-in the other: KINGDOM, PHYLUM, CLASS, ORDER? */
    
    function generate_usgs_archive($local_path)
    {   /* steps:
        1. get species list here: from a [csv] button here: https://nas.er.usgs.gov/queries/SpeciesList.aspx?group=&genus=&species=&comname=&Sortby=1
        2. get occurrences for each species
        3. create the zip file
        */
        
        $options = $this->download_options;
        $options['resource_id'] = "usgs"; //a folder /usgs/ will be created in /eol_cache/
        $options['download_wait_time'] = 1000000; //1 second
        $options['expire_seconds'] = false;
        $options['download_attempts'] = 3;
        $options['delay_in_minutes'] = 2;
        
        self::create_folder_if_does_not_exist('usgs_nonindigenous_aquatic_species');
        
        //first row - headers of text file
        $WRITE = Functions::file_open($this->destination['USGS'], "w");
        fwrite($WRITE, implode("\t", $this->fields['USGS']) . "\n");
        fclose($WRITE);
        
        $i = 0;
        $species_list = "/Library/WebServer/Documents/cp/FreshData/USGS/SpeciesList.csv"; //use [csv] button below this page: https://nas.er.usgs.gov/queries/SpeciesList.aspx
        foreach(new FileIterator($species_list) as $line_number => $line)
        {
            $line = str_replace(", ", ";", $line); //needed to do this bec of rows like e.g. "Fishes,Cyprinidae,Labeo chrysophekadion,,black sharkminnow, black labeo,Exotic,Freshwater";
            $arr = explode(",", $line);
            if(count($arr) == 7) 
            {
                $i++;
                // print_r($arr); //exit;
                $this->debug['group'][$arr[0]] = '';
                $group = $arr[0];
                $temp = explode(" ", $arr[2]); //scientificname
                $temp = array_map('trim', $temp);
                $genus = $temp[0];
                array_shift($temp);
                $species = trim(implode(" ", $temp));
                $species = urlencode($species);
                $offset = 0;
                
                /* breakdown when caching: as of Jun 5, 2017 total is 1,270
                $cont = false;
                // if($i >= 0    && $i < 250) $cont = true; 
                // if($i >= 250    && $i < 500) $cont = true; 
                // if($i >= 500    && $i < 750) $cont = true; 
                // if($i >= 750    && $i < 1000) $cont = true; 
                if($i >= 1000    && $i < 1300) $cont = true; 
                if(!$cont) continue;
                */
                
                while(true)
                {
                    $api = $this->service['USGS']['occurrences'];
                    $api .= "?offset=$offset&genus=$genus&species=$species";
                    if($json = Functions::lookup_with_cache($api, $options))
                    {
                        $recs = json_decode($json);
                        echo "\n$i. total: ".count($recs->results). "\n";
                        if($val = $recs->results) self::process_usgs_occurrence($val, $group);
                        // break; //debug
                        $offset += 100;
                        if($recs->endOfRecords == "true") break;
                        if(count($recs->results) < 100) break;
                    }
                    else break;
                }
            }
        }
        echo "\ntotal: ".($i-1)."\n";

        self::last_part("usgs_nonindigenous_aquatic_species"); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        // if($this->debug) print_r($this->debug);
    }
    
    private function process_usgs_occurrence($recs, $group)
    {
        $i = 0;
        $WRITE = Functions::file_open($this->destination['USGS'], "a");
        foreach($recs as $rec)
        {
            $i++;
            if(($i % 1000) == 0) echo number_format($i) . "\n";
            if($rec)
            {
                $row = self::process_rec_USGS($rec, $group);
                if($row) fwrite($WRITE, $row . "\n");
            }
            // if($i > 5) break;  //debug only
        }
        fclose($WRITE);
    }
    
    function process_rec_USGS($rec, $group)
    {
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['key'];
        $rek['occurrenceID']     = $rec['museumCatNumber'];
        $rek['eventDate']        = $rec['date'];
        $rek['decimalLatitude']  = $rec['decimalLatitude'];
        $rek['decimalLongitude'] = $rec['decimalLongitude'];
        $rek['scientificName']   = $rec['scientificName'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = '';
        $rek['phylum']           = '';
        $rek['class']            = '';
        $rek['family']           = $rec['family'];
        $rek['basisOfRecord']    = $rec['recordType'];
        */
        /* sample actual data
        [key] => 276594                                     done
        [speciesID] => 707                                  x
        [group] => Fishes                                   http://rs.tdwg.org/dwc/terms/group
        [family] => Gobiidae                                done
        [genus] => Acanthogobius                            http://rs.tdwg.org/dwc/terms/genus
        [species] => flavimanus                             http://rs.gbif.org/terms/1.0/species
        [scientificName] => Acanthogobius flavimanus        done
        [commonName] => Yellowfin Goby                      http://rs.tdwg.org/dwc/terms/vernacularName
        [state] => California                               http://rs.tdwg.org/dwc/terms/stateProvince
        [county] => San Diego                               http://rs.tdwg.org/dwc/terms/county
        [locality] => Mission Bay, off Fiesta Island        http://rs.tdwg.org/dwc/terms/locality
        [decimalLatitude] => 32.778904
        [decimalLongitude] => -117.224078
        [huc8Name] => San Diego
        [huc8] => 18070304
        [date] => 2003-6-13                                 http://purl.org/dc/terms/date
        [year] => 2003                                      http://rs.tdwg.org/dwc/terms/year
        [month] => 6                                        http://rs.tdwg.org/dwc/terms/month
        [day] => 13                                         http://rs.tdwg.org/dwc/terms/day
        [status] => established                             x
        [comments] =>                                       x
        [recordType] => Literature                          done
        [disposal] => Scripps Institution of Oceanography   x
        [museumCatNumber] => SIO 03-78                      http://rs.tdwg.org/dwc/terms/catalogNumber
        [freshMarineIntro] => Brackish                      x
        */
        
        //total of 22 columns
        if(!isset($this->debug['key'][$rec->key])) $this->debug['key'][$rec->key] = '';
        else return false; //print("\nkey duplicate: $rec->key\n");
        $this->ctr++;
        $rek[]  = $this->ctr;
        $rek[]  = $rec->key;
        $rek[]  = @$rec->date;
        $rek[]  = $rec->decimalLatitude;
        $rek[]  = $rec->decimalLongitude;
        $rek[]  = $rec->scientificName;
        $rek[]  = 'species';
        $rek[]  = ($group == "Plants" ? "Plantae" : "Animalia");
        $rek[]  = $rec->family;
        $rek[]  = $rec->recordType;
        
        $rek[]  = $rec->group;
        $rek[]  = $rec->genus;
        $rek[]  = $rec->species;
        $rek[]  = $rec->commonName;
        $rek[]  = $rec->state;
        $rek[]  = $rec->county;
        $rek[]  = $rec->locality;
        $rek[]  = @$rec->date;
        $rek[]  = $rec->year;
        $rek[]  = $rec->month;
        $rek[]  = $rec->day;
        $rek[]  = $rec->museumCatNumber;
        return implode("\t", $rek);
        /*
        [group] => Fishes                                   http://rs.tdwg.org/dwc/terms/group
        [genus] => Acanthogobius                            http://rs.tdwg.org/dwc/terms/genus
        [species] => flavimanus                             http://rs.gbif.org/terms/1.0/species
        [commonName] => Yellowfin Goby                      http://rs.tdwg.org/dwc/terms/vernacularName
        [state] => California                               http://rs.tdwg.org/dwc/terms/stateProvince
        [county] => San Diego                               http://rs.tdwg.org/dwc/terms/county
        [locality] => Mission Bay, off Fiesta Island        http://rs.tdwg.org/dwc/terms/locality
        [date] => 2003-6-13                                 http://purl.org/dc/terms/date
        [year] => 2003                                      http://rs.tdwg.org/dwc/terms/year
        [month] => 6                                        http://rs.tdwg.org/dwc/terms/month
        [day] => 13                                         http://rs.tdwg.org/dwc/terms/day
        [museumCatNumber] => SIO 03-78                      http://rs.tdwg.org/dwc/terms/catalogNumber
        */
    }
    
    //end for USGS ================================================================================================================

    function generate_meta_xml($folder)
    {
        if(!$WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml", "w")) return;
        fwrite($WRITE, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($WRITE, '<archive xmlns="http://rs.tdwg.org/dwc/text/">' . "\n");
        fwrite($WRITE, '  <core encoding="UTF-8" linesTerminatedBy="\n" fieldsTerminatedBy="\t" fieldsEnclosedBy="" ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Occurrence">' . "\n");
        fwrite($WRITE, '    <files>' . "\n");
        fwrite($WRITE, '      <location>observations.txt</location>' . "\n");
        fwrite($WRITE, '    </files>' . "\n");
        fwrite($WRITE, '    <id index="0"/>' . "\n");
        if(in_array($folder, array("reef_life_survey", "eMammal")))
        {
            fwrite($WRITE, '    <field index="0" term="http://rs.gbif.org/terms/1.0/RLSID"/>' . "\n");
            fwrite($WRITE, '    <field index="1" term="http://rs.tdwg.org/dwc/terms/occurrenceID"/>' . "\n");
            fwrite($WRITE, '    <field index="2" term="http://rs.tdwg.org/dwc/terms/eventDate"/>' . "\n");
            fwrite($WRITE, '    <field index="3" term="http://rs.tdwg.org/dwc/terms/decimalLatitude"/>' . "\n");
            fwrite($WRITE, '    <field index="4" term="http://rs.tdwg.org/dwc/terms/decimalLongitude"/>' . "\n");
            fwrite($WRITE, '    <field index="5" term="http://rs.tdwg.org/dwc/terms/scientificName"/>' . "\n");
            fwrite($WRITE, '    <field index="6" term="http://rs.tdwg.org/dwc/terms/taxonRank"/>' . "\n");
            fwrite($WRITE, '    <field index="7" term="http://rs.tdwg.org/dwc/terms/kingdom"/>' . "\n");
            fwrite($WRITE, '    <field index="8" term="http://rs.tdwg.org/dwc/terms/phylum"/>' . "\n");
            fwrite($WRITE, '    <field index="9" term="http://rs.tdwg.org/dwc/terms/class"/>' . "\n");
            fwrite($WRITE, '    <field index="10" term="http://rs.tdwg.org/dwc/terms/family"/>' . "\n");
        }
        elseif($folder == "usgs_nonindigenous_aquatic_species")
        {
            fwrite($WRITE, '    <field index="0" term="http://rs.gbif.org/terms/1.0/RLSID"/>' . "\n");
            fwrite($WRITE, '    <field index="1" term="http://rs.tdwg.org/dwc/terms/occurrenceID"/>' . "\n");
            fwrite($WRITE, '    <field index="2" term="http://rs.tdwg.org/dwc/terms/eventDate"/>' . "\n");
            fwrite($WRITE, '    <field index="3" term="http://rs.tdwg.org/dwc/terms/decimalLatitude"/>' . "\n");
            fwrite($WRITE, '    <field index="4" term="http://rs.tdwg.org/dwc/terms/decimalLongitude"/>' . "\n");
            fwrite($WRITE, '    <field index="5" term="http://rs.tdwg.org/dwc/terms/scientificName"/>' . "\n");
            fwrite($WRITE, '    <field index="6" term="http://rs.tdwg.org/dwc/terms/taxonRank"/>' . "\n");
            fwrite($WRITE, '    <field index="7" term="http://rs.tdwg.org/dwc/terms/kingdom"/>' . "\n");
            fwrite($WRITE, '    <field index="8" term="http://rs.tdwg.org/dwc/terms/family"/>' . "\n");
            fwrite($WRITE, '    <field index="9" term="http://rs.tdwg.org/dwc/terms/basisOfRecord"/>' . "\n");
            fwrite($WRITE, '    <field index="10" term="http://rs.tdwg.org/dwc/terms/group"/>' . "\n");
            fwrite($WRITE, '    <field index="11" term="http://rs.tdwg.org/dwc/terms/genus"/>' . "\n");
            fwrite($WRITE, '    <field index="12" term="http://rs.gbif.org/terms/1.0/species"/>' . "\n");
            fwrite($WRITE, '    <field index="13" term="http://rs.tdwg.org/dwc/terms/vernacularName"/>' . "\n");
            fwrite($WRITE, '    <field index="14" term="http://rs.tdwg.org/dwc/terms/stateProvince"/>' . "\n");
            fwrite($WRITE, '    <field index="15" term="http://rs.tdwg.org/dwc/terms/county"/>' . "\n");
            fwrite($WRITE, '    <field index="16" term="http://rs.tdwg.org/dwc/terms/locality"/>' . "\n");
            fwrite($WRITE, '    <field index="17" term="http://purl.org/dc/terms/date"/>' . "\n");
            fwrite($WRITE, '    <field index="18" term="http://rs.tdwg.org/dwc/terms/year"/>' . "\n");
            fwrite($WRITE, '    <field index="19" term="http://rs.tdwg.org/dwc/terms/month"/>' . "\n");
            fwrite($WRITE, '    <field index="20" term="http://rs.tdwg.org/dwc/terms/day"/>' . "\n");
            fwrite($WRITE, '    <field index="21" term="http://rs.tdwg.org/dwc/terms/catalogNumber"/>' . "\n");
        }
        fwrite($WRITE, '  </core>' . "\n");
        fwrite($WRITE, '</archive>' . "\n");
        fclose($WRITE);
    }

    //start for eMammal ==============================================================================================================
    function generate_eMammal_archive($local_path)
    {
        $folder = "eMammal";
        
        self::create_folder_if_does_not_exist($folder);
        
        //first row - headers of text file
        $WRITE = Functions::file_open($this->destination['eMammal'], "w");
        fwrite($WRITE, implode("\t", $this->fields['eMammal']) . "\n");
        fclose($WRITE);
        
        foreach(glob("$local_path/*.csv") as $filename)
        {
            echo "\n$filename";
            self::process_csv($filename, "eMammal");
            // break; //debug - just process 1 csv file
        }
        
        self::last_part($folder);
        if($this->debug) print_r($this->debug);
    }

    function process_rec_eMammal($rec)
    {
        // id   occurrenceID    eventDate   decimalLatitude decimalLongitude    scientificName  taxonRank   kingdom phylum  class   family
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['id'];
        $rek['occurrenceID']     = $rec['Sequence ID'];
        $rek['eventDate']        = $rec['Begin Time'];
        $rek['decimalLatitude']  = $rec['Actual Lat'];
        $rek['decimalLongitude'] = $rec['Actual Lon'];
        $rek['scientificName']   = $rec['Species Name'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = 'Animalia';
        $rek['phylum']           = 'Chordata';
        $rek['class']            = 'Mammalia';
        $rek['family']           = '';
        
        [Subproject] => White Rock
        [Treatment] => 
        [Deployment Name] => WhiteRock02_062016
        [ID Type] => Researcher
        [Deploy ID] => d20200
        [Sequence ID] => d20200s10
        [Begin Time] => 2016-06-16T10:42:28
        [End Time] => 2016-06-16T10:43:24
        [Species Name] => No Animal
        [Common Name] => No Animal
        [Age] => 
        [Sex] => 
        [Individual] => 
        [Count] => 1
        [Actual Lat] => 48.01166
        [Actual Lon] => -108.00895
        [id] => 1
        */
        
        $taxon = $rec['Species Name'];
        if(stripos($taxon, 'Vehicle') !== false || stripos($taxon, 'Human') !== false ) return false; //string is found
        if(stripos($taxon, 'Unknown') !== false || stripos($taxon, 'Animal') !== false ) return false; //string is found
        if(stripos($taxon, 'Camera') !== false || stripos($taxon, 'Calibration') !== false ) return false; //string is found
        if(stripos($taxon, 'sapiens') !== false || stripos($taxon, 'Homo') !== false ) return false; //string is found
        if(stripos($taxon, 'other') !== false || stripos($taxon, 'species') !== false ) return false; //string is found
        
        //total of 11 columns
        $rek[] = $rec['id'];
        $rek[] = $rec['Sequence ID'];
        $rek[] = $rec['Begin Time'];
        $rek[] = $rec['Actual Lat'];
        $rek[] = $rec['Actual Lon'];
        if(stripos($taxon, ' spp.') !== false || stripos($taxon, ' sp.') !== false ) //string is found
        {
            $taxon = str_ireplace(" spp.", "", $taxon);
            $taxon = str_ireplace(" sp.", "", $taxon);
            $rek[] = $taxon;
            $rek[] = '';
        }
        else
        {
            $rek[] = $taxon;
            $rek[] = 'species';
        }
        $rek[] = 'Animalia';
        $rek[] = 'Chordata';
        $rek[] = 'Mammalia';
        $rek[] = '';
        return implode("\t", $rek);
    }
    //end for eMammal ================================================================================================================

    function generate_ReefLifeSurvey_archive($params)
    {
        $folder = "reef_life_survey";
        if(!file_exists(CONTENT_RESOURCE_LOCAL_PATH . "$folder"))
        {
            $command_line = "mkdir " . CONTENT_RESOURCE_LOCAL_PATH . "$folder"; //may need 'sudo mkdir'
            $output = shell_exec($command_line);
            // if (!mkdir(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey", 0777)) {
            //     die('\nFailed to create folders...\n');
            // }
        }
        
        if(!$WRITE = Functions::file_open($this->destination['reef life survey'], "w")) return;
        fwrite($WRITE, implode("\t", $this->fields['reef life survey']) . "\n");
        fclose($WRITE);
        
        $collections = array("Global reef fish dataset", "Invertebrates");
        // $collections = array("Invertebrates"); //debug only
        foreach($collections as $coll)
        {
            $url = $params[$coll]; //csv url path
            $temp_path = Functions::save_remote_file_to_local($url, $this->download_options);
            self::process_csv($temp_path, "reef life survey", $coll);
            unlink($temp_path);
        }

        self::last_part($folder);
        if($this->debug) print_r($this->debug);
    }
    
    private function last_part($folder)
    {
        self::generate_meta_xml($folder); //creates a meta.xml file

        //copy 2 files inside /reef_life_survey/
        copy(CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt", CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt");
        copy(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml"        , CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml");

        //create reef_life_survey.tar.gz
        $command_line = "zip -rj " . CONTENT_RESOURCE_LOCAL_PATH . $folder . ".zip " . CONTENT_RESOURCE_LOCAL_PATH . $folder . "/"; //may need 'sudo zip -rj...'
        $output = shell_exec($command_line);
    }
    
    function process_csv($csv_file, $dbase, $collection = "")
    {
        if($dbase == "reef life survey") $field_count = 20;
        elseif($dbase == "eMammal")      $field_count = 16;

        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r"))
        {
            echo "\nerror 1\n";
            return;
        }
        if(!$WRITE = Functions::file_open($this->destination[$dbase], "a"))
        {
            echo "\nerror 2\n";
            return;
        }
        
        
        while(!feof($file))
        {
            $temp = fgetcsv($file);
            $i++;
            if(($i % 10000) == 0) echo number_format($i) . "\n";
            if($i == 1)
            {
                $fields = $temp;
                // print_r($fields); //exit;
                if(count($fields) != $field_count)
                {
                    $this->debug["not20"][$fields[0]] = '';
                    continue;
                }
            }
            else
            {
                $this->ctr++;
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                if(count($temp) != $field_count)
                {
                    $this->debug["not20"][$temp[0]] = 1;
                    continue;
                }
                
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                
                if($rec)
                {
                    $rec['id'] = $this->ctr;
                    // print_r($rec); exit;
                    if    ($dbase == "reef life survey") $row = self::process_rec_RLS($rec, $collection);
                    elseif($dbase == "eMammal")          $row = self::process_rec_eMammal($rec);
                    else echo "\n --undefine dbase-- \n";
                    if($row) fwrite($WRITE, $row . "\n");
                }
                
                // if($i > 5) break;  //debug only
            }
        } // end while{}
        fclose($file);
        fclose($WRITE);
    }
    
    /* sample "Invertebrates" record
    [FID] => M2_INVERT_DATA.1
    [Key] => 1
    [SurveyID] => 62003108
    [Country] => Indonesia
    [Ecoregion] => Western Sumatra
    [Realm] => Western Indo-Pacific
    [SiteCode] => ACEH22
    [Site] => Ujung Tunku Nth
    [SiteLat] => 5.8829
    [SiteLong] => 95.2512
    [SurveyDate] => 2009-03-01T00:00:00
    [Depth] => 5
    [Phylum] => Echinodermata
    [Class] => Echinoidea
    [Family] => Echinometridae
    [Taxon] => Echinostrephus aciculatus
    [Block] => 1
    [Total] => 100
    [Diver] => RSS
    [geom] => POINT (95.25118 5.88289)
    [id] => 1

    sample "Global reef fish dataset" record
    [FID] => M1_DATA.1
    [Key] => 1
    [SurveyID] => 62003097
    [Country] => Indonesia
    [Ecoregion] => Western Sumatra
    [Realm] => Western Indo-Pacific
    [SiteCode] => ACEH11
    [Site] => Bate Bukulah
    [SiteLat] => 5.8672
    [SiteLong] => 95.2696
    [SurveyDate] => 2009-02-25T00:00:00
    [Depth] => 9
    [Phylum] => Chordata
    [Class] => Actinopterygii
    [Family] => Labridae
    [Taxon] => Halichoeres marginatus
    [Block] => 2
    [Total] => 1
    [Diver] => GJE
    [geom] => POINT (95.2696 5.86718)
    */

    function process_rec_RLS($rec, $collection)
    {
        // id   occurrenceID    eventDate   decimalLatitude decimalLongitude    scientificName  taxonRank   kingdom phylum  class   family
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['id'];
        $rek['occurrenceID']     = $rec['SurveyID'];
        $rek['eventDate']        = $rec['SurveyDate'];
        $rek['decimalLatitude']  = $rec['SiteLat'];
        $rek['decimalLongitude'] = $rec['SiteLong'];
        $rek['scientificName']   = $rec['Taxon'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = 'Animalia';
        $rek['phylum']           = $rec['Phylum'];
        $rek['class']            = $rec['Class'];
        $rek['family']           = $rec['Family'];
        */
        
        //total of 11 columns
        $rek[] = $rec['id'];
        if($collection == "Global reef fish dataset") $rek[] = $rec['SurveyID'] . "_" . $rec['id'];
        elseif($collection == "Invertebrates")        $rek[] = $rec['FID'];
        $rek[] = $rec['SurveyDate'];
        $rek[] = $rec['SiteLat'];
        $rek[] = $rec['SiteLong'];
        
        $taxon = $rec['Taxon'];
        if(stripos($taxon, ' spp.') !== false || stripos($taxon, ' sp.') !== false ) //string is found
        {
            $taxon = str_ireplace(" spp.", "", $taxon);
            $taxon = str_ireplace(" sp.", "", $taxon);
            $rek[] = $taxon;
            $rek[] = '';
        }
        else
        {
            $rek[] = $taxon;
            $rek[] = 'species';
        }
        
        $rek[] = 'Animalia';
        $rek[] = $rec['Phylum'];
        $rek[] = $rec['Class'];
        $rek[] = $rec['Family'];
        return implode("\t", $rek);
    }
    
    private function create_folder_if_does_not_exist($folder)
    {
        if(!file_exists(CONTENT_RESOURCE_LOCAL_PATH . "$folder")) {
            $command_line = "mkdir " . CONTENT_RESOURCE_LOCAL_PATH . "$folder"; //may need 'sudo mkdir'
            $output = shell_exec($command_line);
        }
    }
    
}
?>
