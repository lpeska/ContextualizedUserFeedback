library(StatRank)


evalRow <- function(dtRow){
  pos <- dtRow[3]
  tot <- dtRow[4] 
  if(!is.na(pos)){
    rAt5Array <- ifelse(pos>5, 0, 1)
    rAt10Array <- ifelse(pos>10, 0, 1)
    rAt50Array <- ifelse(pos>50, 0, 1)
    if(pos <= 1){
      ndcg <- 1
    }else{
      ndcg <- Evaluation.NDCG(seq(1,pos), c(1,rep(0,(pos-1))) )
    }
    
    c(rAt5Array,rAt10Array,rAt50Array,ndcg)
  }else{
    c(NA,NA,NA,NA)
  }  

}

eval <- function(data){
  res <- apply(data,1,evalRow)
  
  rAt5 <- mean(res[1,],na.rm = TRUE)
  rAt10 <- mean(res[2,],na.rm = TRUE)
  rAt50 <- mean(res[3,],na.rm = TRUE)
  ndcg <- mean(res[4,],na.rm = TRUE)
  
  c(rAt5,rAt10,rAt50,ndcg)
}



printTable <- function(dataSource,dataNames,algorithm)
{
  name <- c()
  ndcg <- c()
  recAt5 <- c()
  recAt10 <- c()
  recAt50 <- c()
  
	for(dtnm in dataNames){
		tab <- read.table(paste("results/",dataSource,"-",algorithm,"-",dtnm,".csv", sep=""), header=TRUE, sep=";")
    res<-eval(tab)

    name <- c(name, paste(algorithm,dtnm, sep="_"))
    recAt5 <- c(recAt5,res[1])
    recAt10 <- c(recAt10,res[2])
    recAt50 <- c(recAt50,res[3])
    ndcg <- c(ndcg,res[4])
	}
  
	df <- data.frame(name,recAt5, recAt10,recAt50,ndcg)
	#browser()
  print(df)
}

printTable("slantour",
           c("BinaryFeedback","linReg_dataset1", "linReg_dataset2", "linReg_dataset3", "linReg_dataset4", 
             "lasso_dataset1", "lasso_dataset2", "lasso_dataset3", "lasso_dataset4", 
             "adaLM_dataset1","adaLM_dataset2", "adaLM_dataset3", "adaLM_dataset4", 
             "ada_dataset1","ada_dataset2", "ada_dataset3", "ada_dataset4", 
             "j48_dataset1", "j48_dataset2", "j48_dataset3", "j48_dataset4"
             ),"VSM")

printTable("slantour",
           c("BinaryFeedback","linReg_dataset1", "linReg_dataset2", "linReg_dataset3", "linReg_dataset4",
             "lasso_dataset1", "lasso_dataset2", "lasso_dataset3", "lasso_dataset4", 
             "adaLM_dataset1","adaLM_dataset2", "adaLM_dataset3", "adaLM_dataset4", 
             "ada_dataset1","ada_dataset2", "ada_dataset3", "ada_dataset4", 
             "j48_dataset1", "j48_dataset2", "j48_dataset3", "j48_dataset4"
           ),"PopularSimCat")






binomialTest <- function(n,s) {
	p = 0
	for (k in s:n) { 
    	p <- p+choose(n,k)*0.5^n
	}
	p
}


compareRecAtK <- function(dataName1, dataName2,k){
  p<-paste(dataName1," vs.", dataName2)
  print(p)
  tab1 <- read.table(paste("results/",dataName1,".csv", sep=""), header=TRUE, sep=";")
  tab2 <- read.table(paste("results/",dataName2,".csv", sep=""), header=TRUE, sep=";")
  
  results <- data.frame(tab1$position , tab2$position)
  colnames(results) <- c("pos1", "pos2")
  results$pos1 <- ifelse(results$pos1<=k,1,0)
  results$pos2 <- ifelse(results$pos2<=k,1,0)
  
  firstBetter <- length( which(results$pos1 > results$pos2) )
  secondBetter <- length( which(results$pos1 < results$pos2) )
  different <- firstBetter + secondBetter
  
  
  print(c(different,firstBetter,secondBetter))
  print(
    c(
      First_better=binomialTest(different,firstBetter), 
      Second_better=binomialTest(different,secondBetter) 
    )
  )	
}


compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset1",50)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset1",10)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset1",5)

compareRecAtK("slantour-PopularSimCat-j48_dataset3","slantour-PopularSimCat-j48_dataset1",10)
compareRecAtK("slantour-PopularSimCat-j48_dataset3","slantour-PopularSimCat-j48_dataset1",5)

compareRecAtK("slantour-PopularSimCat-j48_dataset2","slantour-PopularSimCat-j48_dataset1",10)
compareRecAtK("slantour-PopularSimCat-j48_dataset2","slantour-PopularSimCat-j48_dataset1",5)


compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset2",50)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset2",10)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-j48_dataset2",5)


compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset1",50)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset1",10)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset1",5)

compareRecAtK("slantour-PopularSimCat-ada_dataset3","slantour-PopularSimCat-ada_dataset1",10)
compareRecAtK("slantour-PopularSimCat-ada_dataset3","slantour-PopularSimCat-ada_dataset1",5)

compareRecAtK("slantour-PopularSimCat-ada_dataset2","slantour-PopularSimCat-ada_dataset1",10)
compareRecAtK("slantour-PopularSimCat-ada_dataset2","slantour-PopularSimCat-ada_dataset1",5)

compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset2",50)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset2",10)
compareRecAtK("slantour-PopularSimCat-BinaryFeedback","slantour-PopularSimCat-ada_dataset2",5)


compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset1",50)
compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset1",10)
compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset1",5)

compareRecAtK("slantour-VSM-j48_dataset3","slantour-VSM-j48_dataset1",10)
compareRecAtK("slantour-VSM-j48_dataset3","slantour-VSM-j48_dataset1",5)

compareRecAtK("slantour-VSM-j48_dataset2","slantour-VSM-j48_dataset1",10)
compareRecAtK("slantour-VSM-j48_dataset2","slantour-VSM-j48_dataset1",5)


compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset2",50)
compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset2",10)
compareRecAtK("slantour-VSM-BinaryFeedback","slantour-VSM-j48_dataset2",5)

