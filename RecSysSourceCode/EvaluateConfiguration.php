<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EvaluateConfiguration
 *
 * @author peska
 */
class EvaluateConfiguration {

    private $recommending_alg;
    private $datasetFile;
    private $dataset;
    private $datasetPerUser;
    private $testedExamples;
    
    private $user_profile_vector;
    private $useFeedbackRelevance;    
    private $result_file;
    
     private  $db_server="127.0.0.1"; //connect spider
     private $db_jmeno="root";
     private $db_heslo="";
     private $db_nazev_db="antikvariat";
    //put your code here
    public function __construct($typ, $recommending_alg, $datasetFile, $useFeedbackRelevance=true, $latentFactors=10) {        
        if($typ=="antikvariat"){
            $this->db_nazev_db="antikvariat";
        }else{
            $this->db_nazev_db="slantour";
        }     
        $this->latentFactors = $latentFactors;

        $this->datasetFile = $datasetFile;        
        $this->useFeedbackRelevance = $useFeedbackRelevance;
        $this->recommending_alg = $recommending_alg;
        
        if($this->useFeedbackRelevance){
            $data_name = $this->datasetFile;
        }else{
            $data_name = "BinaryFeedback";
        }
        
        if($this->recommending_alg=="MF" or $this->recommending_alg=="CBMF" or $this->recommending_alg=="MF_IPR"){
            $alg_name = $this->recommending_alg."_".$this->latentFactors;
        }else{
            $alg_name = $this->recommending_alg;
        }
        echo Date("H:i:s")." starting  $typ $alg_name $data_name\n<br/>";
        $this->result_file = fopen("results/$typ-$alg_name-$data_name.csv", "w");
        
        
        @$this->db_spojeni = mysql_connect($this->db_server, $this->db_jmeno, $this->db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$this->db_vysledek = mysql_select_db($this->db_nazev_db, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysql_error());                

        fwrite($this->result_file , "uid;oid;position;totalObjects\n") ;  
        $this->leaveOneOutPurchased();  

    }
         
    public function leaveOneOutPurchased(){
       echo Date("H:i:s")." starting  LeaveOneOut()\n<br/>";  
       $file = fopen("source/$this->datasetFile.csv", 'r');
       while (($line = fgetcsv($file,0,";")) !== FALSE) {
          //$line is an array of the csv elements
          $this->dataset[] = $line;
          $this->datasetPerUser[$line[0]][] = $line;
          if($line[3]==1){
              $this->testedExamples[] = $line;
          }          
          //print_r($line);
       }
       $i=1;
       
       foreach ($this->testedExamples as $testCase) {
           $results = $this->evaluate($testCase);
           $position = $results[0];
           $totalObj = $results[1];
           $uid = $testCase[0];
           $oid = $testCase[1];
           fwrite($this->result_file , "$uid;$oid;$position;$totalObj\n") ;  
           //echo "$position -- ".Date("H:i:s")." testCase $i finished\n<br/>"; 
           $i++;
       }
       
       fclose($file);    
       fclose($this->result_file);
       echo Date("H:i:s")." finishing  LeaveOneOut()\n<br/>";  
    }
    
     
    /*
        - test case je ve formatu array(uid,oid,pred,res)
     *      */
    public function evaluate($testCase){
        switch ($this->recommending_alg) {
            case "VSM":
                $this->trainVSM($testCase);
                $result = $this->predictVSM($testCase);
                break;
            
            case "MF":
                $this->trainMF($testCase);
                $result = $this->predictMF($testCase);
                break;
            
            case "PopularSimCat":
                $this->trainSimCat($testCase);
                $result = $this->predictSimCat($testCase);
                break;              
        }
        
        return $result;
    }    
    
    /*
     * Train VSM model for the user specified in $testCase[0]
     */
    public function trainVSM($testCase){
        //z�sk�m vsechny objekty daneho uzivatele
        $objects = $this->datasetPerUser[$testCase[0]];
        $this->visited_objects = array();
        $this->user_profile_vector = array();
        
        foreach ($objects as $object) {
            if($object[1]!=$testCase[1]){//nedovolim trenovat ze stejnych objektu jako jsou testovane, i kdyz jde o jinou navstevu     
                if($this->useFeedbackRelevance){
                    $relevance = $object[2];
                }else{
                    $relevance = 1;
                }
                
                $this->visited_objects[] = $object[1];
                if(is_array(staticData::$object_features[$object[1]])){
                    foreach (staticData::$object_features[$object[1]] as $feature => $tf_idf) {
                        if(!isset($this->user_profile_vector[$feature])){
                            $this->user_profile_vector[$feature] = $relevance*$tf_idf;
                        }else{
                            $this->user_profile_vector[$feature] += $relevance*$tf_idf;
                        }
                    } 
                }
            }            
        }
        
    }  
   
    /*
     * Recommend based on trained VSM model and output position of the $trainCase in the recommended list
     */    
   public function predictVSM($testCase){
        //$this->user_profile_vector, $this->visited_objects
        $topK = array();
        foreach (staticData::$object_features as $oid => $object_features) {                
            if(!in_array($oid, $this->visited_objects)){ 
                $similarity = staticData::CosineSimilarity($this->user_profile_vector, $object_features);
                $topK[$oid] = $similarity;                
            }     
        }            
        arsort($topK);
        $position = array_search($testCase[1],array_keys($topK));  
        if(is_numeric($position)){
            $position ++;
        }else{
            $position = "NA";
        }
        $total = sizeof($topK);
        return array($position,$total);               
    }  

    /*
     * Train Popularity-based SimCat model for the user specified in $testCase[0]
     */
    public function trainSimCat($testCase){
        //z�sk�m vsechny objekty daneho uzivatele
        $objects = $this->datasetPerUser[$testCase[0]];
        $this->visited_objects = array();
        $this->user_profile_vector = array();
        
        foreach ($objects as $object) {
            if($object[1]!=$testCase[1]){//nedovolim trenovat ze stejnych objektu jako jsou testovane, i kdyz jde o jinou navstevu     
                if($this->useFeedbackRelevance){
                    $relevance = $object[2];
                }else{
                    $relevance = 1;
                }                
                $this->visited_objects[] = $object[1];
                               
                if(is_array(staticData::$object_features[$object[1]])){
                    $category = staticData::getObjectCategory($object[1]);//todo
                    $catSimList = staticData::getSimilarCategories($category);  //todo                  
                    //store current category
                    if(!isset($this->user_profile_vector[$category])){
                        $this->user_profile_vector[$category] = $relevance;
                    }else{
                        $this->user_profile_vector[$category] += $relevance;
                    }
                    //store similar categories
                   // print_r($catSimList);
                    if(is_array($catSimList)){
                        foreach ($catSimList as $simcat => $similarity) {
                            if(!isset($this->user_profile_vector[$simcat])){
                               // print_r($similarity);
                               // echo $relevance." - ".$similarity."<br/>";
                                $this->user_profile_vector[$simcat] = ($relevance*$similarity);
                            }else{
                                $this->user_profile_vector[$simcat] += ($relevance*$similarity);
                            }
                        }
                    }
                }
            }            
        }
                 
    }    
          
    /*
     * Recommend based on trained Popularity SimCat model and output position of the $trainCase in the recommended list
     */    
   public function predictSimCat($testCase){
        //$this->user_profile_vector, $this->visited_objects
        $topK = array();
        foreach (staticData::$object_features as $oid => $object_features) {                
            if(!in_array($oid, $this->visited_objects)){ 
                $category = staticData::getObjectCategory($oid);//todo                
                if(isset($this->user_profile_vector[$category])){
                    $objectScore = $this->user_profile_vector[$category]*staticData::$object_popularity[$oid];
                }else{
                    $objectScore = -1/(staticData::$object_popularity[$oid]+1);
                }                                 
                $topK[$oid] = $objectScore;                
            }     
        }            
        arsort($topK);
        $position = array_search($testCase[1],array_keys($topK));  
        if(is_numeric($position)){
            $position ++;
        }else{
            $position = "NA";
        }
        $total = sizeof($topK);
        return array($position,$total);               
    }          
    
    
/*
     
    public function trainMF(){
        require_once "MatrixFactorization.php";
        $latentFactors = $this->latentFactors;           
        $use_object_predictors = 1; 
     
       //get avg ratings     
       $average_rating = 0;
       $total_users = 1;
       $total_objects = 1;
       $sql_global_avg = "select count(*) as all_rows, count(distinct userID) as uids, count(distinct objectID) as oids  from `train_set` ";
       $result = mysql_query($sql_global_avg);
       while ($row = mysql_fetch_array($result)){
           $average_rating = $row["all_rows"] / ($row["uids"] * $row["oids"]);   
           $total_users = $row["uids"];
           $total_objects = $row["oids"];
       }
       $user_baseline_predictors = array();
       $sql_uid_avg = "select userID, count(distinct objectID) as oids from `train_set` group by userID";
       $result = mysql_query($sql_uid_avg);
       while ($row = mysql_fetch_array($result)){
           $user_baseline_predictors[$row["userID"]] = ($row["oids"]/$total_objects) - $average_rating;    
       }
       $object_baseline_predictors = array();
       $sql_oid_avg = "select objectID, count(distinct userID) as uids from `train_set` group by objectID";
       $result = mysql_query($sql_oid_avg);
       while ($row = mysql_fetch_array($result)){
           $object_baseline_predictors[$row["objectID"]] = ($row["uids"]/$total_users) - $average_rating;    
       }
                   
    $oid_array = array();
    $object_matrix = array();

    $query =  "select distinct oid from `objects_binary_attributes` where 1 "; 
    echo $query;
    $data_bmatrix = mysql_query($query);
    echo mysql_error();
    while ($row = mysql_fetch_array($data_bmatrix)) {
        $i=0;
        $oid = $row["oid"];
        while($i < $latentFactors){
            $object_matrix[$oid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }

    }
    
    $this->getUserObjects();
    $user_matrix = array();
    $trueRatings = array();
    $objectFactors = array();
    foreach($this->user_objects_vector as $uid => $objects){
        $i=0;
        while($i<$latentFactors){
            $user_matrix[$uid][$i] = (mt_rand(0, 10)/1000);           
            $i++;
        }
        foreach ($objects as $oid => $value) {
            if($value>0){
                $key = $uid.",".$oid;
                $trueRatings[$key] = 1;//$value;
            }
        }
     }
  //  print_r($trueRatings);
    echo "<br/><br/><br/><br/>";

    
    $MF = new MatrixFactorization("MF_".$this->latentFactors, $user_matrix, $object_matrix, $trueRatings);     
    $MF->train();  
    
    $this->user_profile_vector = array();
    foreach ($this->user_objects_vector as $uid => $objects) {
        foreach (staticData::$object_features as $oid => $object_features) {
           // echo "object_ $oid _factors";
           // print_r($objectFactors[$oid]);
            
            $base_score = $MF->computeScore($oid, $uid);
           // $score = $base_score + $average_rating + $user_baseline_predictors[$uid] + $object_baseline_predictors[$oid] ;
            $score = $base_score;
            $this->user_top_k[$uid][$oid] = $score;
        }
        arsort($this->user_top_k[$uid]);
        if($uid == "202816"){
        echo "user_ $uid _topk";
        print_r($this->user_top_k);}
        $this->evaluate($uid);
        
        $this->user_top_k[$uid] = "";
    }       
}  


    
    //natrénuje pro u?ivatele preferenci kategorie - v první ?ad? p?ímé, následn? odvozené
    public function trainSimCat(){
        $sql = "select distinct userID from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID "; 
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            //direct categories                
            $categoryList = $this->getUserCategories($row["userID"]);        
            //výpo?et odvozených kategorií
            foreach ($categoryList as $catID => $value) {
                $catSimList = $this->getCategorySimilarity($catID);
                foreach ($catSimList as $catID2 => $similarity) {
                    if(isset($categoryList[$catID2])){
                        $categoryList[$catID2] = $categoryList[$catID2] + ($value*$similarity);
                    }else{
                        $categoryList[$catID2] = ($value*$similarity);
                    }
                }
            }
            if($this->recommending_alg=="Popular"){
                $this->test_Popular($row["userID"], $categoryList);
            }else{
                $this->test_simCat($row["userID"], $categoryList);
            }            
            
        }          
    }    
  

    //vezme v?echny nav?tívené kategorie pro daného u?ivatele
    private function getUserCategories($uid) {
        $catList = array();
        $sql = "select * from train_set "
                . ""
                . "join objects_table on (train_set.objectID = objects_table.oid)"
                . " where train_set.userID=$uid ";
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            if(isset($catList[$row["category"]])){
                $catList[$row["category"]] ++;
            }else{
                $catList[$row["category"]] = 1;
            }
        }
        return $catList;
    }        
     //vezme v?echny podobné kategorie k sou?asné
    private function getCategorySimilarity($catID) {
        $catList = array();
        if($this->db_nazev_db=="antikvariat"){
            $sql = "SELECT `cat_id1` as `ref`, `cat_id2` as `category` , `similarity` FROM `category_similarity` WHERE `cat_id1`=$catID
                union all "
             . "SELECT `cat_id2` as `ref`, `cat_id1` as `category`, `similarity` FROM `category_similarity` WHERE `cat_id2`=$catID";
            
        }else{
            $sql = "SELECT `cat_id1` as `ref`, `cat_id2` as `category` , `similarity` FROM `category_similarity` WHERE `cat_id1`=\"$catID\"
                union all "
             . "SELECT `cat_id2` as `ref`, `cat_id1` as `category`, `similarity` FROM `category_similarity` WHERE `cat_id2`=\"$catID\"";
            
        }
         $query = mysql_query($sql);
       // echo $sql;
        while ($row = mysql_fetch_array($query)) {
            if(isset($catList[$row["category"]])){
                $catList[$row["category"]] += $row["similarity"];
            }else{
                $catList[$row["category"]] = $row["similarity"];
            }
        }
        return $catList;
    }
    
    
    
private function getUserObjects(){
        $this->user_objects_vector = array();
        //p?ed produk?ní verzí odstranit limit
        $sql = "select * from train_set where userID in ("
                . "select distinct test_set.userID from test_set  "
                . "where test_set.is_recommendable=1 "
                . ") "
                . "order by userID ";
        $query = mysql_query($sql);
        while ($row = mysql_fetch_array($query)) {
            if($row["objectID"]>0){
                //stránka je o jednom objektu => má plnou podporu
                if(!isset($this->user_objects_vector[$row["userID"]][$row["objectID"]])){
                    $this->user_objects_vector[$row["userID"]][$row["objectID"]] = 1;
                }else{
                    $this->user_objects_vector[$row["userID"]][$row["objectID"]] += 1;
                }
      //          echo "finished getUserObjects - object ".$row["objectID"]."\n<br/>";
            }else{
                //category page => stránka je o více objektech a tím pádem je do user profile p?idám s pat?i?ným zmen?ením
                //zde lze update: p?idám dle visibility
                $sumVisibility = 0;
                $count = 0;
                $vis_array = array();
                $sql_objects = "select * from object_visibility  where visitID=".$row["visitID"]."";
                $query_objects = mysql_query($sql_objects);
                while ($row_objects = mysql_fetch_array($query_objects)) {
                    $vis = IPR::GetVisibility($row_objects["visible_percentage"], $row_objects["visible_time"], $this->useVisibility, $this->minVisibilityThreshold);
                    $sumVisibility += $vis;
                    $count++;
                    $vis_array[$row_objects["objectID"]] = $vis;                    
                }
                foreach ($vis_array as $oid => $value) {
                    if($sumVisibility > 0){
                        if(!isset($this->user_objects_vector[$row["userID"]][$oid])){
                            $this->user_objects_vector[$row["userID"]][$oid] = $value/$sumVisibility;
                        }else{
                            $this->user_objects_vector[$row["userID"]][$oid] += $value/$sumVisibility;
                        } 
                    }else if($count > 0){
                        //uzivatel se poradne nepodival na zadnou cast category page, vykaslem se na sumVis
                        if(!isset($this->user_objects_vector[$row["userID"]][$oid])){
                            $this->user_objects_vector[$row["userID"]][$oid] = 1/$count;
                        }else{
                            $this->user_objects_vector[$row["userID"]][$oid] += 1/$count;
                        } 
                    }
                }                
            }
        }
    }

    
  
    public function test($uid){
  
        if($this->recommending_alg=="VSM" or $this->recommending_alg=="VSM_TF"){
            $this->test_vsm($uid);
        }
        if($this->recommending_alg=="VSM_IPR" or $this->recommending_alg=="VSM_TF_IPR"){
            //vytvorim prvni sadu a zaroven otestuju
            $this->test_vsm_ipr($uid);
        }
    }
    public function test_vsm($uid){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
        $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
        $this->evaluate($uid);        
        echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
                
    }   
    
    public function test_rand($uid){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        $this->user_top_k = array();
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            $rating = (mt_rand(0, 1000000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->evaluate($uid);
    }      
    
    public function test_simCat($uid, $catList){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        //print_r($catList);
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] + (mt_rand(0, 10000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
       // print_r($this->user_top_k[$uid]);
        $this->evaluate($uid);
    }  
    
    public function test_Popular($uid, $catList){
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        //print_r($catList);
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
       // $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] * staticData::$object_popularity[$oid];
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
       // print_r($this->user_top_k[$uid]);
        $this->evaluate($uid);
    }      
    
    public function test_IPR($uid){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();

        $resultList = array();
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
        
    
    public function test_Popular_IPR($uid, $catList){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] * staticData::$object_popularity[$oid];
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
    
    public function test_simCat_IPR($uid, $catList){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->user_top_k = array();
       // $this->test_vsm_uid($uid, $this->user_profile_vector[$uid]);
       // $this->evaluate($uid);        
       // echo Date("H:i:s")."finished test user  ".$uid."\n<br/>";
        $resultList = array();
        $rand_base = 1000000;
        foreach (staticData::$object_category as $oid => $category) {
            if(!isset($catList[$category])){
                $catList[$category] = 0;
            }
            $rating = $catList[$category] + (mt_rand(0, 10000)/$rand_base);
            $resultList[$oid] = $rating;
        }  
        arsort($resultList);
        $this->user_top_k[$uid] = $resultList;
        $this->createIPRList($uid); //pretridi seznam dle IPR
        $this->evaluate($uid);
    } 
    
        
    public function test_MF_IPR($uid){
        //print_r($catList);
        $this->IPRAppliedRelations = 0;
        $this->ConcordantRelations = 0;
        $this->WeakRelations = 0;
        
        $this->createIPRList($uid); //pretridi seznam dle IPR
        fwrite($this->log, "Merged MF and IPR for user $uid \n") ;
        $this->evaluate($uid);
    } 
    
    
    */

    
   
}
