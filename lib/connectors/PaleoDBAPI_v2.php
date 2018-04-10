<?php
namespace php_active_record;
// connector: [pbdb_fresh_harvest.php]
class PaleoDBAPI_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months // orig
        $this->download_options['expire_seconds'] = false; //debug

        $this->service["taxon"] = "https://paleobiodb.org/data1.2/taxa/list.json?all_taxa&variant=all&pres=regular&show=full,attr,app,classext,etbasis,ref&rowcount=true&datainfo=true&save=alltaxa.json";
        $this->service["taxon"] = "http://localhost/cp/PaleoDB/TRAM-746/alltaxa.json";

        $this->map['acceptedNameUsageID']       = "acc";
        $this->map['phylum']                    = "phl";
        $this->map['class']                     = "cll";
        $this->map['order']                     = "odl";
        $this->map['family']                    = "fml";
        $this->map['genus']                     = "gnl";

        $this->map['taxonID']                   = "oid";
        // $this->map['taxonID']                   = "vid";
        $this->map['scientificName']            = "nam";
        $this->map['scientificNameAuthorship']  = "att";
        
        // $this->map['furtherInformationURL']     = "oid";
        $this->map['parentNameUsageID']         = "par";
        $this->map['taxonRank']                 = "rnk";
        $this->map['taxonomicStatus']           = "tdf";
        $this->map['nameAccordingTo']           = "ref";
        
        $this->map['vernacularName']            = "nm2";
        


        /* used in PaleoDBAPI.php
        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";
        */
    }

    function get_all_taxa()
    {
        self::parse_big_json_file();
        $this->archive_builder->finalize(TRUE);
    }
    
    private function parse_big_json_file()
    {
        $jsonfile = Functions::save_remote_file_to_local($this->service["taxon"], $this->download_options);
        $i = 0;
        foreach(new FileIterator($jsonfile) as $line_number => $line) {
            $i++;
            // echo "\n-------------------------\n".$line;
            if(substr($line, 0, strlen('{"oid":')) == '{"oid":') {
                $str = substr($line, 0, -1); //remove last char (",") the comma, very important to convert from json to array.
                $arr = json_decode($str, true);
                $taxon_id = self::create_taxon_archive($arr);
                
                // Important: Taxa that have "flg":"V" are synonyms, spelling variants, and variants with alternative ranks. For these we only want to use the taxon information as 
                // outlined in the taxa sheet.  Ignore measurements and vernaculars associated with these records.  
                if($flg = @$arr['flg']) {
                    if($flg == "V") continue;
                }
                self::create_vernacular_archive($arr, $taxon_id);
                self::create_trait_archive($arr, $taxon_id);
            }
            if($i > 100) break; //debug
        }
        unlink($jsonfile);
    }
    private function generate_id_from_array_record($arr)
    {
        $json = json_encode($arr);
        // exit("\n[$json]\n");
        return md5($json);
    }
    private function create_trait_archive($a, $taxon_id)
    {
        if(@$a['ext'] == '0') {
            $rec = array();
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/ExtinctionStatus";
            $rec['measurementValue']    = "http://eol.org/schema/terms/extinct";
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec["taxon_id"]    = $taxon_id;
            // $rec["catnum"]      = md5($rec['measurementType'] . self::generate_id_from_array_record($a));
            $rec["catnum"]      = self::generate_id_from_array_record($a);
            self::add_string_types($rec);
        }
        if(@$a['ext'] == '1') {
            $rec = array();
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/ExtinctionStatus";
            $rec['measurementValue']    = "http://eol.org/schema/terms/extant";
            $rec['measurementUnit']     = '';
            $rec['statisticalMethod']   = '';
            $rec["taxon_id"]    = $taxon_id;
            // $rec["catnum"]      = md5($rec['measurementType'] . self::generate_id_from_array_record($a));
            $rec["catnum"]      = self::generate_id_from_array_record($a);
            self::add_string_types($rec);
        }
        
        /*
        $rec = array();
        $rec["taxon_id"]            = $line['taxon_id'];
        $rec["catnum"]              = "_" . str_replace(" ", "_", $line['text']);
        $rec['measurementOfTaxon']  = "true";
        $rec['measurementType']     = "http://eol.org/schema/terms/Habitat";
        $rec['measurementValue']    = $uri;
        $rec['measurementMethod']   = 'text mining';
        $rec["contributor"]         = '<a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>';
        $rec["source"]              = "http://eol.org/pages/" . str_replace('EOL:', '', $rec["taxon_id"]);
        $rec['measurementRemarks']  = "source text: \"" . $line['text'] . "\"";
        if($val = self::get_reference_ids($line)) $rec['referenceID'] = implode("; ", $val);
        self::add_string_types($rec);
        */
    }
    private function add_string_types($rec)
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"]);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $catnum;

        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        $o->taxonID = $taxon_id;
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function get_uris($spreadsheet)
    {
        $fields = array();
        $fields["sex"] = "sex_uri";
        $fields["typeStatus"] = "typeStatus_uri";
        $fields["institutionCode"] = "institutionCode_uri";
        
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        $spreadsheet_options = $this->spreadsheet_options;
        
        $uris = array();
        echo("\nspreadsheet: [$spreadsheet]\n"); //debug
        if($spreadsheet) {
            if($arr = $func->convert_spreadsheet($spreadsheet, 0, $spreadsheet_options)) {
                 foreach($fields as $key => $value) {
                     $i = 0;
                     if(@$arr[$key]) {
                         foreach($arr[$key] as $item) {
                             $item = trim($item);
                             if($item) {
                                 $temp = $arr[$value][$i];
                                 $temp = trim(str_replace(array("\n"), "", $temp));
                                 $uris[$item] = $temp;
                                 if(!Functions::is_utf8($temp)) echo "\nnot utf8: [$temp]\n";
                             }
                             $i++;
                         }
                     }
                 }
            }
        }
        return $uris;
    }
    
    private function create_vernacular_archive($a, $taxon_id)
    {
        if($vernacular = @$a[$this->map['vernacularName']]) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $taxon_id;
            $v->vernacularName  = $vernacular;
            $v->language        = 'en';
            $this->archive_builder->write_object_to_file($v);
        }
    }
    private function create_taxon_archive($a)
    {
        /*(
            [oid] => txn:10
            [rnk] => 5
            [nam] => Actinomma
            [att] => Haeckel 1862
            [par] => txn:64926
            [rid] => ref:6930
            [ext] => 1
            [noc] => 97
            [fea] => 247.2
            [fla] => 242
            [lea] => 86.3
            [lla] => 70.6
            [tei] => Middle Triassic
            [tli] => Cretaceous
            [siz] => 10
            [exs] => 1
            [cll] => Radiolaria
            [cln] => txn:4
            [odl] => Spumellaria
            [odn] => txn:5
            [fml] => Actinommidae
            [fmn] => txn:64926
            [gnl] => Actinomma
            [gnn] => txn:10
            [jev] => marine
            [jec] => Radiolaria
            [jmo] => passively mobile
            [jmc] => Radiolaria
            [jlh] => planktonic
            [jhc] => Radiolaria
            [jdt] => omnivore
            [jdc] => Radiolaria
            [jco] => silica
            [jtc] => Radiolaria
            [ref] => J. J. Sepkoski, Jr. 2002. A compendium of fossil marine animal genera. Bulletins of American Paleontology 363:1-560
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                  = self::compute_taxonID($a);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        $taxon->acceptedNameUsageID      = self::numerical_part(@$a[$this->map['acceptedNameUsageID']]);
        $taxon->nameAccordingTo          = $a[$this->map['nameAccordingTo']];

        if($val = @$a[$this->map['taxonID']]) $taxon->furtherInformationURL = "https://paleobiodb.org/classic/checkTaxonInfo?taxon_no=" . self::numerical_part($val);

        if(!@$a[$this->map['acceptedNameUsageID']]) { //acceptedNameUsageID => "acc"
            $taxon->parentNameUsageID = self::numerical_part(@$a[$this->map['parentNameUsageID']]);
            $taxon->phylum  = @$a[$this->map['phylum']];
            $taxon->class   = @$a[$this->map['class']];
            $taxon->order   = @$a[$this->map['order']];
            $taxon->family  = @$a[$this->map['family']];
            $taxon->genus   = @$a[$this->map['genus']];
        }

        if($rank = @$taxon->taxonRank) { //by Eli alone: if taxon is genus then exclude genus from ancestry.
            if(in_array($rank, array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) $taxon->$rank = "";
        }
        $this->archive_builder->write_object_to_file($taxon);
        return $taxon->taxonID;
    }
    private function compute_taxonID($a)
    {
        if($vid = @$a['vid']) return self::numerical_part($a['oid'])."-".self::numerical_part($vid);
        else                  return self::numerical_part($a['oid']);
    }
    private function numerical_part($var)
    {
        if(!$var) return "";
        $temp = explode(":", $var);
        return $temp[1];
    }
    private function compute_taxonRank($a)
    {
        $mappings = self::get_rank_mappings();
        if($num = @$a[$this->map['taxonRank']]) {
            if($val = $mappings[$num]) return $val;
        }
        return "";
    }
    private function compute_taxonomicStatus($a)
    {
        $mappings = self::get_taxon_status_mappings();
        if($str_index = @$a[$this->map['taxonomicStatus']]) {
            if($val = $mappings[$str_index]) return $val;
        }
        return "";
    }
    private function get_taxon_status_mappings()
    {
        $s['invalid subgroup of'] = "invalid subgroup";
        $s['nomen dubium'] = "nomen dubium";
        $s['nomen nudum'] = "nomen nudum";
        $s['nomen oblitum'] = "nomen oblitum";
        $s['nomen vanum'] = "nomen vanum";
        $s['objective synonym of'] = "objective synonym";
        $s['replaced by'] = "replaced";
        $s['subjective synonym of'] = "subjective synonym";
        $s['corrected to'] = "corrected";
        $s['misspelling of'] = "misspelling";
        $s['obsolete variant of'] = "obsolete variant";
        $s['reassigned as'] = "reassigned";
        $s['recombined as'] = "recombined";
        return $s;
    }
    private function get_rank_mappings()
    {
        $r[2] = "subspecies";
        $r[3] = "species";
        $r[4] = "subgenus";
        $r[5] = "genus";
        $r[6] = "subtribe";
        $r[7] = "tribe";
        $r[8] = "subfamily";
        $r[9] = "family";
        $r[10] = "superfamily";
        $r[11] = "infraorder";
        $r[12] = "suborder";
        $r[13] = "order";
        $r[14] = "superorder";
        $r[15] = "infraclass";
        $r[16] = "subclass";
        $r[17] = "class";
        $r[18] = "superclass";
        $r[19] = "subphylum";
        $r[20] = "phylum";
        $r[21] = "superphylum";
        $r[22] = "subkingdom";
        $r[23] = "kingdom";
        $r[25] = "unranked clade";
        $r[26] = "informal";
        return $r;
    }

}
?>