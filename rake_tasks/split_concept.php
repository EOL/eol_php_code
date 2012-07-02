<?php

// The name is misleading: more appropriately, it moves entries from one page to another. I'm keeping the name
// (for the rake task) for historical consistency.

include_once(dirname(__FILE__) . "/../config/environment.php");
include_once(dirname(__FILE__) . "/../lib/MoveEntryHandler.php");

MoveEntryHandler::split_concept(array('taxon_concept_id_from'        => @$argv[1],
                                      'hierarchy_entry_id'           => @$argv[2],
                                      'taxon_concept_id_to'          => @$argv[3],
                                      'bad_match_hierarchy_entry_id' => @$argv[4],
                                      'confirmed'                    => @$argv[5],
                                      'reindex'                      => @trim($argv[6])));


?>
