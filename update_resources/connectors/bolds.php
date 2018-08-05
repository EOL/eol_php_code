<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-737
BOLDS connector for new API service
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
// $GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();

// $resource_id = "Annelida_new"; //Animals
// $resource_id = "Rhodophyta_new"; //Plants
// $resource_id = "Basidiomycota_new"; //Fungi
// $resource_id = "Protista_new";
$resource_id = '81';
// $resource_id = "Arthropoda"; //Animals
// $resource_id = 'Priapulida'; //smallest phylum

/* tests...
// $json = Functions::lookup_with_cache("http://www.boldsystems.org/index.php/API_Tax/TaxonData?taxId=30367&dataTypes=all");
// print_r(json_decode($json, true));

// $taxids[1]['images'] = array("a",'b','c');
// $taxids[2]['images'] = array("e",'f','g');
// $taxids[1]['parentID'] = "parent of 1";
// $taxids[2]['parentID'] = "parent of 2";
// foreach($taxids as $taxid => $images) {
//     echo "\n taxid is [$taxid]";
//     print_r($images);
// }

require_library('connectors/BOLDS_DumpsServiceAPI');
$func = new BOLDS_DumpsServiceAPI($resource_id);
if($info = $func->get_info_from_page(170890)) {
    print_r($info);
}
exit("\n");
*/

// /* using Dumps
require_library('connectors/BOLDS_DumpsServiceAPI');
$func = new BOLDS_DumpsServiceAPI($resource_id);
$func->start_using_dump();
unset($func);
Functions::finalize_dwca_resource($resource_id, false);
// */

require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
