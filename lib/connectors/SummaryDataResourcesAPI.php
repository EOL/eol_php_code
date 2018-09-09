<?php
namespace php_active_record;
/* [SDR.php] */
class SummaryDataResourcesAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR', 'timeout' => 60*5, 'expire_seconds' => false, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        // $this->file['parent child']['fields'] = array('parent_term_URI', 'subclass_term_URI');
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        // $this->file['preferred synonym']['fields'] = array('preferred_term_URI', 'deprecated_term_URI');
        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated');

        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2a.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2a.csv";
        
        $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        if(Functions::is_production())  $this->working_dir = "/extra/summary data resources/page_ids/";
        else                            $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/";
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
    }
    function start()
    {
        // self::utility_compare();
        // self::utility_compare2("/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_raw_step1.txt");
        
        /* Important Step: working OK - commented for now.
        self::working_dir(); self::generate_page_id_txt_files(); exit("\n\nText file generation DONE.\n\n");
        */
        /* another test...
        self::initialize();
        $uris = array("http://www.geonames.org/6255151", "https://www.wikidata.org/entity/Q41228", "http://www.marineregions.org/gazetteer.php?p=details&id=1904");
        $uris = array("http://purl.obolibrary.org/obo/ENVO_00000856");
        $uris = array("http://purl.obolibrary.org/obo/ENVO_00000873");
        foreach($uris as $uri) {
            echo "\n\nprocessing $uri:\n";
            if($arr = @$this->children_of[$uri]) {
                echo " -- has children:";
                print_r($arr);
            }
            else echo " -- no children";
            if($arr = @$this->parents_of[$uri]) {
                echo "\n -- has parents:";
                print_r($arr);
            }
            else echo " -- no parents";
            
        }
        exit("\n");
        */
        self::initialize();
        $page_id = 46559197; $predicate = "http://eol.org/schema/terms/Present";
        // $page_id = 46559217; $predicate = "http://eol.org/schema/terms/Present";
        $page_id = 7662; $predicate = "http://eol.org/schema/terms/Habitat";
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) {
            echo "\nNo records for [$page_id] [$predicate].\n";
            return;
        }
        $uris = self::get_valueUris_from_recs($recs);
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $ISVAT = self::get_initial_shared_values_ancestry_tree($recs); //initial "shared values ancestry tree"
        $ISVAT = self::sort_ISVAT($ISVAT);
        $info = self::add_new_nodes_for_NotRootParents($ISVAT);
        $new_nodes = $info['new_nodes'];    
        echo "\n\nnew nodes 0:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        
        
        $info['new_nodes'] = self::sort_ISVAT($new_nodes);
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        echo "\n\nnew nodes 1:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 1:\n"; print_r($roots);
        
        // /* merged...
        $info = self::merge_nodes($info, $ISVAT);
        $ISVAT = $info['new_isvat'];
        $roots     = $info['new_roots'];
        $new_nodes = array();
        // */
        
        // /*
        //for jen: 
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        echo "\n\ninitial shared values ancestry tree:\n";
        foreach($ISVAT as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nnew nodes 2:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 2:\n"; print_r($roots);
        // exit("\n");
        // */
        
        //for step 1: So, first you must identify the tips- any values that don't appear in the left column. The parents, for step one, will be the values to the left of the tip values.
        if(count($ISVAT) <= 5 ) {}
        else { // > 5
            $step_1 = self::get_step_1($ISVAT, $roots);
            echo "\nStep 1:".count($step_1)."\n";
            foreach($step_1 as $a) echo "\n".$a;
        }
        
        /*
        $joined = array_merge($ISVAT, $new_nodes);
        if(count($joined) <= 5 ) {}
        else { // > 5
            $set_1 = self::get_step_1($joined, $roots); //all uri where parent is root
            echo "\nSet 1:\n";
            foreach($set_1 as $a) echo "\n".$a;
        }
        */
        exit("\nelix\n");
    }
    /*
    private function get_step_1($isvat)
    {
        $final = array();
        foreach($isvat as $a) {
            $left[] = $a[0]; $right[] = $a[1];
            $parent_of_right[$a[1]] = $a[0];
        }
        foreach($right as $r) {
            if(!in_array($r, $left)) $tips[$r] = '';
        }
        $tips = array_keys($tips);
        foreach($tips as $tip) {
            $parent = @$parent_of_right[$tip];
            $final[$parent] = '';
        }
        
        $final = array_keys($final);
        asort($final);
        
        return $final;
        exit;
    }
    */
    // /*
    private function get_step_1($isvat, $roots)
    {
        $final = array();
        foreach($isvat as $rec) {
            if(in_array($rec[0], $roots)) $final[] = $rec[1];
        }
        $final = array_unique($final);
        asort($final);
        return $final;
    }
    // */
    private function utility_compare()
    {
        foreach(new FileIterator($this->jen_isvat) as $line_number => $line) {
            $arr[] = explode("\t", $line);
        }
        asort($arr); foreach($arr as $a) echo "\n".$a[0]."\t".$a[1];
        exit("\njen_isvat.txt\n");
    }
    private function utility_compare2($file)
    {
        foreach(new FileIterator($file) as $line_number => $line) {
            $arr[$line] = '';
        }
        $arr = array_keys($arr);
        asort($arr); foreach($arr as $a) echo "\n".$a;
        exit("\n[$file]\n");
    }
    
    private function merge_nodes($info, $ISVAT)
    {
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        
        $new_isvat = array_merge($ISVAT, $new_nodes);
        $new_isvat = self::sort_ISVAT($new_isvat);
        
        $new_roots = $roots;
        foreach($new_isvat as $a) {
            if(!$a[0]) $new_roots[] = $a[1];
        }
        
        return array('new_roots' => $new_roots, 'new_isvat' => $new_isvat);
    }
    private function sort_ISVAT($arr) //also remove parent nodes where there is only one child. Make child an orphan.
    {
        rsort($arr);
        foreach($arr as $a) {
            @$temp[$a[0]][$a[1]] = '';
            $right_cols[$a[1]] = '';
            $temp2[$a[0]] = $a[1];
        }
        asort($temp);
        // print_r($temp);
        foreach($temp as $key => $value) $totals[$key] = count($value);
        // print_r($totals);

        $discard_parents = array(); echo "\n--------------------\n";
        foreach($totals as $key => $total_children) {
            if($total_children == 1) {
                echo "\n $key: ";
                if(isset($right_cols[$key])) echo " -- also a child in the orig tree";
                
                // echo "\nelix elix\n";
                // print_r($temp[$key]); exit("\ncha cha\n");
                elseif(isset($this->original_nodes[$temp2[$key]])) {
                    echo "\nxxx".@$temp2[$key]."\n";
                    /* "Ancestors can be removed if they are parents of only one node BUT that node must not be an original node" */
                }
                else $discard_parents[] = $key;
            }
        }
        // print_r($discard_parents);
        
        $final = array();
        foreach($arr as $a) {
            if(in_array($a[0], $discard_parents)) $final[] = array("", $a[1]);
            else                                  $final[] = array($a[0], $a[1]);
        }
        asort($final);
        $final = array_unique($final, SORT_REGULAR);
        return $final;
    }
    private function generate_page_id_txt_files()
    {
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; echo " $i";
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    [resource_pk] => M_00238837
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [literal] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/

                $path = self::get_md5_path($this->working_dir, $rec['page_id']);
                $txt_file = $path . $rec['page_id'] . ".txt";
                if(file_exists($txt_file)) {
                    $WRITE = fopen($txt_file, 'a');
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
                else {
                    $WRITE = fopen($txt_file, 'w');
                    fwrite($WRITE, implode("\t", $fields)."\n");
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
            }
        }
        fclose($file);
    }
    private function get_md5_path($path, $taxonkey)
    {
        $md5 = md5($taxonkey);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($path . $cache1)) mkdir($path . $cache1);
        if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
        return $path . "$cache1/$cache2/";
    }
    private function assemble_recs_for_page_id_from_text_file($page_id, $predicate)
    {
        $recs = array();
        $path = self::get_md5_path($this->working_dir, $page_id);
        $txt_file = $path . $page_id . ".txt";
        echo "\n$txt_file\n";
        $i = 0;
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /* Array( old during development with Jen
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [predicate] => http://eol.org/schema/terms/Present
                    [value_uri] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
                )*/
                /*Array(
                    [eol_pk] => R143-PK39533505
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [resource_pk] => 17255
                    [predicate] => http://eol.org/schema/terms/WeaningAge
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://genomics.senescence.info/species/entry.php?species=Arctocephalus_tropicalis
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => 
                    [literal] => 
                    [measurement] => 239
                    [units] => http://purl.obolibrary.org/obo/UO_0000033
                    [normal_measurement] => 0.6543597746702533
                    [normal_units_uri] => http://purl.obolibrary.org/obo/UO_0000036
                    [resource_id] => 50
                )*/
                if($predicate == $rec['predicate'] && $rec['value_uri']) $recs[] = $rec;
                $this->original_nodes[$rec['value_uri']] = '';
            }
        }
        return $recs;
    }
    private function initialize()
    {
        self::working_dir();
        self::generate_terms_values_child_parent_list();
        self::generate_preferred_child_parent_list();
    }
    private function add_new_nodes_for_NotRootParents($list)
    {   //1st step: get unique parents
        foreach($list as $rec) {
            // print_r($rec); exit;
            /*Array(
                [0] => http://www.geonames.org/6255151
                [1] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
            )*/
            $unique[$rec[0]] = '';
        }
        //2nd step: check if parent is not root (meaning has parents), if yes: get parent and add the new node:
        foreach(array_keys($unique) as $child) {
            // echo "\n$child: ";
            if($arr = @$this->parents_of[$child]) { // echo " - not root, has parents ".count($arr);
                foreach($arr as $new_parent) {
                    if($new_parent) $recs[] = array($new_parent, $child);
                }
            }
            else $roots[] = $child; // echo " - already root";
        }
        return array('roots' => $roots, 'new_nodes' => $recs);
    }
    private function get_valueUris_from_recs($recs)
    {
        $uris = array();
        foreach($recs as $rec) $uris[] = $rec['value_uri'];
        return $uris;
    }
    private function get_initial_shared_values_ancestry_tree($recs)
    {
        $final = array();
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($recs as $rec) {
            $term = $rec['value_uri'];
            $parent = self::get_parent_of_term($term);
            $final[] = array($parent, $term);
        }
        return $final;
    }
    function start_ok()
    {
        self::initialize();
        $uris = self::given_value_uri(); //just during development
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=australia";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4366";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4364";
        $terms[] = "http://www.geonames.org/2186224";
        $terms[] = "http://www.geonames.org/3370751";                               //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1914";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1904";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1910";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4276";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4365";
        $terms[] = "http://www.geonames.org/953987";
        $terms[] = "http://www.marineregions.org/mrgid/1914";
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($terms as $term) self::get_parent_of_term($term);
        exit("\nend 01\n");
    }
    private function given_value_uri()
    {
        return array("http://www.marineregions.org/gazetteer.php?p=details&id=australia", "http://www.marineregions.org/gazetteer.php?p=details&id=4366", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=4364", "http://www.geonames.org/2186224", "http://www.geonames.org/3370751", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=1914", "http://www.marineregions.org/gazetteer.php?p=details&id=1904", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=1910", "http://www.marineregions.org/gazetteer.php?p=details&id=4276", 
        "http://www.marineregions.org/gazetteer.php?p=details&id=4365", "http://www.geonames.org/953987");
    }
    private function set_ancestor_ranking_from_set_of_uris($uris)
    {
        $final = array(); $final_preferred = array();
        foreach($uris as $term) {
            if(!$term) continue;
            if($preferred_terms = @$this->preferred_names_of[$term]) {
                // echo "\nThere are preferred term(s):\n";
                // print_r($preferred_terms);
                foreach($preferred_terms as $pterm) {
                    @$final_preferred[$pterm]++;
                    // echo "\nparent(s) of $pterm:";
                    if($parents = @$this->parents_of[$pterm]) {
                        // print_r($parents);
                        foreach($parents as $parent) @$final[$parent]++;
                    }
                    // else echo " -- NO parent";
                }
            }
            else { //no preferred term
                if($parents = @$this->parents_of[$term]) {
                    foreach($parents as $parent) @$final[$parent]++;
                }
                // else exit("\n\nHmmm no preferred and no immediate parent for term: [$term]\n\n"); //seems acceptable
            }
        }//end main
        /*
        foreach($uris as $term) {
            if($parents = @$this->parents_of[$term]) {
                foreach($parents as $parent) @$final[$parent]++;
            }
        }//end main
        */
        arsort($final);
        // print_r($final);
        $final = array_keys($final);
        // print_r($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        // print_r($final_preferred);
        $final_preferred = array_keys($final_preferred);
        // print_r($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {
        if(!$preferred_terms) {
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                    return $parent;
                }
            }
        }
        else {
            // don't do THIS if preferred + parents are all inside $this->ancestor_ranking
            $all_inside = true;
            $temp = array_merge($parents, $preferred_terms);
            foreach($temp as $id) {
                if(!in_array($id, $this->ancestor_ranking)) $all_inside = false;
            }
            if(!$all_inside) {
                //THIS:
                foreach($this->ancestor_ranking as $parent) {
                    if(in_array($parent, $preferred_terms)) {
                        $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                        return $parent;
                    }
                }
            }
            
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) && in_array($preferred_terms[0], $this->ancestor_ranking_preferred)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                return $preferred_terms[0];
            }
        }
        
        //2nd option
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    private function get_parent_of_term($term)
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n";
        echo "term in question: [$term]:\n";
        /*
        if($parents = @$this->parents_of[$term]) {
            echo "\nParents:\n"; print_r($parents);
        }
        else echo "\nNO PARENT\n";
        */
        
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $term) {
                echo "\nparent(s) of $term:\n";
                if($parents = @$this->parents_of[$term]) {
                    print_r($parents);
                    $chosen = self::get_rank_most_parent($parents, $preferred_terms);
                    echo "\nCHOSEN PARENT: ".$chosen."\n";
                    return $chosen;
                }
                else echo " -- NO parent";
            }
            /* seems not needed
            foreach($preferred_terms as $term) {
                echo "\nprefered name of $term:";
                if($names = @$this->prefered_name_of[$term]) {
                    print_r($names);
                }
                else echo " -- NO preferred name";
            }
            */
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = @$this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                $chosen = self::get_rank_most_parent($immediate_parents);
                echo "\nCHOSEN PARENT*: ".$chosen."\n";
                return $chosen;
                // foreach($immediate_parents as $immediate) {
                //     echo "\nparent(s) of $immediate:";
                //     if($parents = @$this->parents_of[$immediate]) {
                //         print_r($parents);
                //     }
                //     else echo " -- NO parent";
                // }
            }
        }
    }
    private function generate_preferred_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym']['path'], $this->download_options);
        $file = fopen($temp_file, 'r'); $i = 0;
        $fields = $this->file['preferred synonym']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [preferred] => http://marineregions.org/mrgid/19161
                    [deprecated] => http://marineregions.org/gazetteer.php?p=details&id=19161
                )*/
                $this->preferred_names_of[$rec['deprecated']][] = $rec['preferred'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function get_ancestry_of_term($page_id)
    {
        $final = array(); $final2 = array();
        if($parent_ids = @$this->terms_values_child_parent_list[$page_id]) {
            foreach($parent_ids as $temp_id) {
                while(true) {
                    if($parent_ids2 = @$this->terms_values_child_parent_list[$temp_id]) {
                        foreach($parent_ids2 as $temp_id2) {
                            while(true) {
                                if($parent_ids3 = @$this->terms_values_child_parent_list[$temp_id2]) {
                                    foreach($parent_ids3 as $temp_id3) {
                                        $final['L3'][] = $temp_id3;
                                        $final2[$temp_id3] = '';
                                        $temp_id2 = $temp_id3;
                                    }
                                }
                                else break;
                            }
                            $final['L2'][] = $temp_id2;
                            $final2[$temp_id2] = '';
                            $temp_id = $temp_id2;
                        }
                    }
                    else break;
                }
                $final['L1'][] = $temp_id;
                $final2[$temp_id] = '';
                $page_id = $temp_id;
            }
        }
        return array($final, array_keys($final2));
        /*
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->terms_values_child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
        */
    }
    private function generate_terms_values_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child']['path'], $this->download_options);
        $file = fopen($temp_file, 'r');
        $i = 0;
        $fields = $this->file['parent child']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [parent] => ﻿http://purl.obolibrary.org/obo/ENVO_00000111
                    [child] => http://purl.obolibrary.org/obo/ENVO_01000196
                )*/
                $this->parents_of[$rec['child']][] = $rec['parent'];
                $this->children_of[$rec['parent']][] = $rec['child'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    
    function start_v1()
    {
        self::working_dir();
        $this->child_parent_list = self::generate_child_parent_list();
        // /* tests...
        $predicate = "http://reeffish.org/occursIn";
        $predicate = "http://eol.org/schema/terms/Present";
        $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms); exit;
        
        self::print_taxon_and_ancestry($similar_terms);
        
        self::given_predicates_get_values_from_traits_csv($similar_terms);

        exit("\n-end tests-\n");
        // */
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        // remove temp dir
        /* un-comment in real operation
        recursive_rmdir($this->main_paths['temp_dir']);
        echo ("\n temporary directory removed: " . $this->main_paths['temp_dir']);
        */
    }
    private function generate_child_parent_list()
    {
        $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $final[$rec['child']] = $rec['parent'];
            }
        }
        fclose($file);
        return $final;
    }
    private function print_taxon_and_ancestry($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Taxa (with ancestry) having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "ancestry"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    $ancestry = self::get_ancestry_using_page_id($rec['page_id']);
                    // print_r($ancestry);
                    if(!isset($printed_already[$rec['page_id']])) {
                        fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], implode("|", $ancestry)))."\n");
                        $printed_already[$rec['page_id']] = '';
                    }
                }
            }
        }
        fclose($file);
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        // exit("\nelix 100\n");
    }

    private function given_predicates_get_values_from_traits_csv($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Records from traits.csv having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "predicate", "value_uri OR literal"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) {
                $fields = $line;
                print_r($fields); //exit;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    [resource_pk] => M_00329828
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [literal] => http://eol.org/schema/terms/Cordillera_de_Merida_paramo
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    
                    fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], $rec['predicate'], self::get_value($rec)))."\n");
                }
            }
        }
        fclose($file);
    }
    private function get_ancestry_using_page_id($page_id)
    {
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
    }
    private function get_value($rec)
    {
        if($val = @$rec['value_uri']) return $val;
        if($val = @$rec['literal']) return $val;
    }
    private function setup_working_dir()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        return $paths;
    }
    private function working_dir()
    {
        if(Functions::is_production()) {
            if(!($info = self::setup_working_dir())) return;
            $this->main_paths = $info;
        }
        else { //local development only
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
            $this->main_paths = $info;
        }
    }
    private function given_predicate_get_similar_terms($pred)
    {
        $final = array();
        $final[$pred] = ''; //processed predicate is included
        
        //from 'parent child':
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[0] == $pred) $final[$line[1]] = '';
        }
        fclose($file); unlink($temp_file);
        
        //from 'preferred synonym':
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[1] == $pred) $final[$line[0]] = '';
        }
        fclose($file); unlink($temp_file);
        $final = array_keys($final);
        
        //start write
        $WRITE = fopen($this->report_file, 'w');
        fwrite($WRITE, "REPORT FOR PREDICATE: $pred\n\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fwrite($WRITE, "Similar terms from [terms relationship files]:\n\n");
        foreach($final as $url) fwrite($WRITE, $url . "\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        //end write
        
        return $final;
    }

        /*
    Hi Jen, we are now just down to 1 discrepancy. But I think (hopefully) the last one is just something you've missed doing it manually.
    But more importantly, let me share my algorithm how I chose the parent. Please review closely and suggest improvement or even revise if needed.
    I came up with this using our case scenario for page_id 46559197 and your explanations why you chose your parents.
    Like what I said it came down to now just 1 discrepancy.

    I process each of the 12 terms, one by one.
        http://www.marineregions.org/gazetteer.php?p=details&id=australia
        http://www.marineregions.org/gazetteer.php?p=details&id=4366
        http://www.marineregions.org/gazetteer.php?p=details&id=4364
        http://www.geonames.org/2186224
        http://www.geonames.org/3370751
        http://www.marineregions.org/gazetteer.php?p=details&id=1914
        http://www.marineregions.org/gazetteer.php?p=details&id=1904
        http://www.marineregions.org/gazetteer.php?p=details&id=1910
        http://www.marineregions.org/gazetteer.php?p=details&id=4276
        http://www.marineregions.org/gazetteer.php?p=details&id=4365
        http://www.geonames.org/953987
        http://www.marineregions.org/mrgid/1914

    1.  First I get the preferred term(s) of the term in question. 
        Case A: If there are any: e.g. (pref1, pref2) Mostly only 1 preferred term.
            I get the immediate parent(s) each of the preferred terms. 
            e.g. pref1_parent1, pref1_parent2, pref1_parent3

        Case B: If there are NO preferred term(s)
            I get the immediate parent(s) of the term in question.
            e.g. term_parent1, term_parent2

    2.  Then whatever the Case be, I sent the collected items to the ranking selection.
            */
}
?>