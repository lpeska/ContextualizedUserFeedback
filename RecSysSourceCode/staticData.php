<?php
/**
 * Description of staticData
 * Data potøebné nìkolika rùznými tøídami - optimalizace dotazù do DTB
 * @author peska
 */
class staticData {
    //put your code here
    public static $object_features;
    public static $object_category;
    public static $category_similarity;
    public static $object_popularity;
    
    private static $db_server="127.0.0.1"; //connect spider
    private static $db_jmeno="root";
    private static $db_heslo="";
    private static $db_nazev_db="antikvariat";
    //inicializace a zpracování SQL dotazù
    static function init($typ){
        echo Date("H:i:s")." starting  staticData\n<br/>";  
        
        
        //init object features
        if($typ=="antikvariat"){
            self::$db_nazev_db="antikvariat";
        }else{
            self::$db_nazev_db="slantour";
        }
        @$db_spojeni = mysql_connect(self::$db_server, self::$db_jmeno, self::$db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());
        @$db_vysledek = mysql_select_db($typ, $db_spojeni) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysql_error());

        $query_val = "SELECT * FROM `objects_binary_attributes` WHERE 1";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_features[$row_val["oid"]][$row_val["feature"]] = $row_val["value"];
        } 
        
        $query_cat = "SELECT distinct `TourSeriesID`, `TourTypeID`, `CountryList`, `DestinationList` FROM `content_based_tour_attributes` WHERE 1";
        $result_cat = mysql_query($query_cat);
        while ($row_cat = mysql_fetch_array($result_cat)) {  
            self::$object_category[$row_cat["TourSeriesID"]] = $row_cat["TourTypeID"].":".$row_cat["CountryList"].":".$row_cat["DestinationList"];
        } 
        
        $query_cat = "SELECT * FROM `category_similarity` WHERE 1";
        $result_cat = mysql_query($query_cat);
        while ($row_cat = mysql_fetch_array($result_cat)) {  
            self::$category_similarity[$row_cat["cat_id1"]][$row_cat["cat_id2"]] = $row_cat["similarity"];
        } 
        
        $query_val = "SELECT OID, count(*) as popularity FROM `extended_user_behavior` WHERE 1 group by OID";
        $result_val = mysql_query($query_val);
        while ($row_val = mysql_fetch_array($result_val)) {  
            self::$object_popularity[$row_val["OID"]] = log($row_val["popularity"]+2.72);
        } 

        
        
        echo Date("H:i:s")." finish  staticData\n<br/>"; 
    }
    
    static function getObjectCategory($oid){
        return self::$object_category[$oid];
    }
    
    static function getSimilarCategories($category){
        return self::$category_similarity[$category];
    }    
    
    //spocita COS sim
   static function CosineSimilarity($objectFeatures1, $objectFeatures2){
         $sumOF1 = 0.000000001; //nenulovy jmenovatel
         $sumOF2 = 0.000000001;
         $sumOF1_x_OF2 = 0;
         $features = array();
         //stanovuju globalni seznam vlastnosti         
         foreach ($objectFeatures1 as $key => $value) {
             $features[$key] = 1;
         }
         foreach ($objectFeatures2 as $key => $value) {
             $features[$key] = 1;
         }
         //projdu seznam vlastnosti, spoctu podobnost
         foreach ($features as $key => $val) {
             if(!isset($objectFeatures1[$key])){
                 $objectFeatures1[$key]=0;
             }
             if(!isset($objectFeatures2[$key])){
                 $objectFeatures2[$key]=0;
             }
             $sumOF1 += $objectFeatures1[$key]*$objectFeatures1[$key];
             $sumOF2 += $objectFeatures2[$key]*$objectFeatures2[$key];
             $sumOF1_x_OF2 += $objectFeatures1[$key]*$objectFeatures2[$key];
         }
         $objectFeatures1 = "";
         $objectFeatures2 = "";
         $similarity = $sumOF1_x_OF2 /(sqrt($sumOF1)*sqrt($sumOF2));
      
         return $similarity;
    }  
    
}
