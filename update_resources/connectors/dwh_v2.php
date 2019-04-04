<?php
namespace php_active_record;
/* For the 2nd smasher run.
https://eol-jira.bibalex.org/browse/TRAM-805: Dynamic Hierarchy Version 1.1.

Note: separation files in zip format is provided by Katja, both for ver 1.0 (newSeparationFiles.zip) and ver 1.1 (separationFiles.zip)

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
/* e.g. php dws.php _ gbif */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/DHSourceHierarchiesAPI_v2');
$timestart = time_elapsed();
ini_set('memory_limit','7096M'); //required

/*
$haystack = "ASW:v-Diasporus-sapo-Batista-Köhler-Mebert-Hertz-and-Vesely-2016-Zool.-J.-Linn.-Soc.-178:-274.";
$replace = "_elix_";
$needle = ":";
$pos = strpos($haystack, $needle);
if ($pos !== false) {
    $new = substr_replace($haystack, $replace, $pos, strlen($needle));
}
echo "\n$haystack";
echo "\n$new";
exit("\n");
*/

// /* //main operation ------------------------------------------------------------
$resource_id = "2019_03_28";
$func = new DHSourceHierarchiesAPI_v2($resource_id);
// $func->start($cmdline_params['what']); //main to generate the respective taxonomy.tsv (and synonym.tsv if available).

// $func->syn_integrity_check(); exit("\n-end syn_integrity_check-\n"); //to check record integrity of synoyms spreadsheet: 1XreJW9AMKTmK13B32AhiCVc7ZTerNOH6Ck_BJ2d4Qng
/* but this check is driven by taxonID and NOT by the sciname. It is the sciname that is important.
So generally we don't need this syn_integrity_check(). We can just add to phython file all those we know that are synonyms.
*/

// $func->generate_python_file(); exit("\n-end generate_python_file-\n");           //to generate script entry to build_dwh.py
// $func->clean_up_destination_folder(); exit("\n-end cleanup-\n");    //to do before uploading hierarchies to eol-smasher server

// $func->test($cmdline_params['what']);                    //for testing only

// this is now obsolete in TRAM-805: Dynamic Hierarchy Version 1.1.
// $func->start($cmdline_params['what'], "CLP_adjustment"); //from CLP #3 from: https://eol-jira.bibalex.org/browse/TRAM-800?focusedCommentId=63045&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63045


// $func->compare_results();                                //a utility to compare results. During initial stages
// -------------------------------------------------------------------------------- */

// /* =========== generate DwCA --- OK
// $func->save_all_ids_from_all_hierarchies_2MySQL(); exit("\n-end txt 2MySQL-\n"); //one-time only. NOT YET DONE FOR Ver 1.1.
// this will then be appended to MySQL table ids_scinames in DWH database.

// $func->generate_dwca($resource_id);
// Functions::finalize_dwca_resource($resource_id, false, false);
// =========== */

/* utility ========================== a good utility after generating DwCA --- OK
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

$undefined_accepted = $func->check_if_all_parents_have_entries($resource_id, true, false, false, "acceptedNameUsageID");
echo "\nTotal undefined_accepted:" . count($undefined_accepted)."\n"; unset($undefined_accepted);
=====================================*/

/* another utility but was never used actually:
$without = $func->get_all_taxa_without_parent($resource_id, true); //true means output will write to text file
echo "\nTotal taxa without parents:" . count($without)."\n";
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
