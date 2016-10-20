library(doParallel)
registerDoParallel(5)
library(caret)


tC2 <- trainControl(method = "cv",
                      number = 5,
                      verboseIter = TRUE
)


print(Sys.time())


dt <- function(datasetName){
  
  dataset <- read.table(datasetName, header=TRUE, sep=";")
  colnames(dataset)[3] <- "label"
  dataset
}



dataset1 <- dt("dataset1.csv")
dataset2 <- dt("dataset2.csv")
dataset3 <- dt("dataset3.csv")
dataset4 <- dt("dataset4.csv")

distUIDs <- unique(dataset1$uid)

datasets <- c(
  "dataset4", "dataset3", "dataset2", "dataset1"
)





###############################################################ADA boost with LinReg ############################################


for (dtName in datasets){
  dataset <- eval(parse(text=dtName))
  print(dtName)
  print(Sys.time())
  
  set.seed(2016)
  grid <- expand.grid(mstop=c(10,20,50),nu=c(1))
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
    train <- dataset[!(dataset$uid %in% i),] 
    train <- subset(train, select = -c(dataset$uid) )
    test <- dataset[(dataset$uid %in% i),]	
    test <- subset(test , select = -c(dataset$uid) )
    
    mod <- train(label ~ ., data = train,
                 method = "BstLm",
                 trControl = tC2,
                 metric="RSquared",
                 tuneGrid = grid
    )
    prediction <-predict(mod , newdata = test)
    results <-test$label
    users <- test$uid
    obj <- test$oid
    tsizes <- rep(mod$bestTune[1,"mstop"], each=length(users))
    
    uids <- append(uids,users )
    oids <- append(oids,obj )
    pred <- append(pred,prediction )
    res <- append(res,results )
    iterations <- append(iterations,tsizes ) 
    
  }
  
  predTable <- data.frame(uids,oids,pred,res,iterations)
  write.table(predTable, file = paste("results/adaLM_",dtName,".csv", sep=""),row.names=FALSE, na="",col.names=TRUE, sep=";")
  
}


###############################################################Lin Reg ############################################

for (dtName in datasets){
  dataset <- eval(parse(text=dtName))
  print(dtName)
  print(Sys.time())

set.seed(2016)
uids <- c()
oids <- c()
pred <- c()
res <- c()
predTable <- data.frame()
k <- 0 

for (i in distUIDs) {
  k <- k+1
  print(k)
  print(Sys.time())
	train <- dataset[!(dataset$uid %in% i),] 
	train <- subset(train, select = -c(dataset$uid) )
	test <- dataset[(dataset$uid %in% i),]	
	test <- subset(test , select = -c(dataset$uid) )


 	mod <- train(label ~ ., data = train,
             method = "lm",
             trControl = tC2,
	       metric="RSquared",
	       tuneLength = 1
	)

	prediction <-predict(mod , newdata = test)
	results <-test$label
	users <- test$uid
	obj <- test$oid
	
	uids <- append(uids,users )
	oids <- append(oids,obj )
	pred <- append(pred,prediction )
	res <- append(res,results )
	
}
predTable <- data.frame(uids,oids,pred,res)
write.table(predTable, file = paste("results/linReg_",dtName,".csv", sep=""),row.names=FALSE, na="",col.names=TRUE, sep=";")


}





############################################################### Lasso ############################################

for (dtName in datasets){
  dataset <- eval(parse(text=dtName))
  print(dtName)
  print(Sys.time())
set.seed(2016)
uids <- c()
oids <- c()
pred <- c()
res <- c()
predTable <- data.frame()
fraction <-c()



k <- 0 

for (i in distUIDs) {
  k <- k+1
  print(k)
  print(Sys.time())
  train <- dataset[!(dataset$uid %in% i),] 
  train <- subset(train, select = -c(dataset$uid) )
  test <- dataset[(dataset$uid %in% i),]	
  test <- subset(test , select = -c(dataset$uid) )
  
  
  
  mod <- train(label ~ ., data = train,
               method = "lasso",
               trControl = tC2,
               metric="Rsquared",
               tuneLength = 5
  )
  prediction <-predict(mod , newdata = test)
  results <-test$label
  users <- test$uid
  obj <- test$oid
  fsizes <- rep(mod$bestTune[1,"fraction"], each=length(users))
  
  uids <- append(uids,users )
  oids <- append(oids,obj )
  pred <- append(pred,prediction )
  res <- append(res,results )
  fraction <- append(fraction,fsizes ) 
  

}

predTable <- data.frame(uids,oids,pred,res,fraction)
write.table(predTable, file = paste("results/lasso_",dtName,".csv", sep=""),row.names=FALSE, na="",col.names=TRUE, sep=";")


}















