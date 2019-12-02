<?php
namespace php_active_record;
/* DATA-1812
globi_associations	Monday 2019-07-01 09:53:16 AM	{"association.tab":3097482,"occurrence_specific.tab":2288584,"reference.tab":327413,"taxon.tab":215828}
globi_associations	Thursday 2019-07-04 06:20:42 AM	{"association.tab":3097726,"occurrence_specific.tab":2288805,"reference.tab":327528,"taxon.tab":215846}
globi_associations	Tuesday 2019-09-24 02:36:25 PM	{"association.tab":3251759,"occurrence_specific.tab":2438087,"reference.tab":467632,"taxon.tab":217885} MacMini
globi_associations	Wednesday 2019-09-25 01:40:19 AM{"association.tab":3251759,"occurrence_specific.tab":2438087,"reference.tab":467632,"taxon.tab":217885} eol-archive
globi_associations	Sunday 2019-12-01 08:41:08 PM	{"association.tab":3484127,"occurrence_specific.tab":2642172,"reference.tab":457021,"taxon.tab":234408} eol-archive Consistent OK
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* //main operation
require_library('connectors/DwCA_Utility');
$resource_id = "globi_associations";
$dwca = 'https://depot.globalbioticinteractions.org/snapshot/target/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
// $dwca = 'http://localhost/cp/GloBI_2019/eol-globi-datasets-1.0-SNAPSHOT-darwin-core-aggregated.zip';
$func = new DwCA_Utility($resource_id, $dwca);

$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/Taxon', 'http://eol.org/schema/reference/Reference'); //orig in partners meta XML. Overwritten below.
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/reference/reference'); //was forced to lower case in DwCA_Utility.php

$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, true, $timestart);
// */
?>