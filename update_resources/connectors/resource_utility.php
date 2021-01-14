<?php
namespace php_active_record;
/* This is generic way of calling ResourceUtility
removing taxa without MoF records.
first client: https://jenkins.eol.org/job/EOL%20Connectors/job/Environmental%20tagger%20for%20EOL%20resources/job/Wikipedia%20EN%20(English)/
              environments_2_eol.php for Wikipedia EN 

php update_resources/connectors/resource_utility.php _ '{"resource_id": "617_final", "task": "remove_taxa_without_MoF"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "wiki_en_report", "task": "report_4_Wikipedia_EN_traits"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "WoRMS2EoL_zip", "task": "add_canonical_in_taxa"}'
 -------------------------- START of metadata_recoding  --------------------------
task_123
php update_resources/connectors/resource_utility.php _ '{"resource_id": "692_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "201_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "726_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "griis_meta_recoded", "task": "metadata_recoding"}'

task_67
php update_resources/connectors/resource_utility.php _ '{"resource_id": "770_meta_recoded", "task": "metadata_recoding"}'


php update_resources/connectors/resource_utility.php _ '{"resource_id": "natdb_meta_recoded_1", "task": "metadata_recoding"}'
->occurrenceRemarks
php update_resources/connectors/resource_utility.php _ '{"resource_id": "natdb_meta_recoded", "task": "metadata_recoding"}'
->lifeStage


php update_resources/connectors/resource_utility.php _ '{"resource_id": "copepods_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "42_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "727_meta_recoded", "task": "metadata_recoding"}'

php update_resources/connectors/resource_utility.php _ '{"resource_id": "707_meta_recoded", "task": "metadata_recoding"}'
-> case where lifeStage is a col in MoF => move to a col in occurrence.

----------start Coral traits
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded_1", "task": "metadata_recoding"}'
-> fixes lifeStage
php update_resources/connectors/resource_utility.php _ '{"resource_id": "cotr_meta_recoded", "task": "metadata_recoding"}'
-> fixes eventDate as row in MoF
----------end Coral traits

----------start WoRMS
WoRMS
-> case where lifeStage & sex is a row child in MoF => move to a col in occurrence
-> case where statisticalMethod is a row in MoF => move to a col in MoF
php update_resources/connectors/resource_utility.php _ '{"resource_id": "26_meta_recoded_1", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "26_meta_recoded", "task": "metadata_recoding"}'
----------end WoRMS

task_45
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test2_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "test3_meta_recoded", "task": "metadata_recoding"}'
 -------------------------- END of metadata_recoding --------------------------

-------------------------- START of Unrecognized_fields --------------------------
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Cicadellinae_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Deltocephalinae_meta_recoded", "task": "metadata_recoding"}'
php update_resources/connectors/resource_utility.php _ '{"resource_id": "Appeltans_et_al_meta_recoded", "task": "metadata_recoding"}'

BioImages, the virtual fieldguide, UK (168.tar.gz)
php update_resources/connectors/resource_utility.php _ '{"resource_id": "168_meta_recoded", "task": "metadata_recoding"}'

-------------------------- END of Unrecognized_fields --------------------------




201	                Wed 2020-10-14 02:15:39 PM	{"MoF":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":518.17, "min":8.640000000000001, "hr":0.14}}
201_meta_recoded	Thu 2020-10-29 10:54:43 AM	{"MoF":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":216.07, "min":3.6, "hr":0.06}}
less MoF is expected for 201_meta_recoded
201	                Tue 2020-12-01 09:56:56 PM	{"MoF":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":503.2, "min":8.390000000000001, "hr":0.14}}
201_meta_recoded	Tue 2020-12-01 10:00:43 PM	{"MoF":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":226.54, "min":3.78, "hr":0.06}}

726	            Thursday 2019-12-05 09:09:30 AM	{"MoF":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":17.5,"min":0.29,"hr":0}}
726_meta_recoded	Thu 2020-10-29 11:44:26 AM	{"MoF":21485, "occurrence.tab":2838, "taxon.tab":968, "time_elapsed":{"sec":15.11, "min":0.25, "hr":0}}

770	                Tue 2020-09-15 09:20:16 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":false}
770_meta_recoded	Wed 2020-10-28 09:37:23 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":{"sec":8.01, "min":0.13, "hr":0}}

770	                Wed 2020-12-02 01:13:05 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":false}
770_meta_recoded	Wed 2020-12-02 01:13:13 AM	{"MoF":979, "occurrence_specific.tab":978, "reference.tab":1, "taxon.tab":921, "time_elapsed":{"sec":7.83, "min":0.13, "hr":0}}

natdb	                Fri 2020-07-17 11:24:08 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":293.77, "min":4.9, "hr":0.08}}
natdb_meta_recoded	    Wed 2020-10-28 09:43:50 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":82.73, "min":1.38, "hr":0.02}}
natdb_meta_recoded_1	Thu 2020-11-12 08:42:00 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":84.57, "min":1.41, "hr":0.02}}
natdb_meta_recoded	    Thu 2020-11-12 08:43:21 AM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":80.65, "min":1.34, "hr":0.02}}

natdb	                Tue 2020-12-01 10:00:47 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":312.39, "min":5.21, "hr":0.09}}
natdb_meta_recoded_1	Tue 2020-12-01 10:02:15 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":87.87, "min":1.46, "hr":0.02}}
natdb_meta_recoded	    Tue 2020-12-01 10:03:40 PM	{"MoF":129380, "occurrence_specific.tab":96894, "reference.tab":11, "taxon.tab":2778, "time_elapsed":{"sec":84.94, "min":1.42, "hr":0.02}}



copepods	        Thursday 2019-07-11 08:30:46 AM	{"MoF":21345,"occurrence.tab":18259,"reference.tab":925,"taxon.tab":2644}
copepods_meta_recoded	Wed 2020-10-28 09:47:22 AM	{"MoF":21345, "occurrence_specific.tab":18259, "reference.tab":925, "taxon.tab":2644, "time_elapsed":{"sec":21.39, "min":0.36, "hr":0.01}}

42	            Sun 2020-09-13 04:41:23 PM	{"agent.tab":146, "MoF":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7343.42, "min":122.39, "hr":2.04}}
42_meta_recoded	Thu 2020-10-29 12:22:42 PM	{"agent.tab":146, "MoF":177712, "media_resource.tab":135702, "occurrence_specific.tab":161031, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":313.42, "min":5.22, "hr":0.09}}

42	            Wed 2020-12-02 12:38:02 AM	{"agent.tab":146, "MoF":165551, "media_resource.tab":135702, "occurrence_specific.tab":148873, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":7330.38, "min":122.17, "hr":2.04}}
42_meta_recoded	Wed 2020-12-02 12:42:55 AM	{"agent.tab":146, "MoF":165551, "media_resource.tab":135702, "occurrence_specific.tab":148873, "reference.tab":32237, "taxon.tab":95593, "vernacular_name.tab":157469, "time_elapsed":{"sec":291.26, "min":4.85, "hr":0.08}}

griis	            Wed 2020-10-28 02:09:49 AM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":1007.65, "min":16.79, "hr":0.28}}
griis_meta_recoded	Mon 2020-11-02 08:36:01 AM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":57.34, "min":0.96, "hr":0.02}}

griis	            Tue 2020-12-01 10:13:29 PM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":1001.8, "min":16.7, "hr":0.28}}
griis_meta_recoded	Tue 2020-12-01 10:14:28 PM	{"MoF":85499, "occurrence_specific.tab":57655, "taxon.tab":14891, "time_elapsed":{"sec":59.3, "min":0.99, "hr":0.02}}

cotr	            Sat 2020-10-10 06:43:23 AM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":82.14, "min":1.37, "hr":0.02}}
cotr_meta_recoded_1	Wed 2020-11-04 05:27:50 AM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":53.87, "min":0.9, "hr":0.01}}
cotr_meta_recoded	Wed 2020-11-04 05:28:32 AM	{"MoF":52298, "occurrence.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":41.62, "min":0.69, "hr":0.01}}

cotr	            Tue 2020-12-01 09:58:59 PM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":73.78, "min":1.23, "hr":0.02}}
cotr_meta_recoded_1	Tue 2020-12-01 09:59:45 PM	{"MoF":56648, "occurrence_specific.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":45.34, "min":0.76, "hr":0.01}}
cotr_meta_recoded	Tue 2020-12-01 10:00:30 PM	{"MoF":52298, "occurrence.tab":33475, "reference.tab":555, "taxon.tab":1547, "time_elapsed":{"sec":45.16, "min":0.75, "hr":0.01}}


727	                Fri 2020-09-11 12:40:30 AM	{"agent.tab":1, "MoF":581778, "media_resource.tab":5, "occurrence_specific.tab":636468, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Mon 2020-11-02 08:59:01 AM	{"agent.tab":1, "MoF":581778, "media_resource.tab":5, "occurrence_specific.tab":636468, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":524.71, "min":8.75, "hr":0.15}}
727	                Tue 2020-12-01 10:23:40 PM	{"agent.tab":1, "MoF":581779, "media_resource.tab":5, "occurrence_specific.tab":636469, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Tue 2020-12-01 10:32:27 PM	{"agent.tab":1, "MoF":581779, "media_resource.tab":5, "occurrence_specific.tab":636469, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":526.2, "min":8.77, "hr":0.15}}

26_meta_recoded_1	Wed 2020-11-11 08:13:07 AM	{"agent.tab":1682, "MoF.tab":3180852, "media.tab":91653, "occurrence.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":{"sec":3044.71, "min":50.75, "hr":0.85}}
26_meta_recoded	    Wed 2020-11-11 09:00:11 AM	{"agent.tab":1682, "MoF.tab":2535563, "media.tab":91653, "occurrence.tab":2157834, "reference.tab":670315, "taxon.tab":367878, "vernacular_name.tab":82322, "time_elapsed":{"sec":2824, "min":47.07, "hr":0.78}}

26	                Fri 2020-12-11 03:09:55 AM	{"agent.tab":1690, "MoF.tab":3334428, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":false}
26_meta_recoded_1	Fri 2020-12-11 04:02:47 AM	{"agent.tab":1690, "MoF.tab":3190221, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":3082.64, "min":51.38, "hr":0.86}}
26_meta_recoded	    Fri 2020-12-11 04:49:31 AM	{"agent.tab":1690, "MoF.tab":2544844, "media.tab":91778, "occurrence.tab":2167107, "reference.tab":672534, "taxon.tab":368401, "vernacular_name.tab":82328, "time_elapsed":{"sec":2803.96, "min":46.73, "hr":0.78}}

707	            Tuesday 2020-01-28 08:46:58 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":34.47,"min":0.57,"hr":0.01}}
707_meta_recoded	Wed 2020-11-11 08:09:23 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":16.94, "min":0.28, "hr":0}}
707_meta_recoded	Thu 2020-11-12 08:41:47 AM	{"MoF.tab":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":12.84, "min":0.21, "hr":0}}

707	            Wed 2020-12-02 01:07:42 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.39, "min":0.16, "hr":0}}
707_meta_recodedWed 2020-12-02 01:07:51 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.09, "min":0.15, "hr":0}}
707	            Wed 2020-12-02 01:09:05 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9, "min":0.15, "hr":0}}
707_meta_recodedWed 2020-12-02 01:09:14 AM	{"MoF":4078, "occurrence_specific.tab":632, "taxon.tab":632, "time_elapsed":{"sec":9.06, "min":0.15, "hr":0}}


692_meta_recoded	Wed 2020-10-21 11:28:41 AM	{"MoF":3849108, "occurrence_specific.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1359.92, "min":22.67, "hr":0.38}}

692	                Tue 2020-12-01 10:13:22 PM	{"MoF":1924554, "occurrence.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1129.53, "min":18.83, "hr":0.31}}
692_meta_recoded	Tue 2020-12-01 10:37:34 PM	{"MoF":3849108, "occurrence_specific.tab":486561, "taxon.tab":162187, "time_elapsed":{"sec":1451.08, "min":24.18, "hr":0.4}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$task = $param['task'];
print_r($param);

if($task == 'remove_taxa_without_MoF') {
    if(Functions::is_production()) $dwca_file = '/u/scripts/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    // /* ---------- customize here ----------
    if($resource_id == '617_final') $resource_id = "wikipedia_en_traits";
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
    // ----------------------------------------*/
}
elseif($task == 'report_4_Wikipedia_EN_traits') { //for Jen: https://eol-jira.bibalex.org/browse/DATA-1858?focusedCommentId=65155&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65155
    $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/wikipedia_en_traits.tar.gz';
    // $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/708.tar.gz'; //testing investigation only
}
elseif($task == 'add_canonical_in_taxa') {
    if($resource_id == 'WoRMS2EoL_zip') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/other_files/WoRMS/WoRMS2EoL.zip";
                                        // $dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";
        else                            $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
    }
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}
elseif($task == 'metadata_recoding') {
    if($resource_id == '692_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/692.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/692.tar.gz";
    }
    elseif($resource_id == '201_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/201.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/201.tar.gz";
    }
    elseif($resource_id == '726_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/726.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/726.tar.gz";
    }
    elseif($resource_id == 'griis_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/griis.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/griis.tar.gz";
    }
    elseif($resource_id == '770_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/770.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/770.tar.gz";
    }

    elseif($resource_id == 'natdb_meta_recoded_1') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/natdb.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/natdb.tar.gz";
    }
    elseif($resource_id == 'natdb_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/natdb_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/natdb_meta_recoded_1.tar.gz";
    }

    elseif($resource_id == 'copepods_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/copepods.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/copepods.tar.gz";
    }
    elseif($resource_id == '42_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/42.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/42.tar.gz";
    }
    elseif($resource_id == '727_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/727.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/727.tar.gz";
    }
    elseif($resource_id == '707_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/707.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/707.tar.gz";
    }
    elseif($resource_id == 'cotr_meta_recoded_1') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/cotr.tar.gz";
    }
    elseif($resource_id == 'cotr_meta_recoded') {
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/cotr_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/cotr_meta_recoded_1.tar.gz";
    }

    elseif($resource_id == 'test_meta_recoded') { //task_45: no actual resource atm.
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/test_mUnit_sMethod.zip";
    }
    elseif($resource_id == 'test2_meta_recoded') { //task_45: first client is WorMS (26).
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/test_mUnit_sMethod_asChildInMoF.zip";
    }
    elseif($resource_id == 'test3_meta_recoded') { //task_67: first client is WorMS (26).
        $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/test_lifeStage_sex_asChildInMoF.zip";
    }

    elseif($resource_id == '26_meta_recoded_1') { //task_45: statisticalMethod | measurementUnit
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/26.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26.tar.gz";
    }
    elseif($resource_id == '26_meta_recoded') { //task_67: lifeStage | sex
        if(Functions::is_production())  $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/26_meta_recoded_1.tar.gz";
        else                            $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26_meta_recoded_1.tar.gz";
    }

    // /* Unrecognized_fields
    elseif($resource_id == 'Cicadellinae_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/7408693e-094a-4335-a0c9-b114d7dc64d3/download/archive.zip";
    }
    elseif($resource_id == 'Deltocephalinae_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/e4a7239b-7297-4a75-9fe9-1f5cff5e20d7/resource/5d6f7139-0d1f-4d9f-adb0-15ec7a1ea16e/download/archive.zip";
    }
    elseif($resource_id == 'Appeltans_et_al_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://opendata.eol.org/dataset/b5b2b058-8b2c-4a2d-98f9-f4f5bba77ae5/resource/d9adfd62-01d7-41e1-a125-34130ce33cf4/download/archive.zip";
    }
    elseif($resource_id == '168_meta_recoded') { //task_200: contributor, creator, publisher from Document to Agents
        $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/168.tar.gz";
    }
    // */
    
    else exit("\nERROR: [$task] resource_id not yet initialized. Will terminate.\n");
}

else exit("\nERROR: task not yet initialized. Will terminate.\n");
process_resource_url($dwca_file, $resource_id, $task, $timestart);

function process_resource_url($dwca_file, $resource_id, $task, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);
    
    if($task == 'remove_taxa_without_MoF') {
        if(in_array($resource_id, array('wikipedia_en_traits'))) {
            $preferred_rowtypes = array();
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
            /* These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
            http://rs.tdwg.org/dwc/terms/taxon
            */
        }
    }
    elseif($task == 'report_4_Wikipedia_EN_traits') {
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //best to set this to array() and just set $excluded_rowtypes to taxon
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
    }
    elseif($task == 'add_canonical_in_taxa') {
        /* working but not needed for DH purposes
        $preferred_rowtypes = array();
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        */
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
        $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');
    }

    elseif($task == 'metadata_recoding') {
        $preferred_rowtypes = array();
        if(in_array($resource_id, array('201_meta_recoded', '726_meta_recoded', 'cotr_meta_recoded', 'test2_meta_recoded',
                                        '26_meta_recoded_1'))) {
            $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact'); //means occurrence tab is just carry-over
        }
        elseif(in_array($resource_id, array('Cicadellinae_meta_recoded', 'Deltocephalinae_meta_recoded', 'Appeltans_et_al_meta_recoded',
            '168_meta_recoded'))) $excluded_rowtypes = array('http://eol.org/schema/media/document', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        else $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');

        /* works but just testing. COMMENT IN REAL OPERATION
        if($resource_id == '168_meta_recoded') $excluded_rowtypes[] = 'http://eol.org/schema/agent/agent';
        */
    }
    
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>