<?php
namespace php_active_record;
/* connector: [gbif_gereference.php]
This script searches GBIF API occurrence data via taxon (taxon_key)
*/
class GBIFoccurrenceAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array('resource_id' => "gbif", 'expire_seconds' => 5184000, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1); //2 months to expire
        $this->download_options['expire_seconds'] = false; //debug

        //GBIF services
        $this->gbif_taxon_info      = "http://api.gbif.org/v1/species/match?name="; //http://api.gbif.org/v1/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count    = "http://api.gbif.org/v1/occurrence/count?taxonKey=";
        $this->gbif_occurrence_data = "http://api.gbif.org/v1/occurrence/search?taxonKey=";
        
        $this->html['publisher'] = "http://www.gbif.org/publisher/";
        $this->html['dataset'] = "http://www.gbif.org/dataset/";
        
        $this->save_path['cluster'] = DOC_ROOT . "public/tmp/google_maps/cluster/";
        $this->save_path['fusion']  = DOC_ROOT . "public/tmp/google_maps/fusion/";
    }

    function start()
    {
        $scinames = array();
        // $scinames[] = "Gadus morhua";
        $scinames[] = "Anopheles";
        $scinames[] = "Ursus maritimus";
        $scinames[] = "Carcharodon carcharias";
        $scinames[] = "Panthera leo";
        $scinames[] = "Rattus rattus";
        $scinames[] = "Cavia porcellus";
        $scinames[] = "Chanos chanos";
        
        foreach($scinames as $sciname)
        {
            if(!($this->file = Functions::file_open($this->save_path['cluster'].$sciname.".json", "w"))) return;
            if(!($this->file2 = Functions::file_open($this->save_path['fusion'].$sciname.".txt", "w"))) return;
            
            $headers = "catalogNumber, sciname, publisher, publisher_id, dataset, dataset_id, gbifID, latitude, longitude, recordedBy, identifiedBy, pic_url";
            fwrite($this->file2, str_replace(", ", "\t", $headers) . "\n");
            
            $rec = self::get_initial_data($sciname);
            self::get_georeference_data($rec['usageKey']);
            
            
            fclose($this->file);
            fclose($this->file2);
        }
        
        print_r($rec);

        /*
        [offset]        => 0
        [limit]         => 20
        [endOfRecords]  => 
        [count]         => 78842
        [results]       => Array
        */
        
    }

    private function get_georeference_data($taxonKey)
    {
        $offset = 0;
        $limit = 300;
        $continue = true;
        
        $final = array();
        $final['records'] = array();
        
        while($continue)
        {
            $url = $this->gbif_occurrence_data . $taxonKey . "&limit=$limit";
            if($offset) $url .= "&offset=$offset";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $j = json_decode($json);
                $recs = self::write_to_file($j);
                $final['records'] = array_merge($final['records'], $recs);
                
                echo "\n" . count($j->results) . "\n";
                if($j->endOfRecords) $continue = false;
            }
            $offset += $limit;
        }
        
        $final['count'] = count($final['records']);
        $json = json_encode($final);
        fwrite($this->file, "var data = ".$json);
        
    }

    private function write_to_file($j)
    {
        $recs = array();
        $i = 0;
        foreach($j->results as $r)
        {
            // if($i > 2) break; //debug
            $i++;
            if(@$r->decimalLongitude && @$r->decimalLatitude)
            {
                $rec = array();
                $rec['catalogNumber']   = (string) @$r->catalogNumber;
                $rec['sciname']         = self::get_sciname($r);
                $rec['publisher']       = self::get_org_name('publisher', $r->publishingOrgKey);
                $rec['publisher_id']    = $r->publishingOrgKey;
                if($val = @$r->institutionCode) $rec['publisher'] .= " ($val)";
                $rec['dataset']         = self::get_org_name('dataset', $r->datasetKey);
                $rec['dataset_id']      = $r->datasetKey;
                $rec['gbifID']          = $r->gbifID;
                $rec['lat']             = $r->decimalLatitude;
                $rec['lon']             = $r->decimalLongitude;

                // if($val = @$r->recordedBy)           $rec['recordedBy'] = $val;
                // if($val = @$r->identifiedBy)         $rec['identifiedBy'] = $val;
                // if($val = @$r->media[0]->identifier) $rec['pic_url'] = $val;
                
                $rec['recordedBy']   = @$r->recordedBy;
                $rec['identifiedBy'] = @$r->identifiedBy;
                $rec['pic_url']      = @$r->media[0]->identifier;

                self::write_to_fusion_table($rec);

                $recs[] = $rec;
                
                // print_r($r); //exit;
                /*
                Catalogue number: 3043
                Uncinocythere stubbsi
                Institution: Unidad de Ecología (Ostrácodos), Dpto. Microbiología y Ecología, Universidad de Valencia
                Collection: Entocytheridae (Ostracoda) World Database
                */
            }
        }
        return $recs;
    }
    
    private function write_to_fusion_table($rec)
    {   /*
        [catalogNumber] => 1272385
        [sciname] => Chanos chanos (Forsskål, 1775)
        [publisher] => iNaturalist.org (iNaturalist)
        [publisher_id] => 28eb1a3f-1c15-4a95-931a-4af90ecb574d
        [dataset] => iNaturalist research-grade observations
        [dataset_id] => 50c9509d-22c7-4a22-a47d-8c48425ef4a7
        [gbifID] => 1088910889
        [lat] => 1.87214
        [lon] => -157.42781
        [recordedBy] => David R
        [pic_url] => http://static.inaturalist.org/photos/1596294/original.jpg?1444769372
        */
        fwrite($this->file2, implode("\t", $rec) . "\n");
    }
    
    private function get_sciname($r)
    {
        // if($r->taxonRank == "SPECIES") return $r->species;
        return $r->scientificName;
    }
    
    private function get_org_name($org, $id)
    {
        if($html = Functions::lookup_with_cache($this->html[$org] . $id, $this->download_options))
        {
            if(preg_match("/Full title<\/h3>(.*?)<\/p>/ims", $html, $arr)) return strip_tags(trim($arr[1]));
        }
    }
    
    private function get_initial_data($sciname)
    {
        if($usageKey = self::get_usage_key($sciname))
        {
            $count = Functions::lookup_with_cache($this->gbif_record_count . $usageKey, $this->download_options);
            if($count > 0)
            {
                $rec['usageKey'] = $usageKey;
                $rec["count"] = $count;
                return $rec;
            }
        }
    }

    private function get_usage_key($sciname)
    {
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname, $this->download_options))
        {
            $json = json_decode($json);
            $usageKey = false;
            if(!isset($json->usageKey))
            {
                if(isset($json->note)) $usageKey = self::get_usage_key_again($sciname);
                else {} // e.g. Fervidicoccaceae
            }
            else $usageKey = trim((string) $json->usageKey);
            if($val = $usageKey) return $val;
        }
        return false;
    }

    private function get_usage_key_again($sciname)
    {
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $sciname . "&verbose=true", $this->download_options))
        {
            $usagekeys = array();
            $options = array();
            $json = json_decode($json);
            if(!isset($json->alternatives)) return false;
            foreach($json->alternatives as $rec)
            {
                if($rec->canonicalName == $sciname)
                {
                    $options[$rec->rank][] = $rec->usageKey;
                    $usagekeys[] = $rec->usageKey;
                }
            }
            if($options)
            {
                /* from NCBIGGIqueryAPI.php connector
                if(isset($options["FAMILY"])) return min($options["FAMILY"]);
                else return min($usagekeys);
                */
                return min($usagekeys);
            }
        }
        return false;
    }

}
?>
