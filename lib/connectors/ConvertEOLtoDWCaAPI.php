<?php
namespace php_active_record;
/* connector: generic connector to convert EOL XML to EOL DWC-A
    412     EOL China
    306     Reptile DB
    21      AmphibiaWeb
    367     DC Birds video
    829     Zookeys
    
*/
class ConvertEOLtoDWCaAPI
{
    const SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
    const EOL = "http://www.eol.org/voc/table_of_contents#";
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->count = 0;
        // $this->download_options = array('download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1);
    }

    function export_xml_to_archive($params, $xml_file_YN = false, $expire_seconds = 60*60*24*25) //expires in 25 days
    {
        if(!$xml_file_YN) {
            require_library('connectors/INBioAPI');
            $func = new INBioAPI();
            $paths = $func->extract_archive_file($params["eol_xml_file"], $params["filename"], array("timeout" => 7200, "expire_seconds" => $expire_seconds));
            // "expire_seconds" -- false => won't expire; 0 => expires now //debug
            print_r($paths);
            $params["path"] = $paths["temp_dir"];
            self::convert_stream_xml($params);
            $this->archive_builder->finalize(TRUE);
            recursive_rmdir($paths["temp_dir"]); // remove temp dir
        }
        else //is XML file
        {
            $params['path'] = DOC_ROOT . "tmp/";
            $local_xml_file = Functions::save_remote_file_to_local($params['eol_xml_file'], array('file_extension' => "xml", "cache" => 1, "expire_seconds" => $expire_seconds, "timeout" => 7200, "download_attempts" => 2, "delay_in_minutes" => 2)); 
            /* expire_seconds is irrelevant if there is no cache => 1 in save_remote_file_to_local() */ 
            $params['filename'] = pathinfo($local_xml_file, PATHINFO_BASENAME);
            self::convert_stream_xml($params);
            $this->archive_builder->finalize(TRUE);
            unlink($local_xml_file);
        }
        echo "\ntotal rows: $this->count\n";
    }

    private function convert_stream_xml($params)
    {
        $file = $params["path"] . $params["filename"];
        $reader = new \XMLReader();
        $reader->open($file);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon") {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                if($this->resource_id == 1) //346
                {
                    $t = self::assgn_eol_subjects($t);
                    if($t = self::replace_Indet_sp($t)) $i = self::process_t($t, $i, $params);
                }
                else $i = self::process_t($t, $i, $params);
            }
        }
    }

    private function convert_xml($params)
    {
        $file = $params["path"] . $params["filename"];
        echo "\n[$file]\n";
        $contents = file_get_contents($file);
        $contents = str_replace("xml:lang", "xml_lang", $contents);
        // $xml = simplexml_load_string($contents);
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $i = 0;
        foreach($xml->taxon as $t)
        {
            $i = self::process_t($t, $i, $params);
            // break; //debug
        }
    }

    private function process_data_object($objects, $taxon_id, $params, $sciname) //$sciname here was added for AntWeb (24)
    {
        $records = array();
        foreach($objects as $o)
        {
            $o_dc       = $o->children("http://purl.org/dc/elements/1.1/");
            $o_dcterms  = $o->children("http://purl.org/dc/terms/");
            $rec = array();
            foreach(array_keys((array) $o) as $field)
            {
                if(in_array($field, array("agent", "reference"))) continue; //processed separately below
                else 
                {
                    $rec[$field] = (string) $o->$field;
                    if($field == "additionalInformation")
                    {
                        if($val = (string) $o->$field->rating) $rec['rating'] = $val;
                        if($val = (string) $o->$field->subtype) $rec['subtype'] = $val;
                        // if($val = (string) $o->$field->subject) $rec['addl_subject'] = $val; --- don't know yet where to put it
                    }
                }
            }
            foreach(array_keys((array) $o_dc) as $field) $rec[$field] = (string) $o_dc->$field;
            foreach(array_keys((array) $o_dcterms) as $field)
            {
                /* if(in_array($field, array("some_field"))) continue; //how to exclude fields, not in schema */
                $rec[$field] = (string) $o_dcterms->$field;
            }

            //start filters - for quality control ================================================================
            if(@$rec['language'] == "English") $rec['language'] = "En"; //used in resource_id = 120
            if(@$rec['dataType'] == 'http://purl.org/dc/dcmitype/Text' && !@$rec['description']) continue;  //Text objects must have descriptions


            /* for debugging - OK
            if(@$rec['subject'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description') echo "\n ==> ". @$rec['description'] . " ==== ";
            */

            if(self::is_media_object($rec['dataType'])) { //for resource_id = 39
                if(!Functions::valid_uri_url($rec['mediaURL'])) continue; //Media objects must have accessURI
            }

            if($this->resource_id == 24) { //AntWeb per https://eol-jira.bibalex.org/browse/DATA-1713?focusedCommentId=61546&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61546
                if($val = @$rec['mediaURL']) $rec['source'] = self::compute_AntWeb_source_from_mediaURL($val); //source becomes furtherInformationURL in DwCA
                else                         $rec['source'] = self::compute_AntWeb_source_from_sciname($sciname); //for text objects
                if(stripos(@$rec['rightsHolder'], "California Academy of Sciences") !== false) $rec['rightsHolder'] = 'California Academy of Sciences'; //rightsHolder becomes Owner in DwCA. Removes extra ", xxx" string.
                if(!@$rec['rightsHolder'])                                                     $rec['rightsHolder'] = 'California Academy of Sciences'; //text objects need Owner as well, forced.
            }

            if($this->resource_id == 346) {
                if($rec['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') $rec['rating'] = 2;
                
                
            }
            //end filters - for quality control ==================================================================
            
            
            //for references in data_object
            if($obj = @$o->reference)
            {
                if($references = self::process_reference($obj, $taxon_id, $params)) {
                    $reference_ids = array();
                    foreach($references as $reference) {
                        self::create_archive($reference, "reference");
                        $reference_ids[$reference["ref_identifier"]] = '';
                    }
                    $rec["referenceID"] = implode(";", array_keys($reference_ids));
                }
            }
            
            //for agent
            if($obj = @$o->agent)
            {
                if($agents = self::process_agent($obj, $params)) {
                    $agent_ids = array();
                    foreach($agents as $agent) {
                        self::create_archive($agent, "agent");
                        $agent_ids[$agent["agentID"]] = '';
                    }
                    $rec["agentID"] = implode(";", array_keys($agent_ids));
                }
            }

            /* obsolete but good reference to history
            if(in_array($params["dataset"], array("EOL China", "EOL XML")))
            {
                if($val = $o_dc->identifier) $identifier = (string) $val;
                else echo("\n -- find or create your own object identifier -- \n");
            }
            */
            if($val = @$o_dc->identifier) $identifier = (string) $val;
            else
            {
                /* from above
                412     EOL China
                306     Reptile DB
                21      AmphibiaWeb
                367     DC Birds video
                */
                if(in_array($this->resource_id, array(412,306,21,367))) //add here resource_ids
                {
                    $json = json_encode($o);
                    $identifier = md5($json);
                }
                else echo("\n -- find or create your own object identifier -- \n");
            }
            
            $rec["obj_identifier"] = $identifier;
            unset($rec["identifier"]);
            $rec["taxonID"] = $taxon_id;
            $records[] = $rec;
        }
        // print_r($records);
        return $records;
    }

    private function is_media_object($data_type)
    {
        $media = array("http://purl.org/dc/dcmitype/MovingImage", "http://purl.org/dc/dcmitype/Sound", "http://purl.org/dc/dcmitype/StillImage");
        if(in_array($data_type, $media)) return true;
        else return false;
    }
    
    private function process_agent($objects, $params)
    {
        $records = array();
        foreach($objects as $o) {
            if($params["dataset"] == "EOL China") {}
            if(!(string) $o) continue;
            $records[] = array("term_name" => strip_tags((string) $o), "agentRole" => (string) $o{"role"}, "agentID" => md5((string) $o), "term_homepage" => (string) @$o{"homepage"});
        }
        // print_r($records);
        return $records;
    }

    private function process_reference($objects, $taxon_id, $params)
    {
        $records = array();
        foreach($objects as $o)
        {
            $full_reference = trim((string) $o);
            if(!$full_reference) continue;
            
            $identifier = ''; $uri = '';
            if($params["dataset"] == "EOL China") {
                $uri = (string) $o{"url"};
                if(preg_match("/\{(.*?)\}/ims", $uri, $arr)) $identifier = $arr[1];
                else echo("\n -- find or create your own ref identifier -- \n");
            }
            // elseif(in_array($params["dataset"], array("Pensoft XML files", "Amphibiaweb", "NMNH XML files"))) 
            else
            {
                if($val = $o{'doi'}) $identifier = (string) $val;
                if($val = $o{'uri'}) $uri = $val;
            }

            if(!$identifier) $identifier = md5($full_reference);
            
            if(!$identifier) echo "\nModule to create ref identifier and uri for this dataset has not yet been defined!\n";
            $records[] = array("full_reference" => $full_reference, "uri" => $uri, "ref_identifier" => $identifier);
        }
        // print_r($records);
        return $records;
    }

    private function process_synonym($objects, $taxon_id)
    {
        $records = array();
        foreach($objects as $o)
        {
            if(trim((string) $o)) //needed validation for IUCN 211.php and NMNH XML resources
            {
                // print_r($o); //debug
                $records[] = array("scientificName" => (string) $o, "taxonomicStatus" => (string) $o{"relationship"}, 
                                   "taxonID" => str_replace(" ", "_", $o) ,"acceptedNameUsageID" => (string) $taxon_id);
            }
        } 
        // print_r($records);
        return $records;
    }

    private function process_vernacular($objects, $taxon_id)
    {
        $records = array();
        foreach($objects as $o) {
            $lang = trim((string) $o{"xml_lang"}); //not used anymore
            $lang = @$o->attributes('xml', TRUE)->lang; //works OK
            if($val = trim((string) $o)) $records[] = array("vernacularName" => $val, "language" => $lang, "taxonID" => (string) $taxon_id);
        }
        // print_r($records);
        return $records;
    }

    private function create_archive($rec, $type)
    {
        if    ($type == "taxon")       $t = new \eol_schema\Taxon();
        elseif($type == "vernacular")  $t = new \eol_schema\VernacularName();
        elseif($type == "reference")   $t = new \eol_schema\Reference();
        elseif($type == "data object") $t = new \eol_schema\MediaResource();
        elseif($type == "agent")       $t = new \eol_schema\Agent();
        
        // if($type == "data object") print_r($rec);
        
        foreach(array_keys($rec) as $orig_field)
        {
            $field = lcfirst($orig_field);
            if    ($field == "identifier")      $tfield = "taxonID";
            elseif($field == "source")          $tfield = "furtherInformationURL";
            elseif($field == "ref_identifier")  $tfield = "identifier";
            elseif($field == "obj_identifier")  $tfield = "identifier";
            //for dataObject
            elseif($field == "dataType")        $tfield = "type";
            elseif($field == "mimeType")        $tfield = "format";
            elseif($field == "license")         $tfield = "UsageTerms";
            elseif($field == "rightsHolder")    $tfield = "Owner";
            elseif($field == "mediaURL")        $tfield = "accessURI";
            elseif($field == "created")         $tfield = "CreateDate";
            elseif($field == "subject")         $tfield = "CVterm";
            elseif($field == "agentID")
            {
                if($type == "data object")      $tfield = "agentID";
                elseif($type == "agent")        $tfield = "identifier";
            }
            elseif($field == "location")        $tfield = "LocationCreated";
            elseif($field == "rating")          $tfield = "Rating";
            else                                $tfield = $field;
            $t->$tfield = $rec[$orig_field];
        }
        
        if($type == "taxon") {
            if(!isset($this->taxon_ids[$t->taxonID])) {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif($type == "data object") {
            if(!isset($this->media_ids[$t->identifier])) {
                $this->media_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif(in_array($type, array("vernacular"))) {
            $this->archive_builder->write_object_to_file($t);
        }
        elseif($type == "reference") {
            if(!isset($this->reference_ids[$t->identifier])) {
                $this->reference_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif($type == "agent") {
            if(!isset($this->agent_ids[$t->identifier])) {
                $this->agent_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }

    }
    
    private function process_t($t, $i, $params)
    {
        $t_dwc      = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        $t_dc       = $t->children("http://purl.org/dc/elements/1.1/");
        $t_dcterms  = $t->children("http://purl.org/dc/terms/");

        /*
        if($i <= 2) {
            print_r($t_dc);
            print_r($t_dwc);
        }
        else return; //exit;
        */
        
        $i++; if(($i % 5000) == 0) echo "\n $i ";
        $rec = array();
        foreach(array_keys((array) $t_dc) as $field)  $rec[$field] = (string) $t_dc->$field;
        foreach(array_keys((array) $t_dwc) as $field) $rec[$field] = (string) $t_dwc->$field;
        foreach(array_keys((array) $t_dcterms) as $field) {
            if(in_array($field, array("created"))) continue; //exclude these fields, not in schema - CreateDate not found in taxon extension
            $rec[$field] = (string) $t_dcterms->$field;
        }
        
        $taxon_id = false;
        if(isset($t_dc->identifier)) {
            if    ($val = trim($t_dc->identifier))      $taxon_id = $val;
            elseif($val = trim($t_dwc->ScientificName)) $taxon_id = md5($val);
            else continue; //meaning if there is no taxon id and sciname then ignore record
        }
        else echo "\nwent here\n";
        if($val = $taxon_id) $rec["identifier"] = $val;
        else
        {
            if(in_array($params["dataset"], array("NMNH XML files"))) continue; //meaning if there is no taxon id and sciname then ignore record
            else 
            {
                echo "\n -- try to figure how to get taxon_id for this resource: $params[dataset] -- \n";
                // print_r($t); print_r($t_dc); print_r($t_dwc); exit; //debug
            }
        }

        if($obj = @$t->commonName) {
            if($vernaculars = self::process_vernacular($obj, $taxon_id)) {
                foreach($vernaculars as $vernacular) {
                    if($vernacular) self::create_archive($vernacular, "vernacular");
                }
            }
        }
        if($obj = @$t->synonym) {
            if($synonyms = self::process_synonym($obj, $taxon_id)) {
                foreach($synonyms as $synonym) self::create_archive($synonym, "taxon");
            }
        }
        if($obj = @$t->reference) {
            if($references = self::process_reference($obj, $taxon_id, $params)) {
                $reference_ids = array();
                foreach($references as $reference) {
                    self::create_archive($reference, "reference");
                    $reference_ids[$reference["ref_identifier"]] = '';
                }
                $rec["referenceID"] = implode(";", array_keys($reference_ids));
            }
        }
        
        if($obj = @$t->dataObject) {
            if($data_objects = self::process_data_object($obj, $taxon_id, $params, $t_dwc->ScientificName)) {
                foreach($data_objects as $data_object) self::create_archive($data_object, "data object");
            }
        }
        
        $rec = array_map('trim', $rec);
        if($rec['identifier'] && $rec['ScientificName'])
        {
            self::create_archive($rec, "taxon");
            $this->count++;
        }
        return $i;
    }
    
    // =============================================================== start customized functions =============================================
    private function replace_Indet_sp($taxon) //resource_id 346 - NMNH Botany
    {
        $orig_taxon = $taxon;
        $dc = $taxon->children("http://purl.org/dc/elements/1.1/");
        $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        $dcterms = $taxon->children("http://purl.org/dc/terms/");
        echo "\n " . $dc->identifier . " -- sciname: [" . $dwc->ScientificName."]";
        if(is_numeric(stripos($dwc->ScientificName, "Indet")) || is_numeric(stripos($dwc->Kingdom, "Indet")) || is_numeric(stripos($dwc->Phylum, "Indet")) ||
           is_numeric(stripos($dwc->Class, "Indet")) || is_numeric(stripos($dwc->Order, "Indet")) || is_numeric(stripos($dwc->Family, "Indet")) || is_numeric(stripos($dwc->Genus, "Indet")))
        {
            if(isset($dwc->Genus)) $ancestry['Genus'] = (string) $dwc->Genus;
            if(isset($dwc->Family)) $ancestry['Family'] = (string) $dwc->Family;
            if(isset($dwc->Order)) $ancestry['Order'] = (string) $dwc->Order;
            if(isset($dwc->Class)) $ancestry['Class'] = (string) $dwc->Class;
            if(isset($dwc->Phylum)) $ancestry['Phylum'] = (string) $dwc->Phylum;
            if(isset($dwc->Kingdom)) $ancestry['Kingdom'] = (string) $dwc->Kingdom;
            $ancestry['ScientificName'] = (string) $dwc->ScientificName;

            $ancestry = self::get_names($ancestry);
            echo "\n final sciname: [" . $ancestry['ScientificName'] . "]";

            $dwc->ScientificName = $ancestry['ScientificName'];
            if(isset($dwc->Genus)) $dwc->Genus = $ancestry['Genus'];
            if(isset($dwc->Family)) $dwc->Family = $ancestry['Family'];
            if(isset($dwc->Order)) $dwc->Order = $ancestry['Order'];
            if(isset($dwc->Class)) $dwc->Class = $ancestry['Class'];
            if(isset($dwc->Phylum)) $dwc->Phylum = $ancestry['Phylum'];
            if(isset($dwc->Kingdom)) $dwc->Kingdom = $ancestry['Kingdom'];
            if(!$ancestry['ScientificName']) return false;
            else {
                $xml = $taxon->asXML();
                return simplexml_load_string($xml, null, LIBXML_NOCDATA);
            }
        }
        return $orig_taxon;
    }
    private function get_names($ancestry)
    {
        // first loop is to remove all Indet taxon entries
        foreach($ancestry as $rank => $name) {
            if(is_numeric(stripos($name, "Indet"))) {
                $ancestry[$rank] = "";
                echo "\n $rank has [$name] now removed.";
            }
        }
        // if ScientificName is blank, then it will get the immediate higher taxon if it exists
        if($ancestry['ScientificName'] == "") {
            foreach($ancestry as $rank => $name)
            {
                if(trim($name) != "") {
                    echo "\n This will be the new ScientificName: [$name] \n";
                    $ancestry['ScientificName'] = $name;
                    $ancestry[$rank] = "";
                    return $ancestry;
                }
            }
        }
        return $ancestry;
    }
    private function assgn_eol_subjects($taxon)
    {
        foreach($taxon->dataObject as $dataObject) {
            $eol_subjects[] = self::EOL . "SystematicsOrPhylogenetics";
            $eol_subjects[] = self::EOL . "TypeInformation";
            $eol_subjects[] = self::EOL . "Notes";
            if(@$dataObject->subject)
            {
                if(in_array($dataObject->subject, $eol_subjects))
                {
                    $dataObject->addChild("additionalInformation", "");
                    $dataObject->additionalInformation->addChild("subject", $dataObject->subject);
                    if    ($dataObject->subject == self::EOL . "SystematicsOrPhylogenetics") $dataObject->subject = self::SPM . "Evolution";
                    elseif($dataObject->subject == self::EOL . "TypeInformation")            $dataObject->subject = self::SPM . "DiagnosticDescription";
                    elseif($dataObject->subject == self::EOL . "Notes")                      $dataObject->subject = self::SPM . "Description";
                }
            }
        }
        $xml = $taxon->asXML();
        return simplexml_load_string($xml, null, LIBXML_NOCDATA);
    }
    //================================================================================
    private function compute_AntWeb_source_from_mediaURL($mediaURL)
    {
        // $mediaURL = "http://www.antweb.org/images/casent0103174/casent0103174_h_1_high.jpg";
        // $mediaURL = "http://www.antweb.org/images/casent0103174/casent0103174_l_1_high.jpg";
        /* Array (
            [dirname] => http://www.antweb.org/images/casent0103174
            [basename] => casent0103174_l_1_high.jpg
            [extension] => jpg
            [filename] => casent0103174_l_1_high
        ) */
        $parts = explode("_", pathinfo($mediaURL, PATHINFO_FILENAME));
        if(count($parts) >= 4) return "https://www.antweb.org/bigPicture.do?name=".$parts[0]."&shot=".$parts[1]."&number=".$parts[2];
        // https://www.antweb.org/bigPicture.do?name=casent0103174&shot=h&number=1
        // https://www.antweb.org/bigPicture.do?name=casent0103174&shot=l&number=1
    }
    private function compute_AntWeb_source_from_sciname($sciname)
    {   //e.g. https://www.antweb.org/description.do?genus=acanthognathus&species=teledectus
        $url = '';
        $sciname = trim($sciname);
        $parts = explode(" ", $sciname);
        if($val = $parts[0]) $genus = $val;
        $url = "https://www.antweb.org/description.do?genus=".$genus;
        if($species = $parts[1]) $url .= "&species=".$species;
        return $url;
    }

}
?>