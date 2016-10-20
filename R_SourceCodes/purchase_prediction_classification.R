library(doParallel)
registerDoParallel(5)
library(caret)


tC2 <- trainControl(method = "cv",
                      number = 5,
                      verboseIter = TRUE,
                    classProbs = TRUE
)

dt <- function(datasetName){
  
  dataset <- read.table(datasetName, header=TRUE, sep=";")
  colnames(dataset)[3] <- "label"
  #dataset <- preProcess(dataset, method = c("center", "scale"))
  dataset$label<- as.factor(dataset$label)
  #print(levels(dataset$label))
  levels(dataset$label) <- c("no","yes")
  
  dataset
}




dataset1 <- dt("dataset1.csv")
dataset2 <- dt("dataset2.csv")
dataset3 <- dt("dataset3.csv")
dataset4 <- dt("dataset4.csv")

distUIDs <- unique(dataset1$uid)

datasets <- c(
  "dataset4", "dataset3", "dataset2","dataset1"
)



###############################################################J48 tree############################################


for (dtName in datasets){
  dataset <- eval(parse(text=dtName))
  print(dtName)
  print(Sys.time())

set.seed(2016)
uids <- c()
pred <- c()
oids <- c()
res <- c()
predClass <- c()
predTable <- data.frame()
CList <-c()
grid <- expand.grid(C=c(0.1,0.25,0.9))


for (i in distUIDs) {
  print(Sys.time())
  train <- dataset[!(dataset$uid %in% i),] 
  train <- subset(train, select = -c(dataset$uid) )
  test <- dataset[(dataset$uid %in% i),]	
  test <- subset(test , select = -c(dataset$uid) )


 	mod <- train(label ~ ., data = train,
             method = "J48",
             trControl = tC2,
             metric="Kappa",
		        tuneGrid = grid
	)
 	
 	prediction <-predict(mod , newdata = test)
 	predictionPROB <-predict(mod , newdata = test, type = "prob")
 	results <-test$label
 	users <- test$uid
 	obj <- test$oid
 	csizes <- rep(mod$bestTune[1,"C"], each=length(users))
 	
 	uids <- append(uids,users )
 	predClass <- append(pred,prediction )
 	pred <- append(pred,predictionPROB$yes )
 	res <- append(res,results )
 	oids <- append(oids,obj )
 	CList <- append(CList,csizes ) 
	
}

predTable <- data.frame(uids,oids,pred,res,CList)
predTable$res <- predTable$res - 1 

write.table(predTable, file = paste("results/j48_",dtName,".csv", sep=""),row.names=FALSE, na="",col.names=TRUE, sep=";")

}





###############################################################ADA boost ############################################


for (dtName in datasets){
  dataset <- eval(parse(text=dtName))
  print(dtName)
  print(Sys.time())
  
  set.seed(2016)
  grid <- expand.grid(iter=c(10,20,50),maxdepth=c(1),nu=c(1))
  uids <- c()
  oids <- c()
  pred <- c()
  res <- c()
  predProbYes <- c()
  predTable <- data.frame()
  iterations <-c()
  
  
  k <- 0 
  
  for (i in distUIDs) {
    k <- k+1
    print(k)
    print(Sys.time())
    print(Sys.time())
    train <- dataset[!(dataset$uid %in% i),] 
    train <- subset(train, select = -c(dataset$uid) )
    test <- dataset[(dataset$uid %in% i),]	
    test <- subset(test , select = -c(dataset$uid) )
    
    mod <- train(label ~ ., data = train,
                 method = "ada",
                 trControl = tC2,
                 metric="Kappa",
                 tuneGrid = grid
    )
    prediction <-predict(mod , newdata = test)
    predictionPROB <-predict(mod , newdata = test, type = "prob")
    results <-test$label
    users <- test$uid
    obj <- test$oid
    tsizes <- rep(mod$bestTune[1,"iter"], each=length(users))
    
    uids <- append(uids,users )
    oids <- append(oids, obj)
    pred <- append(pred,predictionPROB$yes )
    res <- append(res,results )
    iterations <- append(iterations,tsizes ) 
    
  }
  
  predTable <- data.frame(uids,oids,pred,res,iterations)
  predTable$res <- predTable$res - 1 
  write.table(predTable, file = paste("results/ada_",dtName,".csv", sep=""),row.names=FALSE, na="",col.names=TRUE, sep=";")
  
}


















