<?php
namespace php_active_record;
/* TRAM-808: Map EOL IDs for Dynamic Hierarchy Version 1.1.
Statistics
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_Mapping_EOL_IDs');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main
$resource_id = "DH_v1_1_Map_EOL_IDs";
$func = new DH_v1_1_Mapping_EOL_IDs($resource_id);

/* tests only
$source_str = "trunk:26c58c2e-b902-4cd8-8596-9c7796338c98,ictv:ICTV:Viruses";
$arr = $func->get_all_source_identifiers($source_str); print_r($arr);
$arr = $func->get_all_sources($source_str); print_r($arr);
exit("\n-end test-\n");
*/

// $func->create_append_text(); exit("\n-end create_append_text-\n");//done only once; worked OK
$func->start_tram_808();
// exit("\n-end for now-\n");
// $func->generate_dwca($resource_id); //use taxonomy_4dwca.txt from Step 5.
// unset($func);
// Functions::finalize_dwca_resource($resource_id, true, false);
// run_diagnostics($resource_id);

//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
// /*
Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
// */
?>