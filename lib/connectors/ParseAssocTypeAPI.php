<?php
namespace php_active_record;
/* */
class ParseAssocTypeAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->assoc_prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/";
        $this->service['GNRD text input'] = 'http://gnrd.globalnames.org/name_finder.json?text=';
    }
    /*#################################################################################################################################*/
    function parse_associations($html, $pdf_id)
    {
        $this->pdf_id = $pdf_id; //works but not being used atm.
        $arr = explode("<br>", $html); // print_r($arr); exit;
        /*[35] => 
          [36] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
          [37] => 
          [38] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        */
        $sciname = $arr[0]; //shouldn't be used bec it is uncleaned e.g. "Periploca orichalcella (Clemens), new combination"
        $arr = self::get_relevant_blocks($arr); //print_r($arr); exit;
        $assoc = self::get_associations($arr);
        // exit("\n[$sciname]\n-end assoc-\n");
        return array('assoc' => $assoc);
    }
    private function get_associations($rows)
    {
        $scinames = array();
        foreach($rows as $prefix => $row) {
            $row = str_replace(":", ",", $row);
            $row = str_replace("—", ",", $row);
            $row = str_replace(";", ",", $row);
            $row = trim(Functions::remove_whitespace($row));
            $row = Functions::conv_to_utf8($row);
            $parts = explode(",", $row); //exploded via a comma (","), since GNRD can't detect scinames from block of text sometimes.
            
            $possible_genuses = array();
            
            foreach($parts as $part) {

                // /* remove period from end of string
                //HOST.—Helian thus.  -> remove period
                //Gadus morhua L.     -> don't remove period
                if(substr($part, -1) == ".") {
                    $len = strlen($part);
                    if(substr($part,$len-3,1) != " ") $part = substr($part,0,$len-1); //"Helian thus." -> remove period
                }
                // */
                
                // /* manual: these names are not recordnized by GNRD. So we manually accept it. Alerted Dima (GNRD).
                /* not supposed to be a real species name: https://verifier.globalnames.org/?capitalize=on&format=html&names=Helianthus
                if($part == "Helian thus") {
                    $scinames[$prefix][$part] = '';
                    continue;
                }
                */
                // */
                
                $possible_genus = "";
                $obj = self::run_GNRD_assoc($part); //echo "\nGNRD for: [$part]\n"; print_r($obj); //exit;
                if(!$obj) continue;
                foreach(@$obj->names as $name) {
                    $tmp = $name->scientificName;
                    /*
                    Populus tremuloides
                    P. grandidentata
                    P. canescens
                    Populus balsamifera
                    P. deltoides
                    Salix
                    Populus alba
                    P. nigra
                    P. tremula
                    */
                    // /* possible genus
                    $words = explode(" ", $tmp);
                    if(substr($tmp,1,2) != ". ") {
                        $possible_genus = trim($words[0]);
                        $possible_genuses[] = trim($words[0]);
                    }
                    if(substr($tmp,1,2) == ". " && substr($tmp,0,1) === substr($possible_genus,0,1)) {
                        array_shift($words); //remove first element "P."
                        $new_sci = $possible_genus." ".implode(" ", $words);
                        $scinames["$prefix"][$new_sci] = '';
                        // exit("\ngoes here...\n");
                    }
                    // /* New: good inclusion to complete genus names. Not perfect but better than nothing.
                    elseif(substr($tmp,1,2) == ". ") { //will use $possible_genuses here
                        foreach($possible_genuses as $pg) {
                            if(substr($tmp,0,1) === substr($pg,0,1)) {
                                array_shift($words); //remove first element "P."
                                $new_sci = $pg." ".implode(" ", $words);
                                $scinames["$prefix"][$new_sci] = '';
                            }
                        }
                    }
                    // */
                    else {
                        if(self::is_one_word($tmp)) continue;
                        $scinames[$prefix][$tmp] = '';
                    }
                    // */
                } //end obj->names loop
            }
        }
        // print_r($scinames); exit("\nexit muna\n");
        return $scinames;
    }
    private function get_relevant_blocks($arr)
    {   //print_r($this->assoc_prefixes); exit;
        $final = array();
        foreach($arr as $string) {
            foreach($this->assoc_prefixes as $prefix) {
                // //a space
                // echo "\nprocess: [$string]\n".substr($string,0,strlen($prefix)+1)." === [$prefix ]"."\n"; //debug only
                
                //a space
                if(substr($string,0,strlen($prefix)+1) === "$prefix"." ") {
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string;
                    continue;
                }
                //a period (.)
                if(substr($string,0,strlen($prefix)+1) === "$prefix".".") {
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string;
                    continue;
                }
                //a diff hyphen (—)
                if(substr($string,0,strlen($prefix)+1) === "$prefix"."—") {
                    $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
                    $final[$prefix] = $string;
                    continue;
                }
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        /*Array(
            [HOSTS] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
            [PARASITOIDS] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        )
        */
        return $final;
    }
    private function run_GNRD_assoc($string)
    {
        $string = trim($string);
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }
    private function is_one_word($str)
    {
        $arr = explode(" ", $str);
        if(count($arr) == 1) return true;
        return false;
    }
    /*
    private function run_gnparser_assoc($string)
    {
        $string = self::format_string_4gnparser($string);
        $url = $this->service['GNParser'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
        }
    }
    private function format_string_4gnparser($str)
    {
        // %26 - &
        // %2C - ,
        // %28 - (
        // %29 - )
        // %3B - ;
        // + - space
        $str = str_replace(",", "%2C", $str);
        $str = str_replace("(", "%28", $str);
        $str = str_replace(")", "%29", $str);
        $str = str_replace(";", "%3B", $str);
        $str = str_replace(" ", "+", $str);
        $str = str_replace("&", "%26", $str);
        return $str;
    }
    */
    function write_associations($rec, $taxon, $archive_builder, $meta, $taxon_ids) //2nd param is source taxon object
    {
        $this->taxon_ids = $taxon_ids;
        $this->archive_builder = $archive_builder;
        // print_r($rec); exit("\n111\n");
        /*Array(
            [HOST] => Array(
                    [Populus tremuloides] => 
                    [Populus grandidentata] => 
                )
            [PARASITOID] => Array(
                    [Cirrospilus cinctithorax] => 
                    [Closterocerus tricinctus] => 
                )
            [pdf_id] => SCtZ-0614
        )*/
        
        // HOST(s)/HOST PLANT(s)   associationType=http://purl.obolibrary.org/obo/RO_0002454
        // PARASITOID(s)           associationType=http://purl.obolibrary.org/obo/RO_0002209
        
        foreach($rec as $assoc_type => $scinames) { if($assoc_type == 'pdf_id') continue;
            $scinames = array_keys($scinames);
            $associationType = self::get_assoc_type($assoc_type);
            foreach($scinames as $target_sciname) {
                $occurrence = $this->add_occurrence($taxon, "$taxon->scientificName $associationType");
                $related_taxon = $this->add_taxon($target_sciname);
                $related_occurrence = $this->add_occurrence($related_taxon, "$related_taxon->scientificName $associationType");
                $a = new \eol_schema\Association();
                $a->associationID = md5("$occurrence->occurrenceID $associationType $related_occurrence->occurrenceID");
                $a->occurrenceID = $occurrence->occurrenceID;
                $a->associationType = $associationType;
                $a->targetOccurrenceID = $related_occurrence->occurrenceID;
                $a->source = @$meta[$rec['pdf_id']]['dc.relation.url'];
                if(!isset($this->association_ids[$a->associationID])) {
                    $this->archive_builder->write_object_to_file($a);
                    $this->association_ids[$a->associationID] = '';
                }
            }
        }
        return $this->taxon_ids;
    }
    private function add_occurrence($taxon, $identification_string)
    {
        $occurrence_id = md5($taxon->taxonID . $this->pdf_id . "assoc_occur" . $identification_string);
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon->taxonID;
        if(!isset($this->occurrence_ids[$occurrence_id])) {
            $this->archive_builder->write_object_to_file($o);
            $this->occurrence_ids[$occurrence_id] = '';
        }
        return $o;
    }
    private function add_taxon($taxon_name)
    {
        /* copied template
        $taxon_id = md5($taxon_name);
        if(isset($this->taxon_ids[$taxon_id])) return $this->taxon_ids[$taxon_id];
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $t->scientificName = $taxon_name;
        $t->order = $order;
        $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = $t;
        return $t;
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = md5($taxon_name);
        $taxon->scientificName  = $taxon_name;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        return $taxon;
    }
    private function get_assoc_type($assoc_type)
    {   /*
        HOST(s)/HOST PLANT(s)   associationType=http://purl.obolibrary.org/obo/RO_0002454
        PARASITOID(s)           associationType=http://purl.obolibrary.org/obo/RO_0002209
        */
        if(stripos($assoc_type, "HOST") !== false) return "http://purl.obolibrary.org/obo/RO_0002454"; //string is found
        if(stripos($assoc_type, "PARASITOID") !== false) return "http://purl.obolibrary.org/obo/RO_0002209"; //string is found
        return false;
    }
}
?>