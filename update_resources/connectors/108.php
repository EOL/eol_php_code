<?php
/* connector for USDA text descriptions compiled by Gerald "Stinger" Guala, Ph.D. using the SLIKS software.
This connector reads 4 HTML files
estimated execution time: 53 secs to 1 min. -> 
*/
$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n"; 
//$wrap = "<br>"; 
 
$resource = new Resource(108); //USDA Plant text descriptions
//exit($resource->id);

$schema_taxa = array();
$used_taxa = array();

//$file = "";
$path="files/USDA_text_descriptions/";
$urls = array( 0 => array( "url" => $path . "legumesEOL.htm"  , "active" => 1),   //
               1 => array( "url" => $path . "GrassEOL.htm"    , "active" => 1),   //
               2 => array( "url" => $path . "gymnosperms.htm" , "active" => 1)
             );
$do_count=0;
$i=0;
foreach($urls as $path)
{    
    if($path["active"])
    {        
        print $i+1 . ". " . $path["url"] . "$wrap";        
        if    ($i <= 1) process_file1($path["url"],$i); //legumesEOL, GrassEOL
        elseif($i == 2) process_file2($path["url"],$i); //gymnosperms
        
    }
    $i++;
}    

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---
$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

exit("\n\n Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################

function process_file2($file,$doc_id)
{        
    /* the gymnosperms.htm is not as structured as the other 2 docs.
       a minor manual edit on the doc was needed. 
    */
    global $wrap;
    global $used_taxa;

    
    print "$wrap";    
    $str = Functions::get_remote_file($file);    

    $str = str_ireplace(chr(10) , "<br>", $str);	
    $str = str_ireplace(chr(13) , "", $str);	    

    $str = str_ireplace('<br><br>' , "&arr[]=", $str);	
    //print "<hr>$str"; exit;        
        
    $str=trim($str);
    $str=substr($str,0,strlen($str)-7);   //to remove last part of string "&arr[]="
    //print "<hr>$str"; exit;

    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);
    
    //print"<pre>";print_r($arr);print"</pre>";
    //exit;
    
    $i=0;
    foreach($arr as $str)
    {
        $str = clean_str($str);
        
        //if($i >= 5)break; //debug        //ditox
        
        $i++;
        // if(in_array($i,array(8))){
        if(true)
        {
            //<b><i>Abrus precatorius</i></b>

            //get sciname
            $str = "xxx" . $str;
            $beg='xxx'; $end1='<br>';
            $sciname = strip_tags(trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"")));            

            //get desc
            $str .= "yyy";
            $beg='<br>'; $end1='yyy'; 
            $desc = strip_tags(trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"")));                        
            $last_char_of_desc = substr($desc,strlen($desc)-1,1);
            if($last_char_of_desc == ",")$desc = substr($desc,0,strlen($desc)-1);            
            $desc .= ".";
            
            if($sciname == "")print "jjj";            
            print "$i. $sciname $wrap";
            //print "$desc";                      

            prepare_agent_rights($doc_id,$sciname,$desc);
                                    
        }        
    }//main loop
    
    //exit;    
        
}//end function process_file2($file)

function process_file1($file,$doc_id)
{        
    global $wrap;
    global $used_taxa;
    
    print "$wrap";    
    $str = Functions::get_remote_file($file);    

    $str = str_ireplace('<br><br>' , "&arr[]=", $str);	
    
    $str=trim($str);
    $str=substr($str,0,strlen($str)-7);   //to remove last part of string "&arr[]="

    //print "<hr>$str"; exit;

    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	//print_r($arr);
    
    //print"<pre>";print_r($arr);print"</pre>";
    
    $i=0;
    foreach($arr as $str)
    {
        $str = clean_str($str);
        $str = str_ireplace("< /i>","</i>",$str);
        
        //if($i >= 5)break; //debug        //ditox
        
        $i++;
        // if(in_array($i,array(8))){
        if(true)
        {
            //<b><i>Abrus precatorius</i></b>

            //get sciname
            $beg='<b>'; $end1='</i></b>';$end2='</i>';$end3='</b>'; 
            $sciname = strip_tags(trim(parse_html($str,$beg,$end1,$end2,$end3,$end1,"")));            

            //get desc
            $str .= "xxx";
            $beg='</i></b>'; $end1='xxx'; 
            $desc = strip_tags(trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"")));                        
            $last_char_of_desc = substr($desc,strlen($desc)-1,1);
            if($last_char_of_desc == ",")$desc = substr($desc,0,strlen($desc)-1);
            $desc .= ".";
                        
            if($sciname == "")print "jjj";
            print "$i. $sciname $wrap";
            //print "$desc";                      
            
            
            prepare_agent_rights($doc_id,$sciname,$desc);
                                    
        }        
    }//main loop
    
    //exit;    
        
}//end function process_file1($file)

function prepare_agent_rights($doc_id,$sciname,$desc)
{
    global $do_count;
    
    $arr_agents=array();
    if($doc_id == 0 or $doc_id == 1)//Grasses & Legumes
    {
        $dc_rights = "Compiled from several sources by Dr. David Bogler, Missouri Botanical Garden in collaboration with the USDA NRCS NPDC";
        $arr_agents[]=array("name"=>"Dr. David Bogler",          "role"=>"compiler" ,"homepage"=>"");
        $arr_agents[]=array("name"=>"Missouri Botanical Garden", "role"=>"source"   ,"homepage"=>"http://www.mobot.org");
        $arr_agents[]=array("name"=>"USDA NRCS NPDC",            "role"=>"source"   ,"homepage"=>"http://www.nrcs.usda.gov");
    }
    elseif($doc_id == 2)//Gymnosperms
    {
        $dc_rights = "Compiled from several sources by Stephen C. Meyers, Oregon State University in collaboration with Aaron Liston, Oregon State University, Steffi Ickert-Bond, University of Alaska Fairbanks, and Damon Little, New York Botanical Garden.";                
        $arr_agents[]=array("name"=>"Stephen C. Meyers",    "role"=>"compiler","homepage"=>"");
        $arr_agents[]=array("name"=>"Aaron Liston",         "role"=>"compiler","homepage"=>"");
        $arr_agents[]=array("name"=>"Steffi Ickert-Bond",   "role"=>"compiler","homepage"=>"");
        $arr_agents[]=array("name"=>"Damon Little",         "role"=>"compiler","homepage"=>"");
    }
    
    $dc_source = "http://npdc.usda.gov/technical/plantid_wetland_mono.html";
    
    $do_count++;           
    assign_variables($sciname,$desc,$arr_agents,$dc_rights,$dc_source,$do_count);                            
}


function assign_variables($sciname,$desc,$arr_agents,$dc_rights,$dc_source,$do_count)
{
    global $used_taxa;
    global $wrap;
    
        $kingdom="Animalia";
    
        $genus = substr($sciname,0,stripos($sciname," "));
        $taxon_identifier = "eomlfbi_" . $do_count;
        if(@$used_taxa[$taxon_identifier])
        {
            $taxon_parameters = $used_taxa[$taxon_identifier];
        }
        else
        {
            $taxon_parameters = array();
            $taxon_parameters["identifier"] = $taxon_identifier;
            $taxon_parameters["kingdom"] = $kingdom;
            $taxon_parameters["genus"] = $genus;
            $taxon_parameters["scientificName"]= $sciname;        
            $taxon_parameters["source"] = $dc_source;                    

            /*
            $taxon_parameters["commonNames"] = array();
            $arr_comname=conv_2array($comname);
            foreach ($arr_comname as $commonname) 
            {
                $commonname = str_ireplace(';' , '', $commonname);
                $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
            }
            */
            /////////////////////////////////////////////////////////////
            /*
            $taxon_params["synonyms"] = array();
            $arr_synonym=conv_2array($synonymy);
            foreach ($arr_synonym as $synonym) 
            {
                $taxon_parameters["synonyms"][] = new SchemaSynonym(array("synonym" => $synonym, "relationship" => "synonym"));
            }
            */
            /////////////////////////////////////////////////////////////
            
            $taxon_parameters["dataObjects"]= array();        
            $used_taxa[$taxon_identifier] = $taxon_parameters;
        }        
        
        //start text dataobject                
        $dc_identifier  = $do_count;    
        $desc           = $desc;
        $title          = "Physical Description";
        $subject        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description";
        $type           = "text";
        $reference      = "";        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $dc_rights, $title, $dc_source, $subject, $type, $reference, $arr_agents);       
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        //end text dataobject                    
        
        //start text dataobject                
        //end text dataobject                    
        
        //start text dataobject                
        //end text dataobject                            

        //start img dataobject                
        //end img dataobject                            
        
        $used_taxa[$taxon_identifier] = $taxon_parameters;                                
        
    return "";        
}

function conv_2array($list)
{    
    $list = str_ireplace('and ', ',', $list);	    
    $arr = explode(",",$list);        
    for ($i = 0; $i < count($arr); $i++) 
    {
        $arr[$i]=trim($arr[$i]);
    }
    //print_r($arr);
    return $arr;
}

function get_data_object($id, $description, $dc_rights, $title, $url, $subject, $type, $reference, $arr_agents, $mediaurl=NULL)
{
     
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["title"] = $title;
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    /*
    else
    {
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["mediaURL"] = $mediaurl;
    }
    */

            /////////////////////////////////////////////////////////////
            
            foreach ($arr_agents as $g)
            {        
                $agentParameters = array();            
                $agentParameters["role"]     = $g["role"];
                $agentParameters["fullName"] = $g["name"];
                $agentParameters["homepage"] = $g["homepage"];
                $agents[] = new SchemaAgent($agentParameters);
            }            
            $dataObjectParameters["agents"] = $agents;    
            /////////////////////////////////////////////////////////////

    ///////////////////////////////////
    ///////////////////////////////////
    
    $dataObjectParameters["description"] = $description;        
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;    
    
    $dataObjectParameters["language"] = "en";        
    $dataObjectParameters["source"] = $url;    

    //$dataObjectParameters["rights"] = "Copyright 2009 IUCN Tortoise and Freshwater Turtle Specialist Group";
	$dataObjectParameters["rights"] = $dc_rights;
	
    $dataObjectParameters["rightsHolder"] = "";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    
    ///////////////////////////////////
    if($reference != "")
    {
        $dataObjectParameters["references"] = array();
        $referenceParameters = array();
        $referenceParameters["fullReference"] = trim($reference);
        $references[] = new SchemaReference($referenceParameters);
        $dataObjectParameters["references"] = $references;
    }    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();        
    $audienceParameters = array();      
    $audienceParameters["label"] = "Expert users";      $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    ///////////////////////////////////
    return $dataObjectParameters;
}

function clean_str($str)
{    
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    //$str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '#', $str);			
    // this line counts how many # as num, and repeats this char in num times, then replaces these chars with just 1 space ' ' 
    //$str = str_replace(str_repeat("#", substr_count($str, '#')), ' ', $str);
    return $str;
}
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)	//str = the html block
{
    //PRINT "[$all]"; exit;
	$beg_len = strlen(trim($beg));
	$end1_len = strlen(trim($end1));
	$end2_len = strlen(trim($end2));
	$end3_len = strlen(trim($end3));	
	$end4_len = strlen(trim($end4));		
	//print "[[$str]]";

	$str = trim($str); 	
	$str = $str . "|||";	
	$len = strlen($str);	
	$arr = array(); $k=0;	
	for ($i = 0; $i < $len; $i++) 
	{
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
		{	
			$i=$i+$beg_len;
			$pos1 = $i;			
			//print substr($str,$i,10) . "<br>";									
			$cont = 'y';
			while($cont == 'y')
			{
				if(	strtolower(substr($str,$i,$end1_len)) == strtolower($end1) or 
					strtolower(substr($str,$i,$end2_len)) == strtolower($end2) or 
					strtolower(substr($str,$i,$end3_len)) == strtolower($end3) or 
					strtolower(substr($str,$i,$end4_len)) == strtolower($end4) or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);																				
					//print "$arr[$k] $wrap";					                    
					$k++;
				}
				$i++;
			}//end while
			$i--;			
            
            //start exit on first occurrence of $beg
            if($exit_on_first_match)break;
            //end exit on first occurrence of $beg
            
		}		
	}//end outer loop
    if($all == "")	
    {
        $id='';
	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
        return $id;
    }
    elseif($all == "all") return $arr;	
}//end function

function remove_tag_with_this_needle($str,$needle)
{
    $pos = stripos($str,$needle); //get pos of needle   
    if($pos != "")
    {        
        $char="";
        $accumulate=""; $start_get=false;
        while ($char != "<") //get pos of < start tag
        {
            $pos--;
            $char = substr($str,$pos,1);
        
            if($char == " ")$start_get = true;
            if($start_get)$accumulate .= $char;                
        }
        //print "pos_of_start_tag [$pos]<br>";
        $pos_of_start_tag = $pos;
    
        //now determine what type of tag it is
        $accumulate = substr($accumulate,0,strlen($accumulate)-1);
        $accumulate = reverse_str($accumulate);
        //print "<hr>$str<hr>$accumulate";               
    
        //now find the pos of the end tag e.g. </div
        $char="";
        $pos = $pos_of_start_tag;
        $end_tag = "</" . $accumulate . ">";
        //print "<br>end tag is " . $end_tag;
        while ($char != $end_tag )
        {   
            $pos++;  
            $char = substr($str,$pos,strlen($end_tag));                
        }    
        //print"<hr>pos of end tag [$pos]<hr>";       
        $pos_of_end_tag = $pos;
        $str = remove_substr_from_this_position($str,$pos_of_start_tag,$pos_of_end_tag,strlen($end_tag));    
        if(stripos($str,$needle) != "")$str = remove_tag_with_this_needle($str,$needle);    
    
    }    
    return trim(clean_str($str));
}
function remove_substr_from_this_position($str,$startpos,$endpos,$len_of_end_tag)
{
    $str1 = substr($str,0,$startpos);
    $str2 = substr($str,$endpos+$len_of_end_tag,strlen($str));
    return $str1 . $str2;
}
function reverse_str($str)
{
    $accumulate="";
    $length = strlen($str)-1;
    for ($i = $length; $i >= 0; $i--) 
    {
        $accumulate .= substr($str,$i,1);
    }    
    return trim($accumulate);
}

?>