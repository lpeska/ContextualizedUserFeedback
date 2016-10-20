library(StatRank)


eval <- function(data){
  minScore <- -100
  
  
  users <- unique(data$uids)
  
  positions <- c()
  relpos <- c()
  ndcgArray <- c()
  ktArray <- c()
  pAtTenArray <- c()

  for (uid in users) {
    userData <- subset(data, data[,"uids"] == uid)
    ndcgArray <- c(ndcgArray, Evaluation.NDCG(userData$pred, userData$res))
    sortedUD <- userData[ order(-userData$pred), ]
    positions <- c(positions, which(sortedUD$res == 1))
    relpos <- c(relpos, which(sortedUD$res == 1)/length(sortedUD$res) )
    #browser()
  }
  rAt5 <- length(which(positions <=5))/length(positions)
  c(mean(ndcgArray),  mean(positions), mean(relpos),rAt5)
}


printTable <- function(dataNames,algorithm, nominal=FALSE)
{
  name <- c()
  ndcg <- c()
  kendallTau <- c()
  precAt5 <- c()
  avgPos <- c()
  avgRelPos <- c()
  rAt5 <- c()
  
	for(dtnm in dataNames){
		tab <- read.table(paste("results/",algorithm,"_",dtnm,".csv", sep=""), header=TRUE, sep=";")
		if(nominal == TRUE){
		  tab$res <- tab$res - 1 
		  tab$pred <- tab$predProbYes
		}

    res <- eval(tab)
    name <- c(name, paste(algorithm,dtnm, sep="_"))
    ndcg <- c(ndcg,res[1])
    avgPos <- c(avgPos,res[2])
    avgRelPos <- c(avgRelPos,res[3])
    rAt5 <- c(rAt5,res[4])
    #browser()
	}
	df <- data.frame(name,ndcg, avgPos,avgRelPos,rAt5)
	#browser()
  print(df)
}


printTable(c("dataset1", "dataset2", "dataset3", "dataset4"),"linReg")
printTable(c("dataset1", "dataset2", "dataset3", "dataset4"),"lasso")
printTable(c("dataset1", "dataset2", "dataset3", "dataset4"),"j48")
printTable(c("dataset1","dataset2", "dataset3", "dataset4"),"ada")
printTable(c("dataset1","dataset2", "dataset3", "dataset4"),"adaLM")



