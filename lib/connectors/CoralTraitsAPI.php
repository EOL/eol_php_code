<?php
namespace php_active_record;
/* connector: [coraltraits.php] */
class CoralTraitsAPI
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        $this->for_mapping = array();
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*25, //expires in 25 days
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0; //debug only
        $this->partner_source_csv = "https://ndownloader.figshare.com/files/3678603";
        $this->download_version = "ctdb_1.1.1";
        $this->spreadsheet_for_mapping  = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Coraltraits/coraltraits_mapping.xlsx"; //from Jen (DATA-1793)
        $this->spreadsheet_for_mapping2 = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Coraltraits/coraltraits_mapping_Eli.xlsx";
    }
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        self::load_zip_contents();
        $this->meta['trait_name'] = self::initialize_spreadsheet_mapping('trait_name'); // print_r($this->meta['trait_name']['Zooxanthellate']); exit;
        $this->meta['value'] = self::initialize_spreadsheet_mapping('value');           // print_r($this->meta['value']['Winter']); exit;
        
        $temp1 = self::initialize_spreadsheet_mapping('unit');
        $temp2 = self::initialize_spreadsheet_mapping('unit', $this->spreadsheet_for_mapping2);
        $this->meta['standard_unit'] = array_merge($temp1, $temp2);
        echo "\n".count($temp1)."\n";
        echo "\n".count($temp2)."\n";
        echo "\ntotal: ".count($this->meta['standard_unit'])."\n";
        // print_r($this->meta['standard_unit']); exit("\n");

        self::process_csv('resources'); //this will initialize $this->refs
        self::process_csv('data'); //this is the main csv file
        
        //remove temp folder and file
        recursive_rmdir($this->TEMP_FILE_PATH); // remove temp dir
        echo ("\n temporary directory removed: [$this->TEMP_FILE_PATH]\n");
        
        $this->archive_builder->finalize(true);
        print_r($this->debug);
        Functions::start_print_debug($this->debug, $this->resource_id);
        // exit("\n-stop muna\n");
    }
    private function process_csv($type)
    {
        $i = 0;
        /* works ok if you don't need to format/clean the entire row.
        $file = Functions::file_open($this->text_path[$type], "r");
        while(!feof($file)) { $row = fgetcsv($file); }
        fclose($file);
        */
        foreach(new FileIterator($this->text_path[$type]) as $line_number => $line) {
            if(!$line) continue;
            $line = str_replace('\flume\"""', 'flume"', $line); //clean entire row. fix for 'data' csv
            $row = str_getcsv($line);
            if(!$row) continue; //continue; or break; --- should work fine
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 10000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    echo "\n--------------\n"; print_r($values);
                    echo("\nWrong CSV format for this row.\n"); exit;
                    @$this->debug['wrong csv'][$type]++;
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($rec); exit;
                if($type == "data")          self::process_data_record($rec);
                elseif($type == "resources") self::initialize_resources_record($rec);
                
            } //main records
            // if($i > 5) break;
        } //main loop
        // fclose($file);
    }
    private function process_data_record($rec)
    {
        self::create_taxon($rec);
        self::create_trait($rec);
    }
    private function create_trait($rec)
    {
        /*Array(
            [observation_id] => 25
            [access] => 1
            [user_id] => 2
            [specie_id] => 968
            [specie_name] => Micromussa amakusensis
            [location_id] => 1
            [location_name] => Global estimate
            [latitude] => 
            [longitude] => 
            [resource_id] => 40
            [resource_secondary_id] => 48
            [measurement_id] => 
            [trait_id] => 40
            [trait_name] => Ocean basin
            [trait_class] => Geographical
            [standard_id] => 10
            [standard_unit] => cat
            [methodology_id] => 9
            [methodology_name] => Derived from range map
            [value] => pacific
            [value_type] => expert_opinion
            [precision] => 
            [precision_type] => 
            [precision_upper] => 
            [replicates] => 
            [notes] => 
        )
        */
        if($rec['trait_class'] == "Contextual") return;
        
        /* good debug
        // $this->debug['value_type'][$rec['value_type']] = ''; return;
        $this->debug['precision'][$rec['precision']] = ''; 
        $this->debug['precision_type'][$rec['precision_type']] = ''; return;
        */
        
        $rek = array();
        
        /*Occurrence file (you'll need to deduplicate):
        specie_id (or whatever): http://rs.tdwg.org/dwc/terms/taxonID
        observation_id: http://rs.tdwg.org/dwc/terms/occurrenceID
        location_name: http://rs.tdwg.org/dwc/terms/locality
        latitude: http://rs.tdwg.org/dwc/terms/decimalLatitude
        longitude: http://rs.tdwg.org/dwc/terms/decimalLongitude
        */
        $rek['occur']['taxonID'] = $rec['specie_id'];
        $rek['occur']['occurrenceID'] = $rec['observation_id']; //will duplicate below, but its OK.
        $rek['occur']['locality'] = $rec['location_name'];
        $rek['occur']['decimalLatitude'] = $rec['latitude'];
        $rek['occur']['decimalLongitude'] = $rec['longitude'];
        
        /*
        wherever trait_class is NOT "Contextual":
        observation_id: http://rs.tdwg.org/dwc/terms/occurrenceID
        trait_name: http://rs.tdwg.org/dwc/terms/measurementType
        value: http://rs.tdwg.org/dwc/terms/measurementValue
        standard_unit: http://rs.tdwg.org/dwc/terms/measurementUnit
        replicates: http://eol.org/schema/terms/SampleSize
        notes: http://rs.tdwg.org/dwc/terms/measurementRemarks
        */
        $rek['occur']['occurrenceID'] = $rec['observation_id'];
        $mType                  = @$this->meta['trait_name'][$rec['trait_name']]['http://rs.tdwg.org/dwc/terms/measurementType'];
        // print_r($mType); exit;
        $mValue                 = @$this->meta['value'][$rec['value']]['uri'];
        $rek['measurementUnit'] = @$this->meta['standard_unit'][$rec['standard_unit']]['uri'];

        if(!@$this->meta['trait_name'][$rec['trait_name']])       $this->debug['undef trait'][$rec['trait_name']] = '';     //debug only - Jen might add mappings here
        if(!is_numeric($rec['value'])) {
            if(!@$this->meta['value'][$rec['value']])             $this->debug['undef value'][$rec['value']] = '';          //debug only - Jen might add mappings here
        }
        if(!@$this->meta['standard_unit'][$rec['standard_unit']]) $this->debug['undef unit'][$rec['standard_unit']] = '';   //debug only - all 4 found are expected to have blank units

        $rek['SampleSize'] = $rec['replicates'];
        $rek['measurementRemarks'] = $rec['notes'];

        $rek["taxon_id"] = $rec['specie_id'];
        if(is_array($mValue)) print_r($mValue);
        $rek["catnum"] = "_".$mValue;
        $mOfTaxon = "true";

        /* http://rs.tdwg.org/dwc/terms/measurementMethod will be concatenated as "methodology_name (value_type)" */
        $rek['measurementMethod'] = "$rec[methodology_name] ($rec[value_type])";
        $rek['statisticalMethod'] = self::get_smethod($rec['value_type']);
        
        $rek = self::implement_precision_cols($rec, $rek);

        // $rek['statisticalMethod'] = $mapped_record['http://eol.org/schema/terms/statisticalMethod'];
        if($ref_ids = self::write_references($rec)) $rek['referenceID'] = implode("; ", $ref_ids);

        $rek['measurementType']  = $mType;
        $rek['measurementValue'] = $mValue;
        $rek = self::run_special_cases($rec, $rek);

        $ret_MoT_true = $this->func->add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon);
        $occurrenceID = $ret_MoT_true['occurrenceID'];
        $measurementID = $ret_MoT_true['measurementID'];
        
        //Special Case #3: add the other mValue
        if($rec['value'] == 'caespitose_corymbose') {
            $rek['measurementValue'] = 'http://eol.org/schema/terms/caespitose';
            //start add
            $ret_MoT_true = $this->func->add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon);
            $occurrenceID = $ret_MoT_true['occurrenceID'];
            $measurementID = $ret_MoT_true['measurementID'];
        }
    }
    private function run_special_cases($rec, $rek)
    {
        //SPECIAL CASES
        // Where trait_name = Symbiodinium sp. in propagules: if value=no, map measurementValue to http://eol.org/schema/terms/no. If value=yes, map to http://eol.org/schema/terms/symbiontInheritance
        if($rec['trait_name'] == 'Symbiodinium sp. in propagules') {
            if($rec['value'] == 'no') $rek['measurementValue'] = 'http://eol.org/schema/terms/no';
            if($rec['value'] == 'yes') $rek['measurementValue'] = 'http://eol.org/schema/terms/symbiontInheritance';
            return $rek;
        } 
        // Where trait_name= 'Light extinction coefficient' and units=m, map measurementType=http://eol.org/schema/terms/secchiDepth. 
        //                                                Where units=Kd, map measurementType=https://www.wikidata.org/entity/Q902086 and the record should have no units
        if($rec['trait_name'] == 'Light extinction coefficient') {
            if($rec['standard_unit'] == 'm') $rek['measurementType'] = 'http://eol.org/schema/terms/secchiDepth';
            elseif($rec['standard_unit'] == 'Kd') {
                $rek['measurementType'] = 'https://www.wikidata.org/entity/Q902086';
                $rek['measurementUnit'] = ''; //set to blank
            }
            return $rek;
        }
        // where value= caespitose_corymbose, create two records sharing all metadata, one with value= http://eol.org/schema/terms/corymbose and one with 
        //                                                                                      value= http://eol.org/schema/terms/caespitose
        if($rec['value'] == 'caespitose_corymbose') {
            $rek['measurementValue'] = 'http://eol.org/schema/terms/corymbose';
            return $rek;
        }
        
        /*
        where value= massive and columnar, create two records sharing all metadata, one with value= http://eol.org/schema/terms/columnar and one with value= http://purl.obolibrary.org/obo/PORO_0000389
        where value= arborescent_tables, create two records sharing all metadata, one with value= http://eol.org/schema/terms/arborescent and one with value= http://eol.org/schema/terms/explanate
        where trait_name=Abundance GBR, measurementValue is always the same, but source value determines the content of the http://purl.obolibrary.org/obo/NCIT_C70589 element. Rare: https://www.wikidata.org/entity/Q3503448, common: https://www.wikidata.org/entity/Q5153621, uncommon: http://eol.org/schema/terms/uncommon
        where statisticalmethod is provided twice- once as a column in the trait_name mapping and once as a child measurement- the child measurement should be kept and the column record discarded
        */
        return $rek;
    }
    private function write_references($rec)
    {
        // [resource_id] => 40
        // [resource_secondary_id] => 48
        /*Array(
            [resource_id] => 543
            [author] => Szmant, A. M.
            [year] => 1986
            [title] => Reproductive ecology of Caribbean reef corals
            [doi_isbn] => 10.1007/bf00302170
            [journal] => Coral Reefs
            [volume_pages] => 5, 43-53
        )*/
        $indexes = array('resource_id', 'resource_secondary_id');
        $ref_ids = array();
        foreach($indexes as $index) {
            if($ref = @$this->refs[$rec[$index]]) {
                $ref_id = $ref['resource_id'];
                $r = new \eol_schema\Reference();
                $r->identifier = $ref_id;
                $r->full_reference = $ref['full_ref'];
                // $r->uri = '';
                $r->doi = $ref['doi_isbn'];
                // $r->publisher = '';
                $r->title = $ref['title'];
                $r->authorList = $ref['author'];
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
                $ref_ids[] = $ref_id;
            }
        }
        return $ref_ids;
    }
    private function implement_precision_cols($rec, $rek)
    {   /*precision, precision_type: precision_type will be the MoF column (this will generate a handful of columns) and precision will be the value for that record
        precision_type:
            standard_deviation: http://semanticscience.org/resource/SIO_000770
            range: http://purl.obolibrary.org/obo/STATO_0000035
            standard_error: http://purl.obolibrary.org/obo/OBI_0000235
            not_given: http://semanticscience.org/resource/SIO_000769
            95_ci: http://purl.obolibrary.org/obo/STATO_0000231
        precision: take numeric values as is. Discard non numeric values
        [precision_type] => Array( --- unique values
                    [] => 
                    [range] => 
                    [standard_deviation] => 
                    [standard_error] => 
                    [95_ci] => 
                    [not_given] => 
                )
        */
        if(!is_numeric($rec['precision'])) return $rek;
        if($rec['precision_type'] == "standard_deviation") $rek['SIO_000770'] = $rec['precision'];
        elseif($rec['precision_type'] == "range") $rek['STATO_0000035'] = $rec['precision'];
        elseif($rec['precision_type'] == "standard_error") $rek['OBI_0000235'] = $rec['precision'];
        elseif($rec['precision_type'] == "not_given") $rek['SIO_000769'] = $rec['precision'];
        elseif($rec['precision_type'] == "95_ci") $rek['STATO_0000231'] = $rec['precision'];
        return $rek;
    }
    private function create_taxon($rec)
    {   /*[specie_id] => 968
          [specie_name] => Micromussa amakusensis
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['specie_id'];
        $taxon->scientificName  = $rec['specie_name'];
        $taxon->kingdom = 'Animalia';
        $taxon->phylum = 'Cnidaria';
        $taxon->class = 'Anthozoa';
        $taxon->order = 'Scleractinia';
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function initialize_resources_record($rec)
    {   /*Array(
        [resource_id] => 543
        [author] => Szmant, A. M.
        [year] => 1986
        [title] => Reproductive ecology of Caribbean reef corals
        [doi_isbn] => 10.1007/bf00302170
        [journal] => Coral Reefs
        [volume_pages] => 5, 43-53
        )*/
        // print_r($rec); exit;
        // Last, F. M. (Year, Month Date Published). Article title. Retrieved from URL
        // Last, F. M. (Year Published) Book. City, State: Publisher.
        $full_ref = "$rec[author]. ($rec[year]). $rec[title]. $rec[journal]. $rec[volume_pages].";
        $full_ref = trim(Functions::remove_whitespace($full_ref));
        $full_ref = str_replace(array('...','..'), ".", $full_ref);
        $rec['full_ref'] = $full_ref;
        $this->refs[$rec['resource_id']] = $rec;
    }
    private function initialize_spreadsheet_mapping($sheet, $file = false)
    {
        if(!$file) $file = $this->spreadsheet_for_mapping;
        $sheets['trait_name'] = 1;
        $sheets['value'] = 2;
        $sheets['unit'] = 3;
        $sheet_no = $sheets[$sheet];
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        // $options['expire_seconds'] = 0; //debug only
        $local_xls = Functions::save_remote_file_to_local($file, $options);
        require_library('XLSParser');
        $parser = new XLSParser();
        debug("\n reading: " . $local_xls . "\n");
        $map = $parser->convert_sheet_to_array($local_xls, $sheet_no);
        $fields = array_keys($map);
        // print_r($map); exit;
        print_r($fields); //exit;
        // foreach($fields as $field) echo "\n$field: ".count($map[$field]); //debug only
        $i = -1;
        foreach($map[$fields[0]] as $var) {
            $i++;
            $rec = array();
            foreach($fields as $fld) $rec[$fld] = $map[$fld][$i];
            $final[$rec[$fields[0]]] = $rec;
        }
        unlink($local_xls);
        // print_r($final); exit;
        return $final;
    }
    private function load_zip_contents()
    {
        $options = $this->download_options;
        $options['file_extension'] = 'zip';
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($local_zip_file = Functions::save_remote_file_to_local($this->partner_source_csv, $options)) {
            $output = shell_exec("unzip -o $local_zip_file -d $this->TEMP_FILE_PATH");
            if(file_exists($this->TEMP_FILE_PATH . "/".$this->download_version."/".$this->download_version."_data.csv")) {
                $this->text_path["data"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_data.csv";
                $this->text_path["resources"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_resources.csv";
                print_r($this->text_path);
                echo "\nlocal_zip_file: [$local_zip_file]\n";
                unlink($local_zip_file);
                return TRUE;
            }
            else return FALSE;
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }
    private function get_smethod($value_type)
    {   /*value_type will get used again: http://eol.org/schema/terms/statisticalMethod
        mapping:
        raw_value: http://www.ebi.ac.uk/efo/EFO_0001444
        median: http://semanticscience.org/resource/SIO_001110
        mean: http://semanticscience.org/resource/SIO_001109
        ANYTHING ELSE: discard
        [value_type] => Array( --- all values for value_type
                    [expert_opinion] => 
                    [group_opinion] => 
                    [raw_value] => 
                    [] => 
                    [mean] => 
                    [median] => 
                    [maximum] => 
                    [model_derived] => 
                )*/
        if($value_type == 'raw_value') return "http://www.ebi.ac.uk/efo/EFO_0001444";
        elseif($value_type == 'median') return "http://semanticscience.org/resource/SIO_001110";
        elseif($value_type == 'mean') return "http://semanticscience.org/resource/SIO_001109";
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    private function fill_up_blank_fieldnames($fields)
    {
        $i = 0;
        foreach($fields as $field) {
            if($field) $final[$field] = '';
            else {
                $i++;
                $final['blank_'.$i] = '';
            } 
        }
        return array_keys($final);
    }
}
?>
