<?php

class SiteStatistics
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////  Main Stats
    ////////////////////////////////////
    
    public function total_pages()
    {
        if(isset($this->total_pages)) return $this->total_pages;
        $this->total_pages = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc WHERE tc.published=1 AND tc.supercedure_id=0");
        if($result && $row=$result->fetch_assoc()) $this->total_pages = $row['count'];
        return $this->total_pages;
    }
    
    public function total_pages_in_col()
    {
        if(isset($this->total_pages_in_col)) return $this->total_pages_in_col;
        $this->total_pages_in_col = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM hierarchy_entries he WHERE he.hierarchy_id=".Hierarchy::col_2009());
        if($result && $row=$result->fetch_assoc()) $this->total_pages_in_col = $row['count'];
        return $this->total_pages_in_col;
    }
    
    public function total_pages_not_in_col()
    {
        if(isset($this->total_pages_not_in_col)) return $this->total_pages_not_in_col;
        
        $this->total_pages_not_in_col = $this->total_pages() - $this->total_pages_in_col();
        return $this->total_pages_not_in_col;
    }
    
    public function pages_with_content()
    {
        if(isset($this->pages_with_content)) return $this->pages_with_content;
        $this->pages_with_content = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND (tcc.text=1 OR tcc.image=1 OR tcc.flash=1 OR tcc.youtube=1 OR tcc.map=1)");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_content = $row['count'];
        return $this->pages_with_content;
    }
    
    public function pages_with_text()
    {
        if(isset($this->pages_with_text)) return $this->pages_with_text;
        $this->pages_with_text = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text = $row['count'];
        return $this->pages_with_text;
    }
    
    public function pages_with_images()
    {
        if(isset($this->pages_with_images)) return $this->pages_with_images;
        $this->pages_with_images = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_images = $row['count'];
        return $this->pages_with_images;
    }
    
    public function pages_with_text_and_images()
    {
        if(isset($this->pages_with_text_and_images)) return $this->pages_with_text_and_images;
        $this->pages_with_text_and_images = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text_and_images = $row['count'];
        return $this->pages_with_text_and_images;
    }
    
    public function pages_with_images_no_text()
    {
        if(isset($this->pages_with_images_no_text)) return $this->pages_with_images_no_text;
        $this->pages_with_images_no_text = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_images_no_text = $row['count'];
        return $this->pages_with_images_no_text;
    }
    
    public function pages_with_text_no_images()
    {
        if(isset($this->pages_with_text_no_images)) return $this->pages_with_text_no_images;
        $this->pages_with_text_no_images = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1 AND tcc.image=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text_no_images = $row['count'];
        return $this->pages_with_text_no_images;
    }
    
    public function pages_with_links_no_text()
    {
        if(isset($this->pages_with_links_no_text)) return $this->pages_with_links_no_text;
        $this->pages_with_links_no_text = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(tc.id)) count FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN mappings m ON (tcn.name_id=m.name_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_links_no_text = $row['count'];
        return $this->pages_with_links_no_text;
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////  Content By Category
    ////////////////////////////////////
    
    public function pages_with_vetted_objects()
    {
        if(isset($this->pages_with_vetted_objects)) return $this->pages_with_vetted_objects;
        $this->pages_with_vetted_objects = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_with_vetted_objects = array_sum($content_by_category);
        return $this->pages_with_vetted_objects;
    }
    
    public function pages_in_col_no_content()
    {
        if(isset($this->pages_in_col_no_content)) return $this->pages_in_col_no_content;
        $this->pages_in_col_no_content = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) WHERE he.hierarchy_id=".Hierarchy::col_2009()." AND tcc.text=0 AND tcc.image=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_in_col_no_content = $row['count'];
        return $this->pages_in_col_no_content;
    }
    
    public function pages_in_col_one_category()
    {
        if(isset($this->pages_in_col_one_category)) return $this->pages_in_col_one_category;
        $this->pages_in_col_one_category = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_in_col_one_category = $content_by_category['col_one'];
        return $this->pages_in_col_one_category;
    }
    
    public function pages_not_in_col_one_category()
    {
        if(isset($this->pages_not_in_col_one_category)) return $this->pages_not_in_col_one_category;
        $this->pages_not_in_col_one_category = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_not_in_col_one_category = $content_by_category['noncol_one'];
        return $this->pages_not_in_col_one_category;
    }
    
    public function pages_in_col_more_categories()
    {
        if(isset($this->pages_in_col_more_categories)) return $this->pages_in_col_more_categories;
        $this->pages_in_col_more_categories = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_in_col_more_categories = $content_by_category['noncol_one'];
        return $this->pages_in_col_more_categories;
    }
    
    public function pages_not_in_col_more_categories()
    {
        if(isset($this->pages_not_in_col_more_categories)) return $this->pages_not_in_col_more_categories;
        $this->pages_not_in_col_more_categories = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_not_in_col_more_categories = $content_by_category['noncol_one'];
        return $this->pages_not_in_col_more_categories;
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////   BHL
    ////////////////////////////////////
    
    public function pages_with_bhl()
    {
        if(isset($this->pages_with_bhl)) return $this->pages_with_bhl;
        $this->pages_with_bhl = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(tc.id)) count FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN page_names pn ON (tcn.name_id=pn.name_id) WHERE tc.published=1 AND tc.supercedure_id=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_bhl = $row['count'];
        return $this->pages_with_bhl;
    }
    
    public function pages_with_bhl_no_text()
    {
        if(isset($this->pages_with_bhl_no_text)) return $this->pages_with_bhl_no_text;
        $this->pages_with_bhl_no_text = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(tc.id)) count FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN page_names pn ON (tcn.name_id=pn.name_id) JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_bhl_no_text = $row['count'];
        return $this->pages_with_bhl_no_text;
    }
    
    
    
    ////////////////////////////////////
    ////////////////////////////////////   Curators
    ////////////////////////////////////
    
    public function pages_awaiting_publishing()
    {
        if(isset($this->pages_awaiting_publishing)) return $this->pages_awaiting_publishing;
        $this->pages_awaiting_publishing = 0;
        
        $events_to_publish = array();
        $result = $this->mysqli->query("SELECT he.resource_id, max(he.id) max FROM harvest_events he GROUP BY he.resource_id");
        while($result && $row=$result->fetch_assoc())
        {
            $harvest_event = new HarvestEvent($row['max']);
            if(!$harvest_event->published_at) $events_to_publish[] = $harvest_event->id;
        }
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(tc.id)) count FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) WHERE het.harvest_event_id IN (".implode($events_to_publish, ",").") AND tc.published=0 AND tc.vetted_id=". Vetted::find("trusted"));
        if($result && $row=$result->fetch_assoc()) $this->pages_awaiting_publishing = $row['count'];
        return $this->pages_awaiting_publishing;
    }
    
    public function col_content_needs_curation()
    {
        if(isset($this->col_content_needs_curation)) return $this->col_content_needs_curation;
        $this->col_content_needs_curation = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM hierarchy_entries he JOIN hierarchy_entries he_concepts ON (he.taxon_concept_id=he_concepts.taxon_concept_id) JOIN taxa t ON (he_concepts.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE he.hierarchy_id=".Hierarchy::col_2009()." AND do.published=1 AND do.vetted_id=".Vetted::find('Unknown'));
        if($result && $row=$result->fetch_assoc()) $this->col_content_needs_curation = $row['count'];
        return $this->col_content_needs_curation;
    }
    
    public function non_col_content_needs_curation()
    {
        if(isset($this->non_col_content_needs_curation)) return $this->non_col_content_needs_curation;
        $this->non_col_content_needs_curation = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM hierarchy_entries he JOIN taxa t ON (he.id=t.hierarchy_entry_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE do.published AND do.vetted_id=".Vetted::find('Unknown'));
        if($result && $row=$result->fetch_assoc()) $this->non_col_content_needs_curation = $row['count'] - $this->col_content_needs_curation();
        return $this->non_col_content_needs_curation;
        
    }
    
    
    
    ////////////////////////////////////
    ////////////////////////////////////   LifeDesk
    ////////////////////////////////////
    
    public function lifedesk_taxa()
    {
        if(isset($this->lifedesk_taxa)) return $this->lifedesk_taxa;
        $this->lifedesk_taxa = 0;
        
        $latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources();
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) WHERE het.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").")");
        if($result && $row=$result->fetch_assoc()) $this->lifedesk_taxa = $row['count'];
        return $this->lifedesk_taxa;
    }
    
    public function lifedesk_data_objects()
    {
        if(isset($this->lifedesk_data_objects)) return $this->lifedesk_data_objects;
        $this->lifedesk_data_objects = 0;
        
        $latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources();
        $result = $this->mysqli->query("SELECT COUNT(DISTINCT(do.id)) count FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").") AND do.published=1");
        if($result && $row=$result->fetch_assoc()) $this->lifedesk_data_objects = $row['count'];
        return $this->lifedesk_data_objects;
    }
    
    
    public function latest_published_lifedesk_resources()
    {
        $resource_ids = array();
        $result = $this->mysqli->query("SELECT r.id, max(he.id) max FROM resources r JOIN harvest_events he ON (r.id=he.resource_id) WHERE r.accesspoint_url LIKE '%lifedesks.org%' GROUP BY r.id");
        while($result && $row=$result->fetch_assoc())
        {
            $resource_ids[] = $row['max'];
        }
        return $resource_ids;
    }
    
    
    ////////////////////////////////////
    //////////////////////////////////// Methods
    ////////////////////////////////////
    
    public function content_by_category()
    {
        if(isset($this->content_by_category)) return $this->content_by_category;
        $this->content_by_category = array();
        
        $image_type_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
        $text_type_id = DataType::find("http://purl.org/dc/dcmitype/Text");
        
        $taxon_concept_ids = array();
        echo "counting\n";
        $result = $this->mysqli->query("SELECT tc.id, he_col.id in_col FROM taxon_concepts tc JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) LEFT JOIN hierarchy_entries he_col on (tc.id=he_col.taxon_concept_id and he_col.hierarchy_id=".Hierarchy::col_2009().") WHERE tc.supercedure_id=0 AND tc.published=1 AND (tcc.text=1 OR tcc.image=1)");
        while($result && $row=$result->fetch_assoc())
        {
            $taxon_concept_ids[$row['id']] = $row['in_col'];
        }
        echo "done counting\n";
        
        $batches = array_chunk(array_keys($taxon_concept_ids), 10000);
        
        $content_count = array();
        $content_count[0] = array();
        $content_count[1] = array();
        foreach($batches as $key => $batch)
        {
            echo "starting batch ".($key+1)." of ".count($batches)."\n";
            $last_concept_id = 0;
            $result = $this->mysqli->query("select he.taxon_concept_id id, do.data_type_id, toc.id toc_id
                from hierarchy_entries he
                join taxa t on (he.id=t.hierarchy_entry_id)
                join data_objects_taxa dot on (t.id=dot.taxon_id)
                join data_objects do on (dot.data_object_id=do.id)
                left join (
                    data_objects_table_of_contents dotoc
                    join table_of_contents toc on (dotoc.toc_id=toc.id)
                ) on (do.id=dotoc.data_object_id) 
                where he.taxon_concept_id IN (".implode($batch,",").") AND do.published=1 and do.vetted_id=".Vetted::insert("Trusted"));
            while($result && $row=$result->fetch_assoc())
            {
                $id = $row["id"];
                
                if($id != $last_concept_id)
                {
                    if($last_concept_id)
                    {
                        $count_text = count($distinct_toc_ids);
                        $count_other = count($distinct_data_type_ids);
                        $content_count[$in_col][$id] = $count_text + $count_other;
                    }
                    
                    $in_col = @$taxon_concept_ids[$id];
                    $distinct_data_type_ids = array();
                    $distinct_toc_ids = array();
                }
                
                $data_type_id = $row["data_type_id"];
                $toc_id = $row["toc_id"];
                if($row["data_type_id"] == $text_type_id) $distinct_toc_ids[$toc_id] = 1;
                elseif($row["data_type_id"] == $image_type_id) $distinct_data_type_ids[$data_type_id] = 1;
            }
        }
        
        $col_one = 0;
        $col_more = 0;
        foreach($content_count[0] as $id => $count)
        {
            if($count==1) $col_one++;
            elseif($count>1) $col_more++;
        }
        
        $noncol_one = 0;
        $noncol_more = 0;
        foreach($content_count[1] as $id => $count)
        {
            if($count==1) $noncol_one++;
            else $noncol_more++;
        }
        
        $this->content_by_category = array('col_one' => $col_one, 'col_more' => $col_more, 'noncol_one' => $noncol_one, 'noncol_more' => $noncol_more);
        return $this->content_by_category;
    }
    
    
}

?>