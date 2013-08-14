<?php
namespace php_active_record;

class WikimediaHarvester
{
    private $mysqli;
    private $taxa_pages;
    private $pageids_to_update;
    private $pageids_to_ignore;
    private $resource_file;
    private $resource;

    function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->resource = Resource::find(71);
        $this->base_directory_path = DOC_ROOT . "update_resources/connectors/files/";
        $this->part_file_base = $this->base_directory_path . "wikimedia/part_";
        // allows for splitting the file into max 26^n parts, e.g. n=3, parts=300Mb, copes with xml files < 4.2Tb
        $this->part_file_suffix_chars = 3;
        $this->taxa = array();
        $this->taxonav_includes = array();
        $this->taxonomic_categories = array();
        $this->taxonomic_galleries  = array();
        $this->gallery_files = array();
        $this->map_categories = $this->get_map_categories($this->base_directory_path);
        $this->n_media_files = 0;
        $this->n_pages = 0;
        $this->processed_files = 0;
        $this->batch = array();

        $this->resource_file_path = CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id . "_temp.xml";
        $this->resource_file = new \SchemaDocument($this->resource_file_path);
    }

    public function begin_wikipedia_harvest()
    {
        // // delete the downloaded files
        $this->cleanup_dump();
        $this->download_dump();

        // FIRST PASS: go through all files to grab TaxonavigationIncluded files, e.g. https://commons.wikimedia.org/wiki/Template:Aves
        $this->iterate_files(array($this, 'get_taxonav_includes'));
        $this->taxonav_includes = unserialize(file_get_contents(DOC_ROOT . 'vendor/wikipedia/taxonavs.txt'));

        // INTERMEDIATE PASS: grab taxon information and determine scientific images
        $this->iterate_files(array($this, 'get_taxonomic_pages'));

        $galleries_with_files = count(array_unique($this->gallery_files));
        echo "\n\n# total galleries:  ". count($this->taxonomic_galleries) .", with ". count($this->gallery_files) ." media files.";
        echo " $galleries_with_files galleries (". @($galleries_with_files / count($this->taxonomic_galleries) * 100) ."%) actually have files\n";
        echo "\n\n# total categories: ". count($this->taxonomic_categories) ."\n";

        // FINAL PASS: grab file information for scientific media pages and save to file
        $this->iterate_files(array($this, 'get_media_pages'));

        $last_number = $this->batch_process();

        echo "\n\n (last $last_number media files processed)\n";
        echo "\n\n# media files: $n_media_files (in ". count($this->taxa) ." taxa)\n";

        $this->check_remaining_gallery_files();

        echo "End\n";
        self::print_memory_and_time();
    }

    private function download_dump()
    {
        // download latest Wikimedia Commons export
        shell_exec("curl ".$this->resource->accesspoint_url." -o ". $this->base_directory_path . "wikimedia.xml.bz2");
        // unzip the download
        shell_exec("bunzip2 ". $this->base_directory_path . "wikimedia.xml.bz2");
        // split the huge file into 300M chunks
        shell_exec("split -a ". $this->part_file_suffix_chars ." -b 300m ". $this->base_directory_path . "wikimedia.xml ". $this->part_file_base);
    }

    private function cleanup_dump()
    {
        // cleaning up downloaded files
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia/*");
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia.xml");
        shell_exec("rm -f ". $this->base_directory_path . "wikimedia.xml.bz2");
    }

    private function iterate_files($callback)
    {
        $left_overs = "";
        $suffix = str_repeat('a', $this->part_file_suffix_chars);
        $suffix = 'ada';
        while((strlen($suffix) == $this->part_file_suffix_chars) && $this->process_file($this->part_file_base . $suffix, $callback, $left_overs))
        {
            // auto-increment allows us to match the output of the 'split' command: aaa->aab, aaz->aba, etc
            $suffix++;
        }
        if(!preg_match('/\s*<\/mediawiki>\s*/smi', $left_overs))
        {
            echo "WARNING: THE LAST WIKI FILE APPEARS TO BE TRUNCATED. Part of the wiki download may be missing.\n";
        }
    }

    private function process_file($filename, $callback, &$left_overs = "")
    {
        if(!file_exists($filename))
        {
            echo "Assuming no more part files to process (as ". basename($filename) ." doesn't exist)\n";
            return false;
        }
        echo "Processing file ". basename($filename) ." with callback ". $callback[1] ."\n";
        self::print_memory_and_time();

        $current_page = $left_overs;
        foreach(new FileIterator($filename) as $line)
        {
            $current_page .= $line;
            if(trim($line) == "<page>")
            {
                $current_page = $line;
            }
            if(trim($line) == "</page>")
            {
                call_user_func($callback, $current_page);
                $current_page = "";
            }
        }
        $left_overs = $current_page;
        return true;
    }

    private function get_taxonav_includes($xml)
    {
        if(\WikimediaPage::fast_is_template($xml))
        {
            // make sure we don't include cases with {{Template in the comments field, etc.
            if($text_start = strpos($xml, "<text"))
            {
                // also catches TaxonavigationIncluded etc.
                if(preg_match("/\{\{Taxonavigation/", $xml, $arr, 0, $text_start))
                {
                    $page = new \WikimediaPage($xml);
                    if($page->contains_template("TaxonavigationIncluded[\w\s]*"))
                    {
                        $include_array = $page->taxonav_as_array("[Tt]axonavigationIncluded[\w\s]*");
                        if(count($include_array))
                        {
                            $this->taxonav_includes[$page->title] = $include_array;
                        }else
                        {
                            echo "$page->title is not a real TaxonavigationInclude* template\n";
                        }
                    }
                }
            }
        }
        static $pages = 0;
        $pages++;
        if($pages % 100000 == 0)
        {
            echo "Page: $pages (preliminary pass). # TaxonavigationIncluded files so far: ". count($this->taxonav_includes) ."\n";
            self::print_memory_and_time();
            echo serialize($this->taxonav_includes)."\n";
        }
    }

    private function get_taxonomic_pages($xml)
    {
        if(\WikimediaPage::fast_is_gallery_category_or_template($xml))
        {
            if($text_start = strpos($xml, "<text")) //make sure we don't include cases with {{Taxonavigation in the comments field, etc.
            {
                if(preg_match("/\{\{Taxonavigation/", $xml, $arr, 0, $text_start))
                {
                    $page = new \WikimediaPage($xml);
                    if($page->is_template())
                    {
                        if($page->contains_template("Taxonavigation"))
                        {
                            //This is a template that itself contains the template "Taxonavigation", so we might be interested in it
                            if(preg_match("/^Template:Taxonavigation\//", $page->title))
                            {
                                //we don't need to worry: it's something like Template::Taxonavigation/doc,
                            }else
                            {
                                echo "The template '$page->title' transcludes {{Taxonavigation}}: we might want to consider looking for taxonomic pages containing this template too.\n";
                            }
                        }
                    }else
                    {
                        if($params = $page->taxon_parameters($this->taxonav_includes)) //pass in "taxonav_includes" to avoid lots of API calls
                        {
                            if(@$params['scientificName'])
                            {
                                $this->taxa[$page->title] = $params;

                                if($page->is_category())
                                {
                                    $this->taxonomic_categories[$page->title] = $page->taxonomy_score();
                                }elseif($page->is_gallery())
                                {
                                    $this->taxonomic_galleries[$page->title] = $page->taxonomy_score();
                                    foreach($page->media_on_page() as $file)
                                    {
                                        $this->gallery_files["File:".$file] = $page->title;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->n_pages++;
        if($this->n_pages % 100000 == 0)
        {
            echo "Page: $this->n_pages (first pass). # taxa so far: ". count($this->taxa) ."\n";
            self::print_memory_and_time();
        }
    }

    private function get_media_pages($xml)
    {
        $wanted = false;
        if(\WikimediaPage::fast_is_media($xml))
        {
            $page = new \WikimediaPage($xml);
            // check if this page has been listed in a gallery
            if(isset($this->gallery_files[$page->title]))
            {
                if(isset($page->redirect))
                {
                    // we won't catch redirects to pages earlier in the XML dump. Let's just hope those
                    // have been picked up when scanning for categories. We'll check this later
                    echo "Page '$page->title' listed in gallery ". $this->gallery_files[$page->title] ." has been redirected. Now looking for '$page->redirect' instead.\n";
                    $this->gallery_files[$page->redirect] = $this->gallery_files[$page->title];
                }else
                {
                    $page->set_gallery($this->gallery_files[$page->title]);
                    $wanted = true;
                }
                // remove the links to the gallery files as we go
                unset($this->gallery_files[$page->title]);
            }

            // check if the page has an associated "taxonomic category"
            // done on every file in the dump: be careful not to trigger off a remote API call
            foreach($page->get_categories() as $cat)
            {
                $cat = "Category:$cat";
                if(isset($this->taxonomic_categories[$cat]))
                {
                    // just flag this as wanted. We'll search for proper categories later
                    $wanted = true;
                    break;
                }
            }
            if($wanted) $this->processed_files += $this->batch_process($page);
        }

        static $p = 0;
        $p++;
        if($p % 100000 == 0)
        {
            echo "Page: $p (final pass, ". round($p/$this->n_pages*100, 1) ."% done). # media files so far: $this->n_media_files ($this->processed_files completed via MediaWiki API query)\n";
            self::print_memory_and_time();
        }
    }

    private function batch_process($page = null)
    {
        // if page is null, just process any remaining in the batch
        $batch_volume = \WikimediaPage::$max_titles_per_lookup;
        if($page)
        {
            $this->n_media_files++;
            // we could potentially only check files with recently updated timestamps here?
            // but we would also need to catch unchanged files whose taxonomic classification has changed
            $this->batch[] = $page;
            // wait until we have enough in a batch.
            if(count($this->batch) < $batch_volume) return 0;
        }

        // either there are enough pages in the batch to process, or $page==null, triggering us to process the remaining pages in the batch
        \WikimediaPage::process_pages_using_API($this->batch);
        foreach($this->batch as $page)
        {
            // page may have multiple taxonomies: e.g. from gallery "Mus musculus", category "Mus musculus", category "Mus", etc.
            // pick the one with the highest "taxonomy score"
            if(is_null($gallery = $page->get_gallery()))
            {
                $best_taxonomy = null;
                $best_taxonomy_score = -1;
            }else
            {
                $best_taxonomy = $gallery;
                $best_taxonomy_score = $this->taxonomic_galleries[$best_taxonomy];
            }

            // only look for categories gleaned from the API (more reliable)
            $categories_from_API = $page->get_categories(true);
            if(count($categories_from_API) == 0)
            {
                echo "ERROR. This shouldn't happen. No categories for $page->title (have you failed to connect to the Wikimedia API?)\n";
            }else
            {
                $potential_license_categories = "";
                $map = false;
                foreach($categories_from_API as $cat)
                {
                    if(isset($this->taxonomic_categories["Category:$cat"]))
                    {
                        $fullcat = "Category:$cat";
                        $diff = $best_taxonomy_score - $this->taxonomic_categories[$fullcat];
                        if($diff < 0)
                        {
                            if(($diff < -0.5) && isset($best_taxonomy))
                            {
                                echo "Got a substantially better taxonomy for $page->title : $best_taxonomy (score $best_taxonomy_score)";
                                echo " replaced with $fullcat (score ". $this->taxonomic_categories[$fullcat] .")\n";
                            }
                            $best_taxonomy = $fullcat;
                            $best_taxonomy_score = $this->taxonomic_categories[$fullcat];
                        }
                    }elseif(isset($this->map_categories[$cat]))
                    {
                        $map = true;
                    }else $potential_license_categories .= $cat."\n";
                }

                if($map) $page->set_additionalInformation("<subtype>Map</subtype>");
                if($license = \WikimediaPage::match_license($potential_license_categories, true))
                {
                    $page->set_license($license);
                }
            }
            if(!$page->has_license())
            {
                echo "No valid license category for $page->title (Categories: ".implode("|", $categories_from_API) .")\n";
            }
            if(empty($best_taxonomy))
            {
                echo "That's odd: no valid taxonomy for $page->title . Perhaps the categories via the API have changed since the XML dump (dump: ". implode("|", $page->categories_from_wikitext) .", API: ". implode("|", $categories_from_API) .")\n";
            }else
            {
                $taxon_data = $this->taxa[$best_taxonomy];
                $data_object_parameters = $page->get_data_object_parameters();
                $this->add_to_resource_file($taxon_data, $data_object_parameters);
            }
        }

        $batch_size = count($batch);
        $this->batch = array();
        return $batch_size;
    }

    private function check_remaining_gallery_files()
    {
        if(count($this->gallery_files))
        {
            $good_files = array();
            echo count($this->gallery_files) ." gallery files remaining at end. Checking them out:";
            $titles = array_chunk(array_keys($this->gallery_files), \WikimediaPage::$max_titles_per_lookup, true);
            foreach($titles as $batch)
            {
                $good_files += \WikimediaPage::check_page_titles($batch);
            }
            if(count($good_files))
            {
                echo "\n\nMISSED THE FOLLOWING ". count($good_files) ." FILES";
                echo " (if you have the scanned whole XML dump, these may be pages whose title has changed and have not been placed in a valid taxonomic category)\n";
                foreach($good_files as $title => $json)
                {
                    echo "* $title in gallery <". $this->gallery_files[$title] .">\n";
                }
            }
        }
    }

    private function get_map_categories($base_directory_path, $contact_sites=true)
    {
        // Try to get latest list of map categories. It's hard to use the MediaWiki API to recursively descend categories
        // but there are 2 online tools which can do it. Try both of these, and if it fails, just use a previously saved version
        // (using an old version should be no problem, as we don't expect many changes to this category structure)

        $base_category= "Distributional maps of organisms";
        $sites = array( "toolserver" => "http://toolserver.org/~daniel/WikiSense/CategoryIntersect.php?wikifam=commons.wikimedia.org&basedeep=100&mode=cl&go=Scan&format=csv&userlang=en&basecat=",
                        "wmflabs" => "http://tools.wmflabs.org/catscan2/quick_intersection.php?lang=commons&project=wikimedia&ns=14&depth=-1&max=30000&start=0&format=json&sparse=1&cats=");

        $mapcats = array($base_category => 1);
        if(count($mapcats) <= 1 && $contact_sites)
        {
            $url = $sites["toolserver"].urlencode($base_category);
            $tab_separated_string = Functions::get_remote_file_fake_browser($url, array('download_wait_time' => DOWNLOAD_WAIT_TIME*10, 'timeout' => DOWNLOAD_TIMEOUT_SECONDS*10));
            if(isset($tab_separated_string) && !preg_match("/^[^\r\n]*Database Error/i",$tab_separated_string))
            {
                foreach(preg_split("/(\r?\n)|(\n?\r)/", $tab_separated_string, null, PREG_SPLIT_NO_EMPTY) as $line)
                {
                    //  Category name is after first tab
                    $name = preg_replace("/_/u", " ", preg_replace("/^[^\t]*\t([^\t]*).*$/u", "$1", $line));
                    $mapcats[$name] = 1;
                }
                echo "Got map categories from toolserver ($url)\n";
            }else echo "Couldn't get map categories from toolserver ($url)\n";
        }

        if(count($mapcats) <= 1 && $contact_sites)
        {
            $url = $sites["wmflabs"].urlencode($base_category);
            $json = @json_decode(Functions::get_remote_file($url));
            if(isset($json) && isset($json->pages))
            {
                foreach($json->pages as $mapcat)
                {
                    $name = preg_replace("/_/u", " ", preg_replace("/^Category:/u", "", $mapcat));
                    $mapcats[$name] = 1;
                }
                echo "Got map categories from wmflabs ($url)\n";
            }else echo "Couldn't get map categories from wmflabs ($url)\n";
        }

        if(count($mapcats) > 1)
        {
            // overwrite previous
            @rename($base_directory_path."MapCategories.txt", $base_directory_path."MapCategories_previous.txt");
            file_put_contents($base_directory_path."MapCategories.txt", implode("\n",array_keys($mapcats)));
            return $mapcats;
        }else
        {
            echo "Didn't download new list of map categories: using old version.\n";
            $mapcats = file($base_directory_path."MapCategories.txt", FILE_IGNORE_NEW_LINES);
            return(array_fill_keys($mapcats, 1));
        }
    }

    private function add_to_resource_file($taxon_data, $data_object_parameters)
    {
        if(isset($data_object_parameters['mediaURL']))
        {
            $taxon_data['dataObjects'][] = new \SchemaDataObject($data_object_parameters);
            $taxon = new \SchemaTaxon($taxon_data);
            $this->resource_file->save_taxon_xml($taxon);
        }
    }

    public static function print_memory_and_time()
    {
        echo "Memory: ". memory_get_usage_in_mb() ." MB\n";
        echo "Time: : ". round(time_elapsed(), 2) ." s\n\n\n";
    }
}

?>
