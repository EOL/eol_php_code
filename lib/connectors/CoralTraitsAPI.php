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
            'expire_seconds'     => 60*60*24, //expires in 1 day
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
        $this->meta['trait_name'] = self::initialize_spreadsheet_mapping('trait_name');
        // print_r($this->meta['trait_name']['Zooxanthellate']); exit;
        
        $this->meta['value'] = self::initialize_spreadsheet_mapping('value');
        // print_r($this->meta['value']['Winter']); exit;
        
        $temp1 = self::initialize_spreadsheet_mapping('unit');
        $temp2 = self::initialize_spreadsheet_mapping('unit', $this->spreadsheet_for_mapping2);
        $this->meta['standard_unit'] = array_merge($temp1, $temp2);
        echo "\n".count($temp1)."\n";
        echo "\n".count($temp2)."\n";
        echo "\ntotal: ".count($this->meta['standard_unit'])."\n";
        // print_r($this->meta['standard_unit']); exit("\n");
        
        self::process_csv('data');
        // self::process_csv('resources');
        
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
        while(!feof($file)) { $row = fgetcsv($file);
        }
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
                elseif($type == "resources") self::process_resources_record($rec);
                
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

        if(!@$this->meta['trait_name'][$rec['trait_name']])       $this->debug['undef trait'][$rec['trait_name']] = ''; //debug only
        if(!is_numeric($rec['value'])) {
            if(!@$this->meta['value'][$rec['value']])             $this->debug['undef value'][$rec['value']] = ''; //debug only
        }
        if(!@$this->meta['standard_unit'][$rec['standard_unit']]) $this->debug['undef unit'][$rec['standard_unit']] = ''; //debug only

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
        // $rek['referenceID'] = ;
        $ret_MoT_true = $this->func->add_string_types($rek, $mValue, $mType, $mOfTaxon);
        $occurrenceID = $ret_MoT_true['occurrenceID'];
        $measurementID = $ret_MoT_true['measurementID'];
        
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
    private function process_resources_record($rec)
    {
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
    //****************************************************************************************************************************************************************************
    //****************************************************************************************************************************************************************************
    //****************************************************************************************************************************************************************************
    private function initialize_mapping()
    {
        /* un-comment in real operation
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
        */
        self::initialize_citations_file();
        self::initialize_spreadsheet_mapping();
        // print_r($this->valid_set['map__.falster.2015_mm_']); exit("\n222\n");
    }
    function startx()
    {
        self::main_write_archive();
        $this->archive_builder->finalize(true);
        
        //massage debug for printing
        /*
        $countries = array(); $territories = array();
        if($use_csv = @$this->debug['use.csv']) {
            if($countries = array_keys($use_csv)) asort($countries);
        }
        if($distribution_csv = @$this->debug['distribution.csv']) {
            if($territories = array_keys($distribution_csv)) asort($territories);
        }
        $this->debug = array();
        foreach($countries as $c) $this->debug['use.csv'][$c] = '';
        foreach($territories as $c) $this->debug['distribution.csv'][$c] = '';
        Functions::start_print_debug($this->debug, $this->resource_id);
        */
        // print_r($this->debug);
        // print_r($this->main);
        // print_r($this->numeric_fields);
        // exit("\n-end for now-\n");
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function main_write_archive()
    {
        $taxa = array_keys($this->main);
        // print_r($taxa);
        foreach($taxa as $species) {
            $taxon_id = self::create_taxon($species);

            if($val = @$this->main[$species]['child measurement']) {
                $child_measurements = self::get_child_measurements($val);
            }
            else $child_measurements = array();
            
               if(!@$this->main[$species]['MeasurementOfTaxon=true']) continue;
            foreach($this->main[$species]['MeasurementOfTaxon=true'] as $mType => $rec3) {
                // echo "\n ------ $mType\n";
                // print_r($rec3);
                foreach($rec3 as $mValue => $rec4) {
                    // echo "\n --------- $mValue\n";
                    // print_r($rec4);
                    $keys = array_keys($rec4);
                    // print_r($keys);
                    $tmp = $keys[0];
                    $samplesize = $rec4[$keys[0]];
                    $metadata = $rec4['r']['md'];
                    $dataset = $rec4['r']['ds'];
                    $mRemarks = $rec4['r']['mr'];
                    $mUnit = $rec4['r']['mu'];
                    $csv_type = $rec4['r']['ty']; //this is either 'c' or 'n'. Came from 'categorical.csv' or 'numerical.csv'.
                    // echo "\n - tmp = [$tmp]\n - metadata = [$metadata]\n - samplesize = [$samplesize]\n";
                    
                    /*Array( --- $mapped_record
                        [variable] => Common_length
                        [value] => 
                        [dataset] => .albouy.2015
                        [unit] => cm
                        [-->] => -->
                        [measurementType] => http://purl.obolibrary.org/obo/CMO_0000013
                        [measurementValue] => 
                        [record type] => MeasurementOfTaxon=true
                        [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000015
                        [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                        [http://eol.org/schema/terms/statisticalMethod] => http://eol.org/schema/terms/average
                        [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                    )*/
                    if($mapped_record = @$this->valid_set[$tmp]) {}
                    else exit("\nShould not go here...\n");
                    
                    $rek = array();
                    $rek["taxon_id"] = $taxon_id;
                    // $rek["catnum"] = substr($csv['type'],0,1)."_".$rec['blank_1'];
                    // $rek["catnum"] = ""; //bec. of redundant value, non-unique
                    $rek["catnum"] = $csv_type."_".$mValue;
                    
                    $mOfTaxon = "true";
                    $rek['measurementUnit'] = $mUnit;
                    $rek['measurementRemarks'] = $mRemarks;
                    $rek['statisticalMethod'] = $mapped_record['http://eol.org/schema/terms/statisticalMethod'];
                    
                    if(in_array($mType, array("http://www.wikidata.org/entity/Q1053008", "http://eol.org/schema/terms/TrophicGuild"))) {
                        $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //measurement_property, yes this is arbitrary field in MoF
                    }
                    else $rek['occur']['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //occurrence_property
                    $rek['occur']['occurrenceRemarks'] = $metadata;                                              //occurrence_property
                    if($samplesize > 1) { //you can now add arbitrary cols in occurrence
                        $rek['occur']['SampleSize'] = $samplesize;              //occurrence_property - http://eol.org/schema/terms/SampleSize
                    }
                    
                    if($val = @$this->main[$species]['occurrence']) {
                        $rek = self::additional_occurrence_property($val, $rek, $metadata, $dataset);
                    }
                    $rek['referenceID'] = self::generate_reference($dataset);
                    $ret_MoT_true = $this->func->add_string_types($rek, $mValue, $mType, $mOfTaxon);
                    $occurrenceID = $ret_MoT_true['occurrenceID'];
                    $measurementID = $ret_MoT_true['measurementID'];

                    /* now moved to occurrence
                    $rek = array();
                    $rek["taxon_id"] = $taxon_id;
                    $rek["catnum"] = ''; //can be blank coz occurrenceID is already generated.
                    $rek['occurrenceID'] = $occurrenceID; //this will be the occurrenceID for all mOfTaxon that is equal to 'false'. That is required.
                    if($samplesize > 1) {
                        $mType_var = 'http://eol.org/schema/terms/SampleSize';
                        $mValue_var = $samplesize;
                        $this->func->add_string_types($rek, $mValue_var, $mType_var, "false");
                    }
                    */
                    if($mapped_record['dataset'] == ".benesh.2017") {
                        $mType_var = 'http://eol.org/schema/terms/TrophicGuild';
                        $mValue_var = 'http://www.wikidata.org/entity/Q12806437';
                        $rek = array();
                        $rek["taxon_id"] = $taxon_id;
                        $rek["catnum"] = $csv_type."_".$mValue_var;
                        $rek['lifeStage'] = $mapped_record['http://rs.tdwg.org/dwc/terms/lifeStage'];  //measurement_property, yes this is arbitrary field in MoF
                        $rek['referenceID'] = self::generate_reference($mapped_record['dataset']);
                        $this->func->add_string_types($rek, $mValue_var, $mType_var, "true");
                    }
                    
                    if($val = $child_measurements) {
                        foreach($child_measurements as $m) {
                            /*Array(
                                        [mType] => http://eol.org/schema/terms/AnnualPrecipitation
                                        [mValue] => 1300
                                        [info] => Array(
                                                [md] => studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae
                                                [mr] => 
                                                [mu] => http://purl.obolibrary.org/obo/UO_0000016
                                                [ds] => .falster.2015
                                                [ty] => n
                                            )
                                    )
                            */
                            if($metadata == $m['info']['md'] && $dataset == $m['info']['ds']) {
                                $rek = array();
                                $rek["taxon_id"] = $taxon_id;
                                $rek["catnum"] = ''; //can be blank coz there'll be no occurrence for child measurements anyway.
                                $rek['occur']['occurrenceID'] = ''; //child measurements don't have occurrenceID
                                $rek['parentMeasurementID'] = $measurementID;
                                $mType_var = $m['mType'];
                                $mValue_var = $m['mValue'];
                                if($val = $m['info']['mu']) $rek['measurementUnit'] = $val;
                                if($val = $m['info']['mr']) $rek['measurementRemarks'] = $val;
                                $rek['referenceID'] = self::generate_reference($dataset);
                                $this->func->add_string_types($rek, $mValue_var, $mType_var, "child");
                            }
                        }
                    }

                }
            }
            
        }
    }
    private function additional_occurrence_property($arr, $retx, $metadata_x, $dataset_x)
    {   /* sample $arr value
        $a['Gadus morhua']['occurrence'] = Array(
                "http://rs.tdwg.org/dwc/terms/fieldNotes" => Array(
                        "field wild" => Array(
                                "growingcondition_fw_.falster.2015__" => 15,
                                "r" => Array(
                                        "md" => "studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae",
                                        "mr" => "FW",
                                        "mu" => "NA"
                                    )
                            )
                    ),
                "sex" => array("male" => array())
            );
        */
        $final = array();
        foreach($arr as $property => $rek1) {
            // echo "\nproperty = $property\n";
            // print_r($rek1);
            foreach($rek1 as $prop_value => $rek2) {
                if($rek2['r']['md'] == $metadata_x && 
                   $rek2['r']['ds'] == $dataset_x) $final[pathinfo($property, PATHINFO_FILENAME)] = $prop_value;
            }
        }
        if($final) {
            // print_r($final);
            foreach($final as $property => $value) {
                /* per Jen: You can put arbitrary columns in the occurrences file now, not just a set list of "valid" fields. https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63183&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63183
                if(!in_array($property, $this->occurrence_properties)) continue;
                */
                if(!isset($retx['occur'][$property])) $retx['occur'][$property] = $value;
                else {
                    if($retx['occur'][$property]) $retx['occur'][$property] .= ". Addtl: $value";
                    else                          $retx['occur'][$property] = $value;
                }
            }
            // print_r($retx);
        }
        return $retx;
    }
    private function mRemarks_map($str, $dataset, $mType)
    {   /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63188
        Interesting! You found a second use of some two letter codes that I'd only seen as primary measurementValue. Another fiddly one, then:
        where dataset =.falster.2015
        in measurementRemarks, please map these four values as follows:
        DA -> http://purl.obolibrary.org/obo/PATO_0001731
        DG -> http://purl.obolibrary.org/obo/PATO_0001731
        EA -> http://purl.obolibrary.org/obo/PATO_0001733
        EG -> http://purl.obolibrary.org/obo/PATO_0001733
        */
        if($dataset == ".falster.2015") {
            if    (in_array($str, array('DA', 'DG'))) $final = 'http://purl.obolibrary.org/obo/PATO_0001731';
            elseif(in_array($str, array('EA', 'EG'))) $final = 'http://purl.obolibrary.org/obo/PATO_0001733';
            else $final = $str;
        }
        else $final = $str;
        /* debug only
        if($final == "DA") {
            echo "\nstr: [$str]\n";
            echo "\ndataset: [$dataset]\n";
            echo "\nmType: [$mType]\n";
        }
        */
        /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1754?focusedCommentId=63189&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63189
        Some measurementRemarks to remove. Keeping original values in this field if available is nearly always helpful, but not in these cases:
        where measurementType is one of these
        http://purl.obolibrary.org/obo/GO_0000003
        http://purl.obolibrary.org/obo/GO_0007530
        http://purl.obolibrary.org/obo/IDOMAL_0002084
        and (measurementRemarks= yes OR measurementRemarks= no)
        please take the string out, leaving measurementRemarks blank. No need to map to anything new
        */
        if(in_array($mType, array("http://purl.obolibrary.org/obo/GO_0000003", "http://purl.obolibrary.org/obo/GO_0007530", "http://purl.obolibrary.org/obo/IDOMAL_0002084"))) {
            if(in_array($final, array("yes", "no"))) $final = "";
        }
        return $final;
    }
    private function assign_child_measurement($rec, $mapped_record)
    {
        /*Array( --- $rec
            [blank_1] => 1999010
            [species] => acer_pensylvanicum
            [metadata] => studyName:Whittaker1974;location:Hubbard Brook Experimental Forest;latitude:44;longitude:-72;species:Acer pensylvanicum;family:Aceraceae
            [variable] => map
            [value] => 1300
            [units] => mm
            [dataset] => .falster.2015
        )
        Array( --- $mapped_record
            [variable] => map
            [value] => 
            [dataset] => .falster.2015
            [unit] => mm
            [-->] => -->
            [measurementType] => http://eol.org/schema/terms/AnnualPrecipitation
            [measurementValue] => 
            [record type] => child measurement
            [http://rs.tdwg.org/dwc/terms/measurementUnit] => http://purl.obolibrary.org/obo/UO_0000016
            [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
        )
        */
        $mType  = $mapped_record['measurementType'];
        $mValue = ($mapped_record['measurementValue'] != "")                             ? $mapped_record['measurementValue']                             : $rec['value'];
        $mUnit  = ($mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] != "") ? $mapped_record['http://rs.tdwg.org/dwc/terms/measurementUnit'] : $rec['units'];
        if($mUnit == "NA") $mUnit = '';
        $this->childm[$rec['species']][$mType][$mValue][$mUnit] = array('metadata' => $rec['metadata'], 'dataset' => $rec['dataset']);
    }
    private function assign_ancestry($rec, $mapped_record)
    {
        /*Array( --- $rec
            [blank_1] => 143100
            [species] => Tsuga heterophylla
            [metadata] => Family:Pinaceae;Genus:Tsuga;Phylum:G
            [variable] => Phylum
            [value] => G
            [units] => NA
            [dataset] => .ameztegui.2016
        )
        Array( --- $mapped_record
            [variable] => Phylum
            [value] => G
            [dataset] => .ameztegui.2016
            [unit] => 
            [-->] => -->
            [measurementType] => http://rs.tdwg.org/dwc/terms/phylum
            [measurementValue] => Gymnosperms
            [record type] => taxa
            [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
            [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
        )
        */
        if    ($val = $mapped_record['measurementValue']) $value = $val;
        elseif($val = $rec['value'])                      $value = $val;
        $this->main[$rec['species']]['ancestry'][$rec['variable']] = $value;
    }
    private function blank_if_NA($str)
    {
        if($str == "NA") return "";
        else return $str;
    }
    private function get_corresponding_rek_from_mapping_spreadsheet($i, $fields, $map)
    {
        $final = array();
        foreach($fields as $field) $final[$field] = $map[$field][$i];
        return $final;
    }
    private function get_child_measurements($arr)
    {
        $final = array();
        foreach($arr as $mType => $rek1) {
            $rec = array();
            foreach($rek1 as $mValue => $rek2) {
                $rec['mType'] = $mType;
                $rec['mValue'] = $mValue;
                $rec['info'] = $rek2['r'];
            }
            if($rec) $final[] = $rec;
        }
        return $final;
    }
    /* working but no longer needed, since you can now put arbitrary fields in occurrence extension.
    function get_occurrence_properties()
    {
        if($xml = Functions::lookup_with_cache("https://editors.eol.org/other_files/ontology/occurrence_extension.xml", $this->download_options)) {
            if(preg_match_all("/<property name=\"(.*?)\"/ims", $xml, $arr)) {
                print_r($arr[1]);
                return $arr[1];
            }
        }
    }
    */
    private function generate_reference($dataset)
    {
        if($ref = @$this->refs[$dataset]) {
            /* [.aubret.2015] => Array(
                    *[URL to paper] => http://www.nature.com/hdy/journal/v115/n4/full/hdy201465a.html
                    *[DOI] => 10.1038/hdy.2014.65
                    [Journal] => Heredity
                    *[Publisher] => Springer Nature
                    *[Title] => Island colonisation and the evolutionary rates of body size in insular neonate snakes
                    *[Author] => Aubret
                    [Year] => 2015
                    *[author_year] => .aubret.2015
                    [BibTeX citation] => @article{aubret2015,title={Island colonisation and the evolutionary rates of body size in insular neonate snakes},author={Aubret, F},journal={Heredity},volume={115},number={4},pages={349--356},year={2015},publisher={Nature Publishing Group}}
                    [Taxonomy ] => Animalia/Serpentes
                    [Person] => Katie
                    [WhoWroteFunction] => 
                    [Everything Completed?] => 
                    [] => 
                    *[full_ref] => Aubret. (2015). Island colonisation and the evolutionary rates of body size in insular neonate snakes. Heredity. Springer Nature.
                )
            */
            if($ref_id = @$ref['author_year']) {
                $r = new \eol_schema\Reference();
                $r->identifier = $ref_id;
                $r->full_reference = $ref['full_ref'];
                $r->uri = $ref['URL to paper'];
                $r->doi = $ref['DOI'];
                $r->publisher = $ref['Publisher'];
                $r->title = $ref['Title'];
                $r->authorList = $ref['Author'];
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
                return $ref_id;
            }
        }
        else $this->debug['no citations yet'][$dataset] = '';
    }
    private function initialize_citations_file()
    {
        $tmp_file = $this->source_csv_path."/citations.tsv";
        $i = 0;
        if(!file_exists($tmp_file)) {
            exit("\nFile does not exist: [$tmp_file]\n");
        }
        foreach(new FileIterator($tmp_file) as $line => $row) {
            $row = Functions::conv_to_utf8($row);
            $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [URL to paper] => http://onlinelibrary.wiley.com/doi/10.1111/nph.13935/abstract
                    [DOI] => 10.1111/nph.13935
                    [Journal] => New Phytologist
                    [Publisher] => Wiley
                    [Title] => Plasticity in plant functional traits is shaped by variability in neighbourhood species composition
                    [Author] => Abakumova
                    [Year] => 2016
                    [author_year] => .abakumova.2016
                    [BibTeX citation] => @article {NPH:NPH13935,author = {Abakumova, Maria and Zobel, Kristjan and Lepik, Anu and Semchenko, Marina},title = {Plasticity in plant functional traits is shaped by variability in neighbourhood species composition},journal = {New Phytologist},volume = {211},number = {2},issn = {1469-8137},url = {http://dx.doi.org/10.1111/nph.13935},doi = {10.1111/nph.13935},pages = {455--463},keywords = {biotic environment, competition, functional traits, local adaptation, neighbour recognition, phenotypic plasticity, selection, spatial patterns},year = {2016},note = {2015-20353},}
                    [Taxonomy ] => Plantae
                    [Person] => Anne
                    [WhoWroteFunction] => 
                    [Everything Completed?] => 
                    [] => 
                )
                Last, F. M. (Year, Month Date Published). Article title. Retrieved from URL
                Last, F. M. (Year Published) Book. City, State: Publisher.
                */
                $full_ref = "$rec[Author]. ($rec[Year]). $rec[Title]. $rec[Journal]. $rec[Publisher].";
                $full_ref = trim(Functions::remove_whitespace($full_ref));
                $rec['full_ref'] = $full_ref;
                $this->refs[$rec['author_year']] = $rec;
            }
        }
        // print_r($this->refs); exit;
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
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    /* ######################################################################################################################################### */
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
}
?>
