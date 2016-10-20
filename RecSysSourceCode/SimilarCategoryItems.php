<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SimilarCategoryItems
 *
 * @author peska
 */
class SimilarCategoryItems {
    //put your code here
     private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
     
    public function __construct($typ) {
    
        echo Date("H:i:s")." starting SimilarCategoryItems\n<br/>";
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());                
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_connection=UTF8");
        mysql_query("SET character_set_client=UTF8");
        
        mysql_query("TRUNCATE category_similarity");
        if($typ=="antikvariat"){
            $sql = "select * from train_set "
                . "join new_implicit_events on (train_set.visitID = new_implicit_events.visitID)"
                . "join objects_table on (train_set.objectID = objects_table.oid)"
                . " where timeOnPage > 500 order by train_set.userID ";
        }else{
            $sql = "select distinct extended_user_behavior.SID,extended_user_behavior.OID, extended_user_behavior.UID, `TourTypeID`, `CountryList`, `DestinationList`  from extended_user_behavior                 
                join content_based_tour_attributes on (extended_user_behavior.OID = content_based_tour_attributes.TourSeriesID)
                 where 1 order by extended_user_behavior.UID ";
        }


        $query = mysql_query($sql);
        $visitsCount = array();
        $pairedVisitCount = array();

        while ($row = mysql_fetch_array($query)) {
            $cat = $row["TourTypeID"].":".$row["CountryList"].":".$row["DestinationList"];
            if(isset($user_visited[$cat])){
                $user_visited[$row["UID"]][$cat]++;
            }else{
                $user_visited[$row["UID"]][$cat] = 1;
            }                                    
        }
              
        foreach ($user_visited as $uid => $cats) {
            $catNames = array_keys($cats);
            for ($i=0; $i<sizeof($catNames);$i++) {
                $cat1=$catNames[$i];
                $cat1Val = $cats[$cat1];
                
                //echo $cat1.": ".$cat1Val."<br/>";
                
                //zaznamename navstevu kategorie
                if(isset($visitsCount[$cat1])){
                    $visitsCount[$cat1] += $cat1Val;
                }else{
                    $visitsCount[$cat1] = $cat1Val;
                }
                if($i<sizeof($catNames)-1){
                    for ($j=$i+1;$j<sizeof($catNames);$j++) {
                        $cat2=$catNames[$j];
                        $cat2Val = $cats[$cat2];                
                        //pro kazdy navstiveny par                
                        if(isset($pairedVisitCount[$cat1][$cat2])){
                            $pairedVisitCount[$cat1][$cat2] += min(array($cat1Val,$cat2Val) );
                            $pairedVisitCount[$cat2][$cat1] = $pairedVisitCount[$cat1][$cat2];
                        }else{
                            $pairedVisitCount[$cat1][$cat2] = min(array($cat1Val,$cat2Val) );
                            $pairedVisitCount[$cat2][$cat1] = $pairedVisitCount[$cat1][$cat2];
                        }
                    }
                    //echo $cat2.": ".$cat2Val."<br/>";
                }
            }
            echo "done user $uid \n<br/>";

        }
        //print_r($pairedVisitCount);
        //print_r($visitsCount);
        $sql = "INSERT INTO `category_similarity`(`cat_id1`, `cat_id2`, `similarity`) VALUES\n";
        $first = 1;
        foreach ($pairedVisitCount as $catID1 => $array) {
            foreach ($array as $catID2 => $prunik) {
                if($prunik>4){
                    $similarity = $prunik / ($visitsCount[$catID1] + $visitsCount[$catID2] - $prunik);
                    if($first){
                        $first = 0;
                    }else{
                        $sql .= ",";
                    }
                    if($typ=="antikvariat"){
                        $sql .= "($catID1,$catID2,$similarity)\n";
                    }else{
                        $sql .= "(\"$catID1\",\"$catID2\",$similarity)\n";
                    }
                }
                                    
            }            
        }
        
        echo nl2br($sql);
        mysql_query($sql);
        echo Date("H:i:s")." finished SimilarCategoryItems\n<br/>";
    }

}
new SimilarCategoryItems("slantour");