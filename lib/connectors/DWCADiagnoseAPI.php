<?php
namespace php_active_record;
/* This will contain functions to diagnose EOL DWC-A files */
class DWCADiagnoseAPI
{
    function __construct()
    {
        $this->file['taxon']             = "http://rs.tdwg.org/dwc/terms/taxonID";
        $this->file['occurrence']        = "http://rs.tdwg.org/dwc/terms/occurrenceID";
        $this->file['reference']         = "http://purl.org/dc/terms/identifier";
        $this->file['document']          = "http://purl.org/dc/terms/identifier";
        $this->file['agent']             = "http://purl.org/dc/terms/identifier";
        $this->file['vernacularname']    = "http://rs.tdwg.org/dwc/terms/vernacularName";
        $this->file['measurementorfact'] = "http://rs.tdwg.org/dwc/terms/measurementID"; //newly added
        // $this->file['association']       = "http://eol.org/schema/associationID";
    }
    function investigate_extension($resource_id, $tab_file) //$tab_file e.g. 'taxon.tab'
    {
        $url = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/".$tab_file;
        echo "\nProcessing file ($url)\n";
        $i = 0;
        foreach(new FileIterator($url) as $line_number => $temp) {
            if(!$temp) continue;
            $temp = explode("\t", $temp);
            $i++;
            if(($i % 300000) == 0) echo "\n count:[".number_format($i)."] ";
            if($i == 1) {
                $fields = $temp;
            }
            else {
                $rec = array();
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); //exit;
                /* === START CUSTOMIZE INVESTIGATION HERE === WikiData 71 resource */
                if($resource_id == 71) {                
                    /* if(@$rec['subtype'] == 'map') print_r($rec); */

                    /*
                    if(stripos($rec['accessURI'], "/Ladybird.jpg") !== false) { //string is found
                        print_r($rec);
                    }
                    */

                    /* DATA-1798 */
                    /*
                    if(stripos($rec['agentID'], "67c0dfecb59b013950f6f712da39eb00") !== false) { //string is found
                        print_r($rec);
                    }
                    */
                    
                    // /* investigate agent.tab
                    if(stripos($rec['identifier'], "ed3ef7203b7002c3022fbfe0ac9ed0d9") !== false) { //string is found
                        print_r($rec);
                    }
                    // */
                    
                    
                    
                }
                /* === END CUSTOMIZE INVESTIGATION HERE === */

                /* === START CUSTOMIZE INVESTIGATION HERE === */
                /* === END CUSTOMIZE INVESTIGATION HERE === */

                /* === START CUSTOMIZE INVESTIGATION HERE === */
                /* === END CUSTOMIZE INVESTIGATION HERE === */
            }
        }
    }

    function check_unique_ids($resource_id, $file_extension = ".tab")
    {
        echo "\n----------Checking unique IDs [$resource_id]...----------\n";
        if(!file_exists(CONTENT_RESOURCE_LOCAL_PATH . $resource_id)) {
            echo "\nDir does not exist: [".CONTENT_RESOURCE_LOCAL_PATH . $resource_id."]\n";
        }
        else {
            $harvester = new ContentArchiveReader(NULL, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/");
            $tables = $harvester->tables;
            $tables = array_keys($tables);
            // $tables = array_diff($tables, array("http://rs.tdwg.org/dwc/terms/measurementorfact")); //exclude measurementorfact
            $tables = array_diff($tables, array("http://rs.gbif.org/terms/1.0/vernacularname")); //exclude vernacular name
            $tables = array_diff($tables, array("http://eol.org/schema/association")); //exclude association name
            print_r($tables);
            foreach($tables as $table) {
                $records = $harvester->process_row_type($table);
                self::process_fields($records, pathinfo($table, PATHINFO_BASENAME));
                $records = null;
            }
        }
        echo "\n----------end Checking unique IDs----------\n";
    }

    private function process_fields($records, $class)
    {
        $temp_ids = array();
        echo "\n[$class]";
        foreach($records as $rec) {
            $keys = array_keys($rec);
            if(!($field_index_key = @$this->file[$class])) {
                echo "\nnot yet defined [$class]\n";
                print_r($keys);
                print_r($rec);
                return false;
            }
            
            /*good debug
            if(!isset($rec[$field_index_key])) {
                print_r($rec);
                echo "\nfield_index_key = $field_index_key\n";
            }
            */
            
            if(!isset($temp_ids[$rec[$field_index_key]])) $temp_ids[$rec[$field_index_key]] = '';
            else {
                if($val = $rec[$field_index_key]) {
                    echo "\n -- not unique ID in [$class] - {" . $rec[$field_index_key] . "} - [$field_index_key]";
                    return false;
                }
            }
        }
        echo " -- OK\n";
        return true;
    }

    function get_irn_from_media_extension($resource_id)
    {
        // e.g. https://collections.nmnh.si.edu/services/media.php?env=mammals&irn=7005395
        // e.g. https://collections.nmnh.si.edu/media/index.php?irn=10295934
        $irns = array();
        $harvester = new ContentArchiveReader(NULL, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/");
        // $tables = $harvester->tables; print_r($tables);
        $records = $harvester->process_row_type('http://eol.org/schema/media/document');
        // print_r($records); echo "\n".count($records)."\n";
        foreach($records as $rec) {
            $url = $rec['http://rs.tdwg.org/ac/terms/accessURI'];
            if(preg_match("/irn=(.*?)elix/ims", $url."elix", $a)) $irns[$a[1]] = '';
        }
        return array_keys($irns);
    }

    function cannot_delete() // a utility
    {
        $final = array();
        foreach(new FileIterator(DOC_ROOT . "/public/tmp/cant_delete.txt") as $line => $r) $final[pathinfo($r, PATHINFO_DIRNAME)] = '';
        $final = array_keys($final);
        asort($final);
        foreach($final as $e) echo "\n $e";
        echo "\n";
    }

    function get_undefined_uris() // a utility
    {
        $ids = array("872", "886", "887", "892", "893", "894", "885", "42");
        foreach($ids as $id)
        {
            echo "\nprocessing id [$id]";
            if($undefined_uris = Functions::get_undefined_uris_from_resource($id)) print_r($undefined_uris);
            echo "\nundefined uris: " . count($undefined_uris) . "\n";
        }
    }
    
    function list_unique_taxa_from_XML_resource($resource_id)
    {
        $file = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id" . ".xml";
        $xml = simplexml_load_file($file);
        $taxa = array();
        $objects = array();
        foreach($xml->taxon as $t)
        {
            $do_count = sizeof($t->dataObject);
            if($do_count > 0)
            {
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                $sciname    = Functions::import_decode($t_dwc->ScientificName);
                $taxa[$sciname] = '';
            }
            
            foreach($t->dataObject as $o)
            {
                $t_dc2 = $o->children("http://purl.org/dc/elements/1.1/");
                $identifier = Functions::import_decode($t_dc2->identifier);
                $objects[$identifier] = '';
            }
            
        }
        print_r($taxa);
        print_r($objects);
        echo "\nTotal taxa: " . count($taxa) . "\n";
        echo "\nTotal objects: " . count($objects) . "\n";
    }

    //============================================================ work in progress...
    function check_if_all_vernaculars_have_entries($resource_id, $write_2text_file = false, $url = false, $suggested_fields = false, $sought_field = false)
    {   /* $suggested_fields -> not so much used here... */

        $what['field'] = "taxonID";
        $what['filename'] = "_undefined_vernaculars.txt"; //vernaculars that have missing taxon in taxon.tab
        echo "\nChecking if all ".$what['field']." from vernacular_name.tab have entries in taxon.tab \n";

        if($write_2text_file) $WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . $what['filename'], "w");
        
        $var = self::get_fields_from_tab_file($resource_id, array("taxonID", $what['field']), $url, $suggested_fields, "taxon.tab");
        /* old
        $taxon_ids = array_keys($var['taxonID']);
        $taxon_ids = array_map('trim', $taxon_ids);
        */
        // new
        $taxon_ids = $var['taxonID'];
        unset($var);

        $var = self::get_fields_from_tab_file($resource_id, array("taxonID", $what['field']), $url, $suggested_fields, "vernacular_name.tab");
        $parent_ids = array_keys($var[$what['field']]);
        $parent_ids = array_map('trim', $parent_ids);
        unset($var);

        $undefined = array();
        foreach($parent_ids as $parent_id) {
            // if(!in_array($parent_id, $taxon_ids)) $undefined[$parent_id] = ''; changed to isset()
            if(!isset($taxon_ids[$parent_id])) $undefined[$parent_id] = '';
        }
        if($write_2text_file) {
            foreach(array_keys($undefined) as $id) fwrite($WRITE, $id . "\n");
            fclose($WRITE);
        }
        $undefined = array_keys($undefined);
        if(!$undefined) {
            $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . $what['filename'];
            if(file_exists($file)) unlink($file);
        }
        return $undefined;
    }
    
    function check_if_all_parents_have_entries($resource_id, $write_2text_file = false, $url = false, $suggested_fields = false, $sought_field = false)
    {   /* $suggested_fields -> if taxon.tab is BIG and there are alot of fields, you might want to limit the no. of fields e.g. suggested_fields from BOLDS_DumpsServiceAPI.php */
        if(!$sought_field) {
            $what['field'] = "parentNameUsageID";
            $what['filename'] = "_undefined_parent_ids.txt";
        }
        else {
            if($sought_field == "acceptedNameUsageID") {
                $what['field'] = "acceptedNameUsageID";
                $what['filename'] = "_undefined_acceptedName_ids.txt";
            }
            else exit("\nsought_field ($sought_field) undefined. Will terminate.\n");
        }
        echo "\nChecking if all ".$what['field']." have entries in taxon.tab \n";
        
        if($write_2text_file) $WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . $what['filename'], "w");
        
        $var = self::get_fields_from_tab_file($resource_id, array("taxonID", $what['field']), $url, $suggested_fields, "taxon.tab"); //$url if to the tool genHigherClass | $suggested_fields from BOLDS_DumpsServiceAPI.php

        if($arr = @$var[$what['field']]) $parent_ids = array_keys($arr);
        else                             $parent_ids = array();
        $parent_ids = array_map('trim', $parent_ids);
        /* old
        $taxon_ids = array_keys($var['taxonID']);
        $taxon_ids = array_map('trim', $taxon_ids);
        */
        // new
        $taxon_ids = $var['taxonID'];
        unset($var);

        $undefined = array();
        foreach($parent_ids as $parent_id) {
            // if(!in_array($parent_id, $taxon_ids)) $undefined[$parent_id] = ''; changed to isset
            if(!isset($taxon_ids[$parent_id])) $undefined[$parent_id] = '';
        }
        if($write_2text_file) {
            foreach(array_keys($undefined) as $id) fwrite($WRITE, $id . "\n");
            fclose($WRITE);
        }
        $undefined = array_keys($undefined);
        if(!$undefined) {
            $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . $what['filename'];
            if(file_exists($file)) unlink($file);
        }
        return $undefined;
    }
    function get_fields_from_tab_file($resource_id, $cols, $url = false, $suggested_fields = false, $tab_file) //$tab_file e.g. 'taxon.tab'
    {
        if(!$url) $url = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/".$tab_file;
        if(!file_exists($url)) {
            echo "\nFile does not exist: [$url]\n";
            return;
        }
        else echo "\nProcessing file ($url)\n";
        $i = 0;
        $var = array();
        foreach(new FileIterator($url) as $line_number => $temp) {
            $temp = explode("\t", $temp);
            $i++;
            if(($i % 300000) == 0) echo "\n count:[".number_format($i)."] ";
            if($i == 1) {
                $fields = $temp;
                //-------------------------------------new
                if($suggested_fields) { //suggested_fields from BOLDS_DumpsServiceAPI.php
                    $fields = $suggested_fields;
                    //process even line 1 coz there is no field headers and actual values start from line 1
                    $rec = array();
                    $k = 0;
                    if(!$temp) continue;
                    foreach($temp as $t) {
                        $rec[$fields[$k]] = $t;
                        $k++;
                    }
                    $rec = array_map('trim', $rec);
                    foreach($cols as $col) {
                        if(@$rec[$col]) $var[$col][@$rec[$col]] = '';
                    }
                }
                //-------------------------------------new
            }
            else {
                $rec = array();
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                /* debug only
                if($rec['taxonID'] == 197230) {
                    print_r($rec); print_r($cols); exit;
                }
                */
                $rec = array_map('trim', $rec);
                foreach($cols as $col) {
                    if(@$rec[$col]) $var[$col][@$rec[$col]] = '';
                }
            }
        }
        return $var;
    }
    //============================================================
    function get_all_taxa_without_parent($resource_id, $write_2text_file = false)
    {
        if($write_2text_file) {
            $WRITE = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_taxa_without_parent.txt", "w");
            fwrite($WRITE, 'taxonID' . "\t" . 'scientificName' . "\t" . 'taxonRank' . "\t" . 'source' . "\n");
        }
        //start loop =======================
        $url = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab";
        if(!file_exists($url)) {
            echo "\nFile does not exist: [$url]\n";
            return;
        }
        $i = 0;
        foreach(new FileIterator($url) as $line_number => $temp) {
            $temp = explode("\t", $temp);
            $i++;
            if($i == 1) $fields = $temp;
            else {
                $rec = array();
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                if(!@$rec['parentNameUsageID']) {
                    $no_parent[$rec['taxonID']] = '';
                    $row = $rec['taxonID'] . " -- " . @$rec['scientificName'] . " -- " . @$rec['taxonRank'] . " -- " . @$rec['source'];
                    // echo "\n". $row; //use to display rows
                    if($write_2text_file) {
                        $row = str_ireplace(" -- ", "\t", $row);
                        fwrite($WRITE, $row . "\n");
                    }
                }
            }
        }
        //end loop =========================
        if($write_2text_file) fclose($WRITE);
        return array_keys($no_parent);
    }
    
    function count_rows_in_text_file($local_path = false, $url_path_to_text_file = false)
    {
        if($url_path_to_text_file) $local_path = Functions::save_remote_file_to_local($url_path_to_text_file);
        
        $i = 0;
        foreach(new FileIterator($local_path) as $line_number => $temp)
        {
            $i++;
        }
        echo "local path: $local_path";
        echo "\ntotal records: $i\n";
    }

}
?>
