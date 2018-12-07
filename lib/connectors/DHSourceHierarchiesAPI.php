<?php
namespace php_active_record;
/* connector: [dwh.php] */
class DHSourceHierarchiesAPI
{
    function __construct()
    {
        /*
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        if(Functions::is_production()) {}
        */
        /* not being used here
        $this->AphiaRecordByAphiaID_download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26, 'expire_seconds' => false);
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";
        */
        $this->gnparser = "http://parser.globalnames.org/api?q=";
        if(Functions::is_production()) {
            $this->smasher_download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/extra/eli_dwh/"; //download_wait_time is 1/4 of a second -> 1000000/4
        }
        else {
            $this->smasher_download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/', //new, started from blank
                // 'cache_path'         => '/Volumes/Thunderbolt4/z backup of AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false); //false
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh/"; //old - initial runs
            $this->main_path = "/Volumes/AKiTiO4/d_w_h/eli_dwh2/"; //new - TRAM-800
        }
        /* Functions::lookup_with_cache($this->gnparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')


/*paste these in terminal
php update_resources/connectors/dwh.php _ EET
php update_resources/connectors/dwh.php _ ASW
php update_resources/connectors/dwh.php _ ictv
php update_resources/connectors/dwh.php _ CLP
php update_resources/connectors/dwh.php _ trunk
php update_resources/connectors/dwh.php _ ERE
php update_resources/connectors/dwh.php _ IOC
php update_resources/connectors/dwh.php _ BOM
php update_resources/connectors/dwh.php _ NCBI
php update_resources/connectors/dwh.php _ ONY
php update_resources/connectors/dwh.php _ ODO
php update_resources/connectors/dwh.php _ WOR

php update_resources/connectors/dwh.php _ COL
*/
        //for testing
        $this->sh['xxx']['source']          = $this->main_path."/xxx/";
        $this->sh['xxx']['has_syn']         = false;
        $this->sh['xxx']['run_gnparse']     = true;

        // /* new list
        $this->sh['EET']['source']          = $this->main_path."/eolearthwormpatch/";
        $this->sh['EET']['has_syn']         = false;
        $this->sh['EET']['run_gnparse']     = true;

        $this->sh['ASW']['source']          = $this->main_path."/amphibianspeciesoftheworld/";
        $this->sh['ASW']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ASW']['run_gnparse']     = true;

        $this->sh['ictv']['source']         = $this->main_path."/ICTV-virus_taxonomy-with-higherClassification/";
        $this->sh['ictv']['has_syn']        = false;
        $this->sh['ictv']['run_gnparse']    = false;

        $this->sh['CLP']['source']          = $this->main_path."/Catalogue_of_Life_Protists_DH/";
        $this->sh['CLP']['has_syn']         = false;
        $this->sh['CLP']['run_gnparse']     = true;

        $this->sh['trunk']['source']        = $this->main_path."/dynamichierarchytrunk2018-11-21/";
        $this->sh['trunk']['has_syn']       = false;
        $this->sh['trunk']['run_gnparse']   = false;

        $this->sh['ERE']['source']          = $this->main_path."/eoldynamichierarchyerebidaepatch/";
        $this->sh['ERE']['has_syn']         = false;
        $this->sh['ERE']['run_gnparse']     = false;

        $this->sh['IOC']['source']          = $this->main_path."/ioc-birdlist/";
        $this->sh['IOC']['has_syn']         = false;
        $this->sh['IOC']['run_gnparse']     = true;

        $this->sh['COL']['source']          = $this->main_path."/Catalogue_of_Life_DH/";
        $this->sh['COL']['has_syn']         = true;
        $this->sh['COL']['run_gnparse']     = true;

        $this->sh['BOM']['source']          = $this->main_path."/kitchingetal2018/";
        $this->sh['BOM']['has_syn']         = true;
        $this->sh['BOM']['run_gnparse']     = true;

        $this->sh['NCBI']['source']         = $this->main_path."/NCBI_Taxonomy_Harvest_DH/";
        $this->sh['NCBI']['has_syn']        = true;
        $this->sh['NCBI']['run_gnparse']    = false; //has specific field for just canonical name

        $this->sh['ONY']['source']          = $this->main_path."/oliveira2012onychophora/";
        $this->sh['ONY']['has_syn']         = false;
        $this->sh['ONY']['run_gnparse']     = true;
        
        $this->sh['ODO']['source']          = $this->main_path."/worldodonata/";
        $this->sh['ODO']['has_syn']         = false; //has syn but we don't want them
        $this->sh['ODO']['run_gnparse']     = true;

        $this->sh['WOR']['source']          = $this->main_path."/WoRMS_DH/";
        $this->sh['WOR']['has_syn']         = true;
        $this->sh['WOR']['run_gnparse']     = true;
        // */
        $this->taxonomy_header_tmp = array("name", "uid", "parent_uid", "rank");
        $this->synonym_header_tmp = array("name", "uid", "type");
        
        /* old list
        $this->sh['WOR']['source']        = $this->main_path."/worms_v5/";
        $this->sh['IOC']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['COL']['source']          = $this->main_path."/col_v1/";
        $this->sh['ictv']['source']         = $this->main_path."/ictv_v3/";
        $this->sh['ictv']['run_gnparse']    = false; //
        $this->sh['ODO']['source']      = $this->main_path."/odonata_v2/";
        $this->sh['ONY']['source']  = $this->main_path."/onychophora_v3/";
        $this->sh['EET']['source']   = $this->main_path."/earthworms_v3/";
        $this->sh['pbdb']['source']         = $this->main_path."/pbdb_v1/";
        $this->sh['pbdb']['run_gnparse']    = false; //has separate field for 'scientificNameAuthorship'
        */
        /* old
        //row_terminator was instroduced for ncbi
        //this was just Eli's initiative. May wait for Katja's instructions here...
        $this->sh['ncbi']['source']         = $this->main_path."/ncbi_v1/";
        $this->sh['ncbi']['run_gnparse']    = false; //has specific field for just canonical name
        $this->sh['ncbi']['iterator_options'] = array('row_terminator' => "\t|\n");
        */
    }
    public function compare_results()
    {
        // print_r($this->sh['WOR']['source'])
        $sets = array_keys($this->sh);
        print_r($sets);
        foreach($sets as $what) {
            $txtfile = $this->sh[$what]['source']."taxonomy.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what: [".number_format($total_rows)."]\n";

            $txtfile = $this->sh[$what]['source']."taxonomy orig.tsv";
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal $what old: [".number_format($total_rows)."]\n";
        }
    }
    public function start($what)
    {
        /*
        $this->what = $what;
        $string = "Malmopsylla† karatavica Bekker-Migdisova, 1985";
        //$string = '“montereina” greeleyi (MacFarland, 1909)';
        // $string = "V latipennis Baehr, 2006";
        $string = "Curcuma vitellina Škornick. & H.Ð.Tran";
        echo "\norig: $string";
        $string = str_replace("†","",$string);
        $string = Functions::conv_to_utf8($string);
        echo "\nutf8: $string";
        echo "\ngnparser canonical: ".self::gnsparse_canonical($string, 'cache');
        $c = Functions::canonical_form($string);
        exit("\ncanonical: $c\n");
        */
        /*
        $json = Functions::lookup_with_cache($this->gnparser.urlencode('Notoscolex wellingtonensis (Spencer, 1895)'), $this->smasher_download_options);
        exit("\n".$json."\n");
        */
        /*
        $sciname = "Amorimia exotropica (Griseb.) W.R.Anderson";
        // $canonical = self::gnsparse_canonical($sciname, 'api');
        // echo "\n[$canonical]\n";
        $canonical = self::gnsparse_canonical($sciname, 'cache');
        echo "\nparsing...[$sciname] ---> [$canonical]\n";
        
        // $options = $this->smasher_download_options; $options['expire_seconds'] = 0; //expires now
        // $canonical = self::gnsparse_canonical($sciname, 'cache', $options);
        // echo "\nparsing...[$sciname] ---> [$canonical]\n";
        
        exit("\nstopx\n");
        */
        /*
        $sciname = "Gadus morhua Eli 1972";
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $this->smasher_download_options);
        print_r(json_decode($json, true));
        $json = self::get_json_from_cache($sciname);
        print_r(json_decode($json, true));
        exit;
        */
        /*
        $cmd = 'gnparser name "Notoscolex imparicystis (Jamieson, 1973)"';
        $json = shell_exec($cmd);
        print_r(json_decode($json, true));
        exit;
        
        gnparser file --input xaa.txt --output xaa_gnparsed.txt
        gnparser file --input xab.txt --output xab_gnparsed.txt
        gnparser file --input xac.txt --output xac_gnparsed.txt
        gnparser file --input xad.txt --output xad_gnparsed.txt
        gnparser file --input xae.txt --output xae_gnparsed.txt
        gnparser file --input xaf.txt --output xaf_gnparsed.txt
        gnparser file --input xag.txt --output xag_gnparsed.txt
        gnparser file --input xah.txt --output xah_gnparsed.txt
        ftp://ftp.ncbi.nlm.nih.gov/pub/taxonomy/
        */
        /*
        gnparser file -f json-compact --input test.txt --output test_gnparsed.txt
        self::save_2local_gnparsed_file_new($what, "test_gnparsed.txt"); exit("\n-end test-\n");
        
        gnparser file -f simple --input test.txt --output test_gnparsed.txt
        

gnparser file -f json-compact --input xaa.txt --output xaa_gnparsed.txt
gnparser file -f json-compact --input xab.txt --output xab_gnparsed.txt
gnparser file -f json-compact --input xac.txt --output xac_gnparsed.txt
gnparser file -f json-compact --input xad.txt --output xad_gnparsed.txt
gnparser file -f json-compact --input xae.txt --output xae_gnparsed.txt
gnparser file -f json-compact --input xaf.txt --output xaf_gnparsed.txt
gnparser file -f json-compact --input xag.txt --output xag_gnparsed.txt
gnparser file -f json-compact --input xah.txt --output xah_gnparsed.txt
        
        self::save_2local_gnparsed_file_new($what, "xaa_gnparsed.txt"); exit("\n-end xaa_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xab_gnparsed.txt"); exit("\n-end xab_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xac_gnparsed.txt"); exit("\n-end xac_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xad_gnparsed.txt"); exit("\n-end xad_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xae_gnparsed.txt"); exit("\n-end xae_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xaf_gnparsed.txt"); exit("\n-end xaf_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xag_gnparsed.txt"); exit("\n-end xag_gnparsed-\n");
        self::save_2local_gnparsed_file_new($what, "xah_gnparsed.txt"); exit("\n-end xah_gnparsed-\n");
        
        */

        /* CoL divided into smaller chunks
        self::save_2local_gnparsed_file($what, "xaa_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xab_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xac_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xad_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xae_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xaf_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xag_gnparsed.txt");
        self::save_2local_gnparsed_file($what, "xah_gnparsed.txt");
        exit;
        */
        // self::parent_id_check($what); exit;
        /*===================================starts here=====================================================================*/
        $this->what = $what;
        
        // /* get problematic names from Google sheet
        $this->problematic_names = self::get_problematic_names();   //UN-COMMENT IN REAL OPERATION
        // */
        
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        print_r($meta); //exit;

        // /* utility write all names. This was used primarily for COL, since it has 3,620,095 rows and had to do some organization to make sure all names got cached.
        self::utility_write_all_names($meta); exit("\n-end write all names-\n"); //works OK                     step 1 --- then step 3
        
        // $meta['ctr'] = 8;
        // self::build_final_taxonomy_tsv($meta, "taxonomy");
        // self::build_final_taxonomy_tsv($meta, "synonym"); exit("\n-end COL-\n");
        
        
        // Then manually run this: didn't actually use these COL_ALL_NAMES_?_gnparsed.txt                          step 2
        // gnparser file -f simple --input COL_ALL_NAMES_1.txt --output COL_ALL_NAMES_1_gnparsed.txt
        // gnparser file -f simple --input COL_ALL_NAMES_2.txt --output COL_ALL_NAMES_2_gnparsed.txt
        // Then start caching...                                                                                   step 3
        // self::run_TSV_file_with_gnparser_new("COL_ALL_NAMES_2_gnparsed.txt", $what); exit("\nCaching TSV for [$what] done!\n");
        // */

        /* this is one-time run for every dataset - all 13 datasets =============================================================
        self::run_file_with_gnparser_new($meta);    exit("\nCaching for [$what] done!\n"); //is used for blank slate, meaning new cache path or new gnparser version.
        self::run_file_with_gnparser_new_v2($meta); exit("\nCaching for [$what] done!\n"); //is used to get names left behind from above. Only processes names, where cache doesn't exist yet
        ========================================================================================================================= */
        
        $with_authorship = false;
        if(@$this->sh[$what]['run_gnparse'] === false) {}
        else { //normal
            if(self::need_2run_gnparser_YN($meta)) {
                $with_authorship = true;
                /* wise move before. That is when using the old gnparser version. The new doesn't have a \n line separator between json records.
                self::run_file_with_gnparser($meta);
                self::save_2local_gnparsed_file($what);
                */
            }
        }
        
        /* 5. Duplicate taxa --- utility generating duplicates report for Katja ==========================================================================================
        // WOR has a bunch of species and subspecific taxa that have the same canonical form but different authors. These are mostly foraminiferans and a few diatoms. 
        // I'm not sure what to do about these. Clearly, they can't all be accepted names, but WOR still has them as such. I don't quite remember how we handled these 
        // in previous smasher runs. If smasher can't handle these apparent duplicate taxa, we could consider cleaning them up by keeping the one with the oldest date and 
        // removing the ones with the more recent data, along with their children.
        // self::check_for_duplicate_canonicals($meta, $with_authorship); exit("\n-end checking for duplicates [$what]-\n");
        self::check_for_duplicate_canonicals_new($meta, "taxonomy"); exit("\n-end checking for duplicates (new) [$what]-\n");
        ================================================================================================================================================================= */
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what".".txt"; if(file_exists($path)) unlink($path);
        
        self::process_taxon_file($meta, $with_authorship);
        self::parent_id_check($what);
        self::show_totals($what);
        if($this->sh[$what]['run_gnparse'] != $with_authorship) echo "\nInvestigate the need to run gnparser [$what]\n";
        else                                                    echo "\n-OK-\n";
    }
    private function get_problematic_names() //sheet found here: https://eol-jira.bibalex.org/browse/TRAM-800
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1A08xM14uDjsrs-R5BXqZZrbI_LiDNKeO6IfmpHHc6wg';
        $params['range']         = 'gnparser failures!C2:D1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[1];
        return $final;
    }
    private function show_totals($what)
    {
        $filenames = array('taxonomy.tsv', 'synonym.tsv', 'taxon.tab', 'taxa.txt');
        foreach($filenames as $filename) {
            $file = $this->sh[$what]['source'].$filename;
            if(file_exists($file)) {
                $total = shell_exec("wc -l < ".escapeshellarg($file));
                $total = trim($total);  echo "\n$filename: [$total]\n";
            }
        }
    }
    private function get_ctr_value($what)
    {
        $directory = $this->sh[$what]['source'];
        $filecount = 0;
        $files = glob($directory . "taxonomy_*_gnparsed.txt"); //taxonomy_1_gnparsed.txt
        if($files) $filecount = count($files);
        return $filecount;
    }
    private function check_for_duplicate_canonicals_new($meta, $pre)
    {
        $what = $meta['what'];
        $ctr = self::get_ctr_value($what);
        echo "\nctr = $ctr \n";
        // exit;
        for ($c = 1; $c <= $ctr; $c++) {
            $txtfile = $this->sh[$what]['source'].$pre."_".$c."_gnparsed.txt"; echo "\nprocessing [$txtfile]\n";
            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile); echo "\nTotal rows: [".number_format($total_rows)."]\n"; $modulo = self::get_modulo($total_rows);
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    //fix $fields: important
                    $count = count($this->{$pre."_header_tmp"});
                    $fields[$count+1] = 'canonicalName';
                    $fields[$count+2] = 'valueRanked';
                    $fields[$count+3] = 'other1';
                    $fields[$count+4] = 'other2';
                    $fields[$count+5] = 'other3';
                    // print_r($fields);
                    continue;
                }
                else {
                    if(!@$row[0]) continue;
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                /*Array(
                    [f33063e7-083e-5910-83b4-9a96c170f159] => 9d241baa-f15b-5231-815f-69c2b59ad659
                    [name] => Limacoccus brasiliensis (Hempel, 1934)
                    [uid] => 316423
                    [parent_uid] => 43080004
                    [rank] => species
                    [canonicalName] => Limacoccus brasiliensis
                    [valueRanked] => Limacoccus brasiliensis
                    [other1] => (Hempel 1934)
                    [other2] => 1934
                    [other3] => 3
                )
                Array(
                    [a274cdda-3ca9-559b-9476-6e45eea18eed] => 59f5f484-b052-52f1-8fc0-0b288ca6f2ee
                    [name] => Canceraspis brasiliensis Hempel, 1934
                    [uid] => 316423
                    [type] => synonym
                    [canonicalName] => Canceraspis brasiliensis
                    [valueRanked] => Canceraspis brasiliensis
                    [other1] => Hempel 1934
                    [other2] => 1934
                    [other3] => 3
                )*/
                @$test[$rec['canonicalName']][] = $rec['name'];
            }
        }
        self::print_duplicates($what, $test, "_duplicates_new.txt");
    }
    
    private function check_for_duplicate_canonicals($meta, $with_authorship)
    {
        $what = $meta['what']; $i = 0; $test = array();
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if(($i % 10000) == 0) echo "\n".number_format($i)."\n";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            /* rray(
                [taxonID] => 6
                [furtherInformationURL] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=6
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Bacteria
                [taxonRank] => kingdom
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
            )*/
            // print_r($rec); exit; //use to test if field - value is OK ==================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($with_authorship) {
                if($canon = self::gnsparse_canonical($rec['scientificName'], "cache")) {
                    @$test[$canon][] = $rec['scientificName'];
                }
            }
        }
        self::print_duplicates($what, $test, "_duplicates.txt");
    }
    private function print_duplicates($what, $test, $postfix)
    {
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        $FILE = Functions::file_open($path, 'w');
        foreach($test as $canon => $origs) {
            if(count($origs) > 1) {
                foreach($origs as $orig) {
                    if($canon != $orig) fwrite($FILE, $canon."\t".$orig."\n");
                }
                fwrite($FILE, "\n");
            }
        }
        fclose($FILE);
        //just to clean-up, delete zero size files
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix;
        if(file_exists($path)) {
            if(!filesize($path)) {
                echo "\nNo duplicates for [$what]\n"; unlink($path);
            }
        }
    }
    private function is_record_valid($what, $rec)
    {
        if($what == "NCBI") {
            if(in_array($rec['taxonomicStatus'], array("in-part", "authority", "misspelling", "equivalent name", "genbank synonym", "misnomer", "teleomorph"))) return false;
        }
        elseif($what == "COL") {
            if(in_array($rec['taxonomicStatus'], array("ambiguous synonym", "misapplied name"))) return false;
        }
        return true;
    }
    private function need_2run_gnparser_YN($meta)
    {
        $what = $meta['what']; $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); exit; //use to test if field - value is OK ==================================================================
            if($val = self::gnsparse_canonical($rec['scientificName'], "cache")) {
                if($val != $rec['scientificName']) return true;
            }
            if($i >= 15) break;
        }
        return false;
    }
    private function process_taxon_file($meta, $with_authorship)
    {
        if($with_authorship) echo "\nWith authorship\n";
        else                 echo "\nWithout authorship\n";
        $what = $meta['what']; $has_synonym = false;
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy.tsv", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym.tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->taxonomy_header)."\t|\t"."\n");
        fwrite($fn_syn, implode("\t|\t", $this->synonym_header)."\t|\t"."\n");
        $i = 0;
        $m = 3620095/3; //for CoL
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file'], false, true, @$this->sh[$what]['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 10000) == 0) echo "\n".number_format($i)."\n";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            /* old
            if($what == 'ncbi') $tmp = explode("\t|\t", $row);
            else                $tmp = explode("\t", $row);
            */
                                $tmp = explode("\t", $row);
            
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ncheck first [$with_authorship]\n"); //use to test if field - value is OK

            /*
            if(in_array($what, array('COL'))) {
                breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m)   $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                if(!$cont) continue;
            }*/
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            // if(in_array($what, array('WOR', 'NCBI', 'BOM', 'COL', 'trunk', 'ODO', 'ONY', 'ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) { //excluded 'pbdb', from initial endeavor
                /*  [index] => 1
                    [taxonomicStatus] => accepted
                    [taxonRank] => superfamily
                    [datasetID] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                    [parentNameUsageID] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                    [scientificNameAuthorship] => 
                    [higherClassification] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                    [acceptedNameUsageID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [scientificName] => Arrenuroidea
                    [taxonID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634

                    if accepted_id != taxon_id:
                        print('synonym found')
                        out_file_s.write(accepted_id + '\t|\t' + name + '\t|\t' + 'synonym' + '\t|\t' + '\t|\t' + '\n')
                    else:
                        out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                */
                
                // if($rec['scientificName'] == "Cataladrilus (Cataladrilus) Qiu and Bouche, 1998") {
                //     print_r($rec); exit("\ndebugging...\n");
                // }
                
                if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
                
                $t = array();
                $t['parent_id']     = $rec['parentNameUsageID'];    //row[4]
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache'); //row[8]
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']      = $rec['taxonID'];              //row[9]
                $t['accepted_id']   = @$rec['acceptedNameUsageID'];  //row[7]
                $t['rank']          = ($val = @$rec['taxonRank']) ? self::clean_rank($val): "no rank"; //row[2]
                $t['source']        = '';

                if($this->sh[$what]['has_syn']) {
                    if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                        self::write2file("syn", $fn_syn, $t);
                        $has_synonym = true;
                    }
                    elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file("tax", $fn_tax, $t);
            // }
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /*
            if(in_array($what, array('ERE', 'CLP', 'ASW', 'IOC', 'ictv', 'EET'))) { //headers changed from version: ioc-birdlist_v2 to ioc-birdlist_v3
                    // [taxonID] => 09af091e166bfa45493c6242ebf16a7c
                    // [scientificName] => Celeus elegans leotaudi Hellmayr, 1906
                    // [taxonRank] => subspecies
                    // [parentNameUsageID] => d6edba5dd4d993cbab690c2df8fc937f
                    // [taxonRemarks] => 
                    // [canonicalName] => Celeus elegans leotaudi
                    // [source] => http://www.worldbirdnames.org/bow/woodpeckers/
                    // [scientificNameAuthorship] => Hellmayr, 1906
                    // out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                $t = array();
                $t['parent_id'] = $rec['parentNameUsageID'];
                if($with_authorship) $t['name'] = self::gnsparse_canonical($rec['scientificName'], 'cache');
                else                 $t['name'] = $rec['scientificName'];
                $t['taxon_id']  = $rec['taxonID'];
                $t['rank']      = ($val = @$rec['taxonRank']) ? $val: "no rank";
                $t['source']    = '';
                self::write2file("tax", $fn_tax, $t);
            }*/
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            /*
            if(in_array($what, array('ictv'))) {
                parent_id = row[2]
                name = row[3]
                taxon_id = row[0]
                rank = row[5].lower()
                source = ''
                out_file.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                    [0] => ICTV:Sobemovirus
                    [1] => 
                    [2] => ICTV:unplaced Viruses
                    [3] => Sobemovirus
                    [4] => Viruses|unplaced
                    [5] => genus
                    [taxonID] => ICTV:Sobemovirus
                    [source] => 
                    [parentNameUsageID] => ICTV:unplaced Viruses
                    [scientificName] => Sobemovirus
                    [higherClassification] => Viruses|unplaced
                    [taxonRank] => genus
            }
            */
            //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        }
        fclose($fn_tax);
        fclose($fn_syn);
        if(!$has_synonym) unlink($this->sh[$what]['source']."synonym.tsv");
    }
    private function clean_rank($rank)
    {
        $rank = strtolower($rank);
        if($rank == "subsp.")       $rank = "subspecies";
        elseif($rank == "var.")     $rank = "variety";
        elseif($rank == "f.")       $rank = "form";
        elseif($rank == "varietas") $rank = "variety";
        elseif($rank == "forma")    $rank = "form";
        return $rank;
    }
    private function parent_id_check($what)
    {
        echo "\nStarts parent_id check...\n";
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            $uids[$rec[0]] = '';
        }
        echo "\nuids: ".count($uids)."\n";
        $i = 0; $undefined_parents = array();
        foreach(new FileIterator($this->sh[$what]['source'].'taxonomy.tsv') as $line => $row) {
            $i++; if($i == 1) continue;
            $rec = explode("\t|\t", $row);
            if($parent_uid = @$rec[1]) {
                // echo " [$parent_uid]";
                if(!isset($uids[$parent_uid])) $undefined_parents[$parent_uid] = '';
            }
        }
        echo "\nUndefined parents: ".count($undefined_parents)."\n";
        if($undefined_parents) {
            echo "\nUndefined parents for [$what]:\n";
            print_r($undefined_parents);
        }
    }
    private function run_file_with_gnparser_new($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $xname = "name_only1";
        $m = 3620095/10; //for CoL
        $what = $meta['what']; $i = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            
            /* A good way to pinpoint the row count - works OK
            if($rec['scientificName'] == "Euchilofulvius carinatus (Poppius, 1913)") exit("\n---[$i]---\n");
            else continue;
            */
            
            /* breakdown when caching:
            $cont = false;
            // if($i >=  1    && $i < $m)   $cont = true;
            // if($i >=  $m   && $i < $m*2) $cont = true;
            // if($i >=  $m*2 && $i < $m*3) $cont = true;
            // if($i >=  $m*3 && $i < $m*4) $cont = true;
            // if($i >=  $m*4 && $i < $m*5) $cont = true;
            // if($i >=  $m*5 && $i < $m*6) $cont = true;
            // if($i >=  $m*6 && $i < $m*7) $cont = true;
            // if($i >=  $m*7 && $i < $m*8) $cont = true;
            // if($i >=  $m*8 && $i < $m*9) $cont = true;
            // if($i >=  $m*9 && $i < $m*10) $cont = true;
            // if($i >= 1,851,000 && $i < 1900000) $cont = true; done
            // if($i >= 1,908,000 && $i < 2000000) $cont = true; done
            if(!$cont) continue;
            */
            
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
            if(($i % 1000) == 0) {
                echo "\nmain count:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
            }
        }
        //last batch
        fclose($WRITE);
        $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
    }
    private function run_file_with_gnparser_new_v2($meta) //
    {
        $xname = "name_onlyx2";
        $what = $meta['what']; $i = 0; $saved = 0;
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if(($i % 5000) == 0) echo "\n --->:[".number_format($i)."]"; //stopped at 1,645,000 for COL
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            // /* breakdown when caching:
            $cont = false;
            if($i >=  931834 && $i < 4000000) $cont = true;
            if(!$cont) continue;
            // */
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) {
                if(!self::cache_exists($val)) {
                    fwrite($WRITE, $val."\n");
                    $saved++;
                }
            }
            if($saved == 1000) {
                echo "\nmain countx:[".number_format($i)."]\n";
                fclose($WRITE);
                $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
                $WRITE = fopen($this->sh[$what]['source'].$xname.".txt", "w"); //will overwrite existing
                $saved = 0;
            }
        }
        //last batch
        fclose($WRITE);
        if($saved) {
            $cmd = "gnparser file -f json-compact --input ".$this->sh[$what]['source'].$xname.".txt --output ".$this->sh[$what]['source'].$xname."_gnparsed.txt";
            $out = shell_exec($cmd); echo "\n$out\n";
            self::save_2local_gnparsed_file_new($what, $xname."_gnparsed.txt");
        }
    }
    /*
    private function run_file_with_gnparser($meta) //creates name_only.txt and converts it to name_only_gnparsed.txt using gnparser. gnparser converts entire file
    {
        $what = $meta['what']; $i = 0;
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        echo "\nRunning gnparser...\n";
        $WRITE = fopen($this->sh[$what]['source']."name_only.txt", "w"); //will overwrite existing
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            // print_r($rec); //exit; //use to test if field - value is OK
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
        }
        fclose($WRITE);
        // Works OK during older version of gnparser. The later version doesn't have a line separator (\n) between json record.
        // //convert entire file (names) to gnparser version
        // $cmd = "gnparser file --input ".$this->sh[$what]['source']."name_only.txt --output ".$this->sh[$what]['source']."name_only_gnparsed.txt";
        // $out = shell_exec($cmd);
        // echo "\n$out\n";
    }
    */
    private function save_2local_gnparsed_file_new($what, $filename = false) //for latest gnparser
    {
        $big_json = file_get_contents($this->sh[$what]['source'].$filename);
        $arrs = json_decode($big_json, true);
        $i = 0;
        foreach($arrs as $arr) {
            $i++; if($i == 1) continue;
            $json = json_encode($arr);
            // echo "\n$json\n"; continue;
            //copied below -------------------------------------- start
            $name = $arr['verbatim'];
            if(($i % 500) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
                if(($i % 500) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 500) == 0) echo " - already saved/cached";
            //copied below -------------------------------------- end
        }
    }
    private function save_2local_gnparsed_file($what, $filename = false)
    {
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed_DONE.txt')) {
            echo "\nAll names for [$what] has already been cached.\n";
            return;
        }
        $i = 0;
        if(!$filename) $filename = "name_only_gnparsed.txt";
        foreach(new FileIterator($this->sh[$what]['source'].$filename) as $line => $json) {
            $i++; if($i == 1) continue;
            // echo "\n$json\n";
            $arr = json_decode($json, true);
            // print_r($arr); exit;
            $name = $arr['verbatim'];
            if(($i % 1000) == 0) echo "\n".number_format($i)." $name - ";
            //now check if json already cached. Ignore if it does and save/cache it if it doesn't
            $options['cache_path'] = $this->smasher_download_options['cache_path'];
            $md5 = md5($name);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
            if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
            $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
            if(!file_exists($cache_path) || filesize($cache_path) == 0) {
                if(($i % 1000) == 0) echo " - saving...";
                if($FILE = Functions::file_open($cache_path, 'w')) {
                    fwrite($FILE, $json);
                    fclose($FILE);
                }
            }
            else if(($i % 1000) == 0) echo " - already saved/cached";
        }
        if(file_exists($this->sh[$what]['source'].'name_only_gnparsed.txt')) Functions::file_rename($this->sh[$what]['source'].'name_only_gnparsed.txt', $this->sh[$what]['source'].'name_only_gnparsed_DONE.txt');
    }
    private function get_json_from_cache($name, $options = array()) //json generated by gnparser
    {
        // download_wait_time
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = false;
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) {
            // echo "\nRetrieving cache ($name)...\n"; //good debug
            $file_contents = file_get_contents($cache_path);
            $cache_is_valid = true;
            if(($file_contents && $cache_is_valid) || (strval($file_contents) == "0" && $cache_is_valid)) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $file_contents;
                if($options['expire_seconds'] === false) return $file_contents;
            }
            @unlink($cache_path);
        }
        //generate json
        echo "\nGenerating cache json for the first time ($name)...\n";
        $cmd = 'gnparser name -f json-compact "'.$name.'"';
        $json = shell_exec($cmd);
        if($json) {
            if($FILE = Functions::file_open($cache_path, 'w+')) {
                fwrite($FILE, $json);
                fclose($FILE);
            }
            //just to check if you can now get the canonical
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value)     echo " ---> OK [$ret]";
                elseif($ret = @$obj->canonicalName->value)  echo " ---> OK [$ret]";
                else                                        echo " ---> FAIL";
            }
        }
        return $json;
    }
    
    private function write2file($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['accepted_id'] . "\t|\t" . $t['name'] . "\t|\t" . 'synonym' . "\t|\t" . "\t|\t" . "\n");
        elseif($ext == "tax") fwrite($fn, $t['taxon_id'] . "\t|\t" . $t['parent_id'] . "\t|\t" . $t['name'] . "\t|\t" . $t['rank'] . "\t|\t" . $t['source'] . "\t|\t" . "\n");
    }
    private function get_canonical_via_api($sciname, $options)
    {
        $json = Functions::lookup_with_cache($this->gnparser.urlencode($sciname), $options);
        if($obj = json_decode($json)) {
            if($ret = @$obj->namesJson[0]->canonical_name->value) return $ret;
        }
    }
    private function gnsparse_canonical($sciname, $method, $download_options = array())
    {
        if(!$download_options) $download_options = $this->smasher_download_options;
        
        $sciname = str_replace('"', "", $sciname);
        
        if($ret = @$this->problematic_names[$sciname]) return $ret;
        
        /*
        if($sciname == "all") return "all";
        elseif($sciname == "root") return "root";
        elseif($sciname == "not Bacteria Haeckel 1894") return "not Bacteria";
        // elseif($sciname == "unplaced extinct Onychophora") return "unplaced extinct Onychophora";
        // elseif($sciname == "[Cellvibrio] gilvus") return "[Cellvibrio] gilvus";
        // elseif($sciname == "unplaced Cryptophyceae") return "unplaced Cryptophyceae";
        //force
        if($sciname == "Ichthyoidei- Eichwald, 1831") $sciname = "Ichthyoidei Eichwald, 1831";
        elseif($sciname == "Raniadae- Smith, 1831") $sciname = "Raniadae Smith, 1831";
        elseif($sciname == "prokaryote") $sciname = "Prokaryote";
        elseif($sciname == "prokaryotes") $sciname = "Prokaryotes";
        elseif($sciname == "Amblyomma (Cernyomma) hirtum. Camicas et al., 1998") $sciname = "Amblyomma (Cernyomma) hirtum Camicas et al., 1998";
        elseif($sciname == "Cryptops (Cryptops) vector Chamberlin 1939") $sciname = "Cryptops (Cryptops) vector";
        */
        if($method == "api") {
            if($canonical = self::get_canonical_via_api($sciname, $this->smasher_download_options)) return $canonical;
        }
        elseif($method == "cache") {
            $json = self::get_json_from_cache($sciname, $download_options);
            if($obj = json_decode($json)) {
                if($ret = @$obj->canonical_name->value) return $ret;
                elseif($ret = @$obj->canonicalName->value) return $ret;
                else { //the gnparser code was updated due to bug. So some names has be be re-run using cmdline OR API with expire_seconds = 0

                    self::write_gnparser_failures($this->what, $obj->verbatim);
                    return $obj->verbatim; //un-successfull
                    
                    /* might need it in the future when a new version of gnparser will be used
                    $options = $this->smasher_download_options; $options['expire_seconds'] = 60*60*24*7; //1 week
                    $json = self::get_json_from_cache($sciname, $options);
                    if($obj = json_decode($json)) {
                        if($ret = @$obj->canonical_name->value) return $ret;
                        elseif($ret = @$obj->canonicalName->value) return $ret;
                        else {
                            self::write_gnparser_failures($this->what, $obj->verbatim);
                            return $obj->verbatim; //un-successfull
                        }
                    }
                    */
                }
            }
        }
        echo("\nInvestigate cannot get canonical name [$sciname][$method]\n");
    }
    private function write_gnparser_failures($what, $name, $postfix = "")
    {
        $path = $this->sh[$what]['source']."../zFailures/$what".$postfix.".txt";
        if($FILE = Functions::file_open($path, 'a')) {
            // echo "\nadded name failures [$what]: [$name]\n"; //good debug
            fwrite($FILE, $name."\n");
            fclose($FILE);
        }
    }
    public function analyze_eol_meta_xml($meta_xml_path, $row_type = false)
    {
        if(!$row_type) $row_type = "http://rs.tdwg.org/dwc/terms/Taxon";
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            
            if(!isset($xml->table)) {
                if(isset($xml->core)) $xml_table = $xml->core; //e.g. meta.xml from WoRMS http://www.marinespecies.org/export/eol/WoRMS2EoL.zip
            }
            else                      $xml_table = $xml->table;
            
            foreach($xml_table as $tbl) {
                if($tbl['rowType'] == $row_type) {
                    if(in_array($tbl['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
                    else                                                    $ignoreHeaderLines = false;
                    $fields = array();
                    foreach($tbl->field as $f) {
                        $term = (string) $f['term'][0];
                        $uris[] = $term;
                        $fields[] = pathinfo($term, PATHINFO_FILENAME);
                    }
                    $file = (string) $tbl->files->location;
                    return array('fields' => $fields, 'taxon_file' => $file, 'file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
                }
                else {}
            }
            exit("\nInvestigate undefined row_type [$row_type]\n");
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 02.\n");
    }
    private function analyze_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            // print_r($xml->core);
            if(!isset($xml->core)) {
                echo "\nNo core entry in meta.xml\n";
                return "No core entry in meta.xml";
            }
            if(in_array($xml->core['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
            else                                                          $ignoreHeaderLines = false;
            $fields = array();
            if($xml->core['index'] == 0) $fields[] = "index";
            if($xml->core->field[0]['index'] == 0) $fields = array(); //this will ignore <id index="0" />
            foreach($xml->core->field as $f) {
                $term = (string) $f['term'][0];
                $uris[] = $term;
                $fields[] = pathinfo($term, PATHINFO_FILENAME);
            }
            $file = (string) $xml->core->files->location;
            return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 01.\n");
    }
    private function utility_write_all_names($meta)
    {
        $what = $meta['what']; $i = 0; $ctr = 1;
        //initialize this report file
        $path = $this->sh[$what]['source']."../zFailures/$what"."_failures.txt"; if(file_exists($path)) unlink($path);
        
        // $WRITE = fopen($this->sh[$what]['source'].$what."_ALL_NAMES_".$ctr.".txt", "w"); //replaced...
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym_".$ctr.".txt", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
        fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");
        
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); //exit; //use to test if field - value is OK
            
            /*replaced...
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            if($val = @$rec['scientificName']) fwrite($WRITE, $val."\n");
            */
            
            //=======================================================================================
            if(!self::is_record_valid($what, $rec)) continue; //main criteria filter
            $t = array();
            $t['parent_id']     = $rec['parentNameUsageID'];
            $t['name']          = self::fix_sciname($rec['scientificName']);
            $t['taxon_id']      = $rec['taxonID'];
            $t['accepted_id']   = @$rec['acceptedNameUsageID'];
            $t['rank']          = ($val = @$rec['taxonRank']) ? self::clean_rank($val): "no rank";
            $t['source']        = '';
            if($this->sh[$what]['has_syn']) {
                if(($t['accepted_id'] != $t['taxon_id']) && $t['accepted_id'] != "") {
                    self::write2file_tmp("syn", $fn_syn, $t);
                    $has_synonym = true;
                }
                elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file_tmp("tax", $fn_tax, $t);
            }
            elseif(($t['accepted_id'] == $t['taxon_id']) || $t['accepted_id'] == "") self::write2file_tmp("tax", $fn_tax, $t);
            //=======================================================================================
            if(($i % 500000) == 0) { //500000 orig
                // fclose($WRITE); //replaced...
                fclose($fn_tax); fclose($fn_syn);
                
                echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."taxonomy_".$ctr.".txt --output ".$this->sh[$what]['source']."taxonomy_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
                $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."synonym_".$ctr.".txt --output ".$this->sh[$what]['source']."synonym_".$ctr."_gnparsed.txt";
                $out = shell_exec($cmd); echo "\n$out\n";
                
                $ctr++;
                // $WRITE = fopen($this->sh[$what]['source'].$what."_ALL_NAMES_".$ctr.".txt", "w"); //replaced...
                $fn_tax = fopen($this->sh[$what]['source']."taxonomy_".$ctr.".txt", "w"); //will overwrite existing
                $fn_syn = fopen($this->sh[$what]['source']."synonym_".$ctr.".txt", "w"); //will overwrite existing
                fwrite($fn_tax, implode("\t", $this->taxonomy_header_tmp)."\n");
                fwrite($fn_syn, implode("\t", $this->synonym_header_tmp) ."\n");
            }
        }
        // fclose($WRITE); //replaced...
        fclose($fn_tax); fclose($fn_syn);
        //last batch
        echo "\nrunning gnparser to taxonomy_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."taxonomy_".$ctr.".txt --output ".$this->sh[$what]['source']."taxonomy_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        echo "\nrunning gnparser to synonym_".$ctr.".txt\n";
        $cmd = "gnparser file -f simple --input ".$this->sh[$what]['source']."synonym_".$ctr.".txt --output ".$this->sh[$what]['source']."synonym_".$ctr."_gnparsed.txt";
        $out = shell_exec($cmd); echo "\n$out\n";
        
        //now we then create the final taxonomy.tsv by looping to all taxonomy_?.txt
        $meta['ctr'] = $ctr;
        self::build_final_taxonomy_tsv($meta, "taxonomy");
        self::build_final_taxonomy_tsv($meta, "synonym");
    }
    private function fix_sciname($str)
    {
        $str = str_ireplace("?kornick", "Škornick", $str);
        $str = str_ireplace("?erný", "Černý", $str);
        $str = str_ireplace("?tyroký", "Čtyroký", $str);
        $str = str_ireplace("†", "", $str);
        return $str;
    }
    private function build_final_taxonomy_tsv($meta, $pre)
    {
        $ctr = $meta['ctr']; $what = $meta['what'];
        $fn_tax = fopen($this->sh[$what]['source'].$pre.".tsv", "w"); //will overwrite existing
        fwrite($fn_tax, implode("\t|\t", $this->{$pre."_header"})."\t|\t"."\n");
        
        for ($c = 1; $c <= $ctr; $c++) {
            $txtfile = $this->sh[$what]['source'].$pre."_".$c."_gnparsed.txt"; echo "\nprocessing [$txtfile]\n";

            //just for progress indicator
            $total_rows = self::get_total_rows($txtfile);
            echo "\nTotal rows: [".number_format($total_rows)."]\n";
            $modulo = self::get_modulo($total_rows);
            
            $i = 0;
            foreach(new FileIterator($txtfile) as $line_number => $line) {
                $i++; if(($i % $modulo) == 0) echo "\n $pre $c of $ctr - ".number_format($i)." ";
                if($i == 1) $line = strtolower($line);
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    //fix $fields: important
                    $count = count($this->{$pre."_header_tmp"});
                    $fields[$count+1] = 'canonicalName';
                    $fields[$count+2] = 'valueRanked';
                    $fields[$count+3] = 'other1';
                    $fields[$count+4] = 'other2';
                    $fields[$count+5] = 'other3';
                    // print_r($fields);
                    continue;
                }
                else {
                    if(!@$row[0]) continue;
                    $k = 0; $rec = array();
                    foreach($fields as $fld) {
                        $rec[$fld] = @$row[$k];
                        $k++;
                    }
                }
                // print_r($rec); exit("\nstopx\n");
                /*Array(
                    [f33063e7-083e-5910-83b4-9a96c170f159] => 9d241baa-f15b-5231-815f-69c2b59ad659
                    [name] => Limacoccus brasiliensis (Hempel, 1934)
                    [uid] => 316423
                    [parent_uid] => 43080004
                    [rank] => species
                    [canonicalName] => Limacoccus brasiliensis
                    [valueRanked] => Limacoccus brasiliensis
                    [other1] => (Hempel 1934)
                    [other2] => 1934
                    [other3] => 3
                )
                Array(
                    [a274cdda-3ca9-559b-9476-6e45eea18eed] => 59f5f484-b052-52f1-8fc0-0b288ca6f2ee
                    [name] => Canceraspis brasiliensis Hempel, 1934
                    [uid] => 316423
                    [type] => synonym
                    [canonicalName] => Canceraspis brasiliensis
                    [valueRanked] => Canceraspis brasiliensis
                    [other1] => Hempel 1934
                    [other2] => 1934
                    [other3] => 3
                )*/
                
                if(!$rec['canonicalName']) self::write_gnparser_failures($what, $rec['name'], "_failures");
                
                $t = array();
                $t['parent_id']     = @$rec['parent_uid'];      //only for taxonomy
                $t['name']          = $rec['canonicalName'];    //for both
                $t['taxon_id']      = $rec['uid'];              //only for taxonomy
                $t['accepted_id']   = $rec['uid'];              //only for synonym
                $t['rank']          = @$rec['rank'];            //only for taxonomy
                $t['source']        = '';
                if($pre == "taxonomy") self::write2file("tax", $fn_tax, $t);
                else                   self::write2file("syn", $fn_tax, $t); //originally fn_syn, from above
            }
        }
    }
    private function write2file_tmp($ext, $fn, $t)
    {
        if($ext == "syn")     fwrite($fn, $t['name'] . "\t" . $t['accepted_id'] . "\t" . 'synonym' . "\n");
        elseif($ext == "tax") fwrite($fn, $t['name'] . "\t" . $t['taxon_id'] . "\t" . $t['parent_id'] . "\t" . $t['rank'] . "\n");
    }
    private function run_TSV_file_with_gnparser_new($file, $what)
    {
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$file) as $line => $row) {
            $i++;
            if(!$row) continue;
            $arr = explode("\t", $row);
            // if(($i % 10000) == 0) echo "\n".number_format($i);
            echo " -".number_format($i)."- ";
            /*Array(
                [0] => 77f24f37-c0ee-5d53-b21b-56a9c1c2e25b
                [1] => Caulanthus crassicaulis var. glaber M.E. Jones   -   verbatim
                [2] => Caulanthus crassicaulis glaber                   -   canonicalName->value
                [3] => Caulanthus crassicaulis var. glaber              -   canonicalName->valueRanked
                [4] => M. E. Jones
                [5] => 
                [6] => 1
            )*/
            $verbatim = $arr[1];
            if(!self::cache_exists($verbatim)) {
                echo "\n$verbatim -> no rec";
                self::gnsparse_canonical($verbatim, 'cache');
            }
        }
    }
    private function cache_exists($name, $options = array())
    {
        if(!isset($options['cache_path'])) $options['cache_path'] = $this->smasher_download_options['cache_path'];
        $md5 = md5($name);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.json";
        if(file_exists($cache_path)) return true;
        else                         return false;
    }
    private function get_total_rows($file)
    {
        /* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document */
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    private function get_modulo($total_rows)
    {
        if($total_rows >= 500000) $modulo = 100000;
        elseif($total_rows >= 100000 && $total_rows < 500000) $modulo = 50000;
        elseif($total_rows >= 50000 && $total_rows < 100000) $modulo = 10000;
        else $modulo = 5000;
        return $modulo;
    }
}
?>