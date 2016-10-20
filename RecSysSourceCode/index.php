<?php
/*
 * Primarni file, bude se starat o cely prubeh experimentu
 * udela predpripravene kroky a pak 
 */
gc_enable();
include 'ObjectAttributesBinarization.php';
include 'EvaluateConfiguration.php';
include 'staticData.php';
$typ = "slantour";
//new ObjectsAttributesBinarization($typ);
staticData::init($typ); 

/*run configurations*/ 
new EvaluateConfiguration($typ, "PopularSimCat", "linReg_dataset1", false); //binary feedback baseline
new EvaluateConfiguration($typ, "VSM", "linReg_dataset1", false); //binary feedback baseline


//domain, recommending alg, user engagement dataset
new EvaluateConfiguration($typ, "PopularSimCat", "adaLM_dataset1");
new EvaluateConfiguration($typ, "PopularSimCat", "adaLM_dataset2");
new EvaluateConfiguration($typ, "PopularSimCat", "adaLM_dataset3");
new EvaluateConfiguration($typ, "PopularSimCat", "adaLM_dataset4");


new EvaluateConfiguration($typ, "VSM", "adaLM_dataset1");
new EvaluateConfiguration($typ, "VSM", "adaLM_dataset2");
new EvaluateConfiguration($typ, "VSM", "adaLM_dataset3");
new EvaluateConfiguration($typ, "VSM", "adaLM_dataset4");


new EvaluateConfiguration($typ, "PopularSimCat", "j48_dataset1");
new EvaluateConfiguration($typ, "PopularSimCat", "j48_dataset2");
new EvaluateConfiguration($typ, "PopularSimCat", "j48_dataset3");
new EvaluateConfiguration($typ, "PopularSimCat", "j48_dataset4");


new EvaluateConfiguration($typ, "PopularSimCat", "ada_dataset1");
new EvaluateConfiguration($typ, "PopularSimCat", "ada_dataset2");
new EvaluateConfiguration($typ, "PopularSimCat", "ada_dataset3");
new EvaluateConfiguration($typ, "PopularSimCat", "ada_dataset4");


new EvaluateConfiguration($typ, "PopularSimCat", "linReg_dataset1");
new EvaluateConfiguration($typ, "PopularSimCat", "linReg_dataset2");
new EvaluateConfiguration($typ, "PopularSimCat", "linReg_dataset3");
new EvaluateConfiguration($typ, "PopularSimCat", "linReg_dataset4");


new EvaluateConfiguration($typ, "PopularSimCat", "lasso_dataset1");
new EvaluateConfiguration($typ, "PopularSimCat", "lasso_dataset2");
new EvaluateConfiguration($typ, "PopularSimCat", "lasso_dataset3");
new EvaluateConfiguration($typ, "PopularSimCat", "lasso_dataset4");


new EvaluateConfiguration($typ, "VSM", "j48_dataset1");
new EvaluateConfiguration($typ, "VSM", "j48_dataset2");
new EvaluateConfiguration($typ, "VSM", "j48_dataset3");
new EvaluateConfiguration($typ, "VSM", "j48_dataset4");


new EvaluateConfiguration($typ, "VSM", "ada_dataset1");
new EvaluateConfiguration($typ, "VSM", "ada_dataset2");
new EvaluateConfiguration($typ, "VSM", "ada_dataset3");
new EvaluateConfiguration($typ, "VSM", "ada_dataset4");


new EvaluateConfiguration($typ, "VSM", "linReg_dataset1");
new EvaluateConfiguration($typ, "VSM", "linReg_dataset2");
new EvaluateConfiguration($typ, "VSM", "linReg_dataset3");
new EvaluateConfiguration($typ, "VSM", "linReg_dataset4");


new EvaluateConfiguration($typ, "VSM", "lasso_dataset1");
new EvaluateConfiguration($typ, "VSM", "lasso_dataset2");
new EvaluateConfiguration($typ, "VSM", "lasso_dataset3");
new EvaluateConfiguration($typ, "VSM", "lasso_dataset4");



?>

