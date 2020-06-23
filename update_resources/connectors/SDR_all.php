<?php
namespace php_active_record;
/*  DATA-1777: Writing resource files
    https://eol-jira.bibalex.org/browse/DATA-1777?focusedCommentId=63478&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63478
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAllAPI');

/*
$a[3] = 'three';
$a[1] = 'one';
$a[5] = 'five';
print_r($a);
ksort($a);
print_r($a);
exit("\n");
*/

/*
$children_of[111]['01'] = 'species';
$children_of[111]['02'] = 'species';
$children_of[111]['03'] = 'species';
print_r($children_of);
exit;
*/
/*
$arr = array(2,4,6,8,10);
$arr = array(1,2,3,4);
$arr = array(1,2);
$middle = get_middle_record($arr);
echo "\n$arr[$middle]\n";
exit("\n");
*/

/*
$a = array(5319, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);     
$a = array_reverse($a); print_r($a);
$temp = $a;
foreach($a as $id) {
    array_shift($temp);
    if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
    else                         $children_of[$id] = $temp;
    $children_of[$id] = array_unique($children_of[$id]);
}

// $a = Array(5110, 5083, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);
// $a = array_reverse($a);                                                     print_r($a);
// $temp = $a;
// foreach($a as $id) {
//     array_shift($temp);
//     if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
//     else                         $children_of[$id] = $temp;
//     $children_of[$id] = array_unique($children_of[$id]);
// }

print_r($children_of);
exit("\n");
*/

/*
$str = "http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000065, http://purl.obolibrary.org/obo/ENVO_00000067, 
http://purl.obolibrary.org/obo/ENVO_00000081, http://purl.obolibrary.org/obo/ENVO_00000086, http://purl.obolibrary.org/obo/ENVO_00000220, http://purl.obolibrary.org/obo/ENVO_00000264, 
http://purl.obolibrary.org/obo/ENVO_00000360, http://purl.obolibrary.org/obo/ENVO_00000446, http://purl.obolibrary.org/obo/ENVO_00001995, http://purl.obolibrary.org/obo/ENVO_00002000, 
http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_01001305, http://purl.obolibrary.org/obo/ENVO_00000078, 
http://purl.obolibrary.org/obo/ENVO_00000113, http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000316, 
http://purl.obolibrary.org/obo/ENVO_00000320, http://purl.obolibrary.org/obo/ENVO_00000358, http://purl.obolibrary.org/obo/ENVO_00000486, http://purl.obolibrary.org/obo/ENVO_00000572, 
http://purl.obolibrary.org/obo/ENVO_00000856, http://purl.obolibrary.org/obo/ENVO_00002030, http://purl.obolibrary.org/obo/ENVO_00002040, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_00000002, http://purl.obolibrary.org/obo/ENVO_00000016, http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands, 
http://purl.obolibrary.org/obo/ENVO_01001125";

$arr = explode(",", $str);
$arr = array_map('trim', $arr);
asort($arr); print_r($arr); 

echo "\n rows: ".count($arr);
foreach($arr as $tip) echo "\n$tip";
exit("\ntotal: ".count($arr)."\n");
*/

/* //tests
$parents = array(1,2,3);
$preferred_terms = array(4,5);
$inclusive = array_merge($parents, $preferred_terms);
print_r($inclusive);
exit("\n-end tests'\n");
*/

/*
$arr = json_decode('["717136"]');
if(!is_array($arr) && is_null($arr)) {
    $arr = array();
    echo "\nwent here 01\n";
}
else {
    echo "\nwent here 02\n";
    print_r($arr);
}
exit("\n");
*/

/*
$a1 = array('45511473' => Array(46557930));
$a2 = array('308533' => Array(1642, 46557930));
$a3 = $a1 + $a2; print_r($a3);
exit("\n");
*/

// $json = "[]";
// $arr = json_decode($json, true);
// if(is_array($arr)) echo "\nis array\n";
// else               echo "\nnot array\n";
// if(is_null($arr)) echo "\nis null\n";
// else               echo "\nnot null\n";
// print_r($arr);
// // if(!is_array($arr) && is_null($arr)) $arr = array();
// exit("\n");

// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/99/cd/R96-PK42697173.txt";
// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/38/49/R344-PK19315117.txt";
// $json = file_get_contents($file);
// print_r(json_decode($json, true)); exit;

// $terms = array("Braunbär", " 繡球菌", "Eli");
// foreach($terms as $t){
//     echo "\n".$t."\n";
//     // $t = utf8_encode($t); echo "\n".$t."\n";
//     $t = Functions::conv_to_utf8($t); echo "\n".$t."\n";
// }
// exit("\nexit muna\n");

ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 'SDR_all';

/* for every new all-trait-export, must update these vars: Done already for 2019Nov11 */
$folder_date = "20190822";
$folder_date = "20191111";

$func = new SummaryDataResourcesAllAPI($resource_id, $folder_date);

/* build data files - MySQL tables --- worked OK
$func->build_MySQL_table_from_text('DH_lookup'); exit; //used for parent methods. TO BE RUN EVERY NEW DH. Done already for DHv1.1
*/

/* can run one after the other: Done for 2019Aug22 | 2019Nov11 ======================================================== this block worked OK

// ----------------------------update 'inferred' start
$func->update_inferred_file(); exit("\n-end 2019Nov11-\n");
    // csv file rows:  1,199,241   2019Nov11
    //                             1,199,241
// ----------------------------update 'inferred' end

$func->generate_refs_per_eol_pk_MySQL(); exit("\n-end 2019Nov11-\n");
    // metadata_refs   984,498 2019Aug22
    //               1,207,934 2019Nov11

$func->build_MySQL_table_from_csv('metadata_LSM'); exit("\n-end 2019Nov11-\n"); //used for method: lifestage and statMeth()
    // metadata_LSM    1,727,545   2019Aug22
    //                 1,878,398   2019Nov11

// these four are for the main traits table 
    $func->generate_page_id_txt_files_MySQL('BV');
    // $func->generate_page_id_txt_files_MySQL('BVp'); //excluded, same as BV
    $func->generate_page_id_txt_files_MySQL('TS');
    $func->generate_page_id_txt_files_MySQL('TSp');
    $func->generate_page_id_txt_files_MySQL('LSM');
    // traits_BV   2019Aug22   3,525,177
    //             2019Nov11   5,724,786
    // 
    // traits_LSM  2019Aug22   190,833
    //             2019Nov11   309,906
    //             
    // traits_TS   2019Aug22   2,178,526
    //             2019Nov11   3.089,998
    // 
    // traits_TSp  2019Aug22   1,402,799
    //             2019Nov11   1,969,893   exit("\n-end 2019Nov11-\n");


    // preparation for parent basal values. This takes some time.
    // this was first manually done last: Jun 9, 2019 - for ALL TRAIT EXPORT - SDR_all_readmeli.txt for more details
    // INSERT INTO page_ids_Present       SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Present'
    // INSERT INTO page_ids_Habitat       SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://eol.org/schema/terms/Habitat';
    // INSERT INTO page_ids_FLOPO_0900032 SELECT DISTINCT t.page_id from SDR.traits_BV t WHERE t.predicate = 'http://purl.obolibrary.org/obo/FLOPO_0900032';

    // $func->pre_parent_basal_values(); return; //Worked OK on the new fresh harvest 'All Trait Export': 2019Jun13 & 2019Aug22. But didn't work anymore for 2019Nov11.
    // On 2019Nov11. Can no longer accommodate big files, memory-wise I think. Used manual again, login to "mysql>", notes in SDR_all_readmeli.txt instead.
    // page_ids_FLOPO_0900032  2019Aug22    189,741
    //                         2019Nov11    160,560
    // 
    // page_ids_Habitat        2019Aug22    344,704
    //                         2019Nov11    391,046
    // 
    // page_ids_Present        2019Aug22    1,242,249
    //                         2019Nov11    1,116,012   exit("\n-end 2019Nov11-\n");

    $func->pre_parent_basal_values(); return; //Updated script. Works OK as of Jun 23, 2020. No more manual step needed.                                              
========================================================================================================== */ 

/* IMPORTANT STEP - for parent BV and parent TS =============================================================================== should run every new all-trait-export.
$func->build_up_children_cache(); exit("\n-end build_up_children_cache()-\n"); //can run max 3 connectors. auto-breakdown installed. Just 3 connectors so CPU wont max out.
                                  exit("\n-end 2019Nov11-\n");
// use this for single page_id:
$page_id = 6551609;
$page_id = 2366;
$page_id = 46451825; //aborted
$func->build_up_children_cache($page_id); exit("\n-end build_up_children_cache() for [$page_id]-\n");

// $json = $func->get_children_from_txt_file($page_id); //check the file path

// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/d3/b1/2366_ch.txt");
// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/ee/20/6551609_ch.txt");
// $json = file_get_contents("/Volumes/AKiTiO4/web/cp/summary_data_resources/page_ids_20190822/26/dd/2774383_ch.txt");
// $arr = json_decode($json, true); print_r($arr);

=============================================================================================================================== */
/*
$func->investigate_metadata_csv(); exit("\nJust a utility. Not part of steps.\n");
*/

// $func->test_basal_values('BV');          //return;
// $func->print_basal_values('BV');         //return; //main orig report -- 3.91 hrs
// $func->test_parent_basal_values('BV', false);   //return; //2nd parm is debugModeYN
// $func->print_parent_basal_values('BV');  return; //main orig report -- 92.75 minutes | 1.25 hrs
// $func->print_parent_basal_values('BV', false, false, true);  return; //4th param true means it is debugMode true

// /* for multiple page_ids: BV
// $page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
// $page_ids = array(7662);
// $func->print_parent_basal_values('BV', $page_ids, 'Carnivora'); return; //used also for test for SampleSize task
// $page_ids = array(1); $func->print_parent_basal_values('BV', $page_ids, 'Metazoa'); //return;
// foreach($page_ids as $page_id) $final[$page_id] = array('taxonRank' => 'not species', 'Landmark' => 1); //good but not used eventually
// */

// $func->test_taxon_summary('TS');             //return;
// $func->print_taxon_summary('TS');            //return; //main orig report - 36.30 minutes | 9.88 minutes | 10.73 minutes
// $func->test_parent_taxon_summary('TSp');     //return;        //[7665], http://purl.obolibrary.org/obo/RO_0002470
// $func->print_parent_taxon_summary('TSp');    //return; //main orig report - 4.23 hrs | 4.89 hrs Aug12'19 | 2.01 hrs | 14.3 hrs Nov14'19
// $func->print_parent_taxon_summary('TSp', array('7662' => array('taxonRank' => 'not species', 'Landmark' => 1)), '7662'); return; //not used eventually

/* for multiple page_ids: TS
$page_ids = array(7662, 4528789, 7675, 7669, 7672, 10647853, 7673, 7674, 4529519, 39311345, 7663, 4524096, 7665, 7677, 7676, 7664, 7670, 7671, 7666, 7667, 7668);
$page_ids = array(7662);
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora'); return;
// $func->print_parent_taxon_summary('TSp', $page_ids, 'Carnivora', true); return; //4th param true means it is debugMode true
$func->print_parent_taxon_summary('TSp', false, false, true); return; //4th param true means it is debugMode true
*/

// $func->test_lifeStage_statMeth('LSM');
$func->print_lifeStage_statMeth('LSM');   //return; //main orig report //49.38 min. | 48.11 min. | 1.2 hrs |

// $func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>