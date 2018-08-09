<?php
    include_once('dbConnect.php');
    setConnectionValue('dbName');
    $projectName = 'JMM';
    
    function makeFirstLetterLowerCase($text)
    {
        return strtolower(substr($text,0,1)) . substr($text,1,strlen($text)-1);
    }
    
    function getTableNameFromPrimaryKey($text)
    {
        return substr($text,0,strlen($text)-2);
    }
    
    function getSharedTableNameFromPrimaryKey($text)
    {
        return "Shared" . substr($text,0,strlen($text)-2);
    }
    
    function tab()
    {
        return "&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    $i = 0;
    $dbColumnName = array();
    $dbColumnType = array();
    $dbColumnTypeName = array();
    $propertyAttribute = array();
    $property;
    $primaryKey;
    $sql = "select * from " . $_GET['tableName'];
    
    
    if ($result=mysqli_query($con,$sql))
    {
        
        // Get field information for all fields
        while ($fieldinfo=mysqli_fetch_field($result))
        {
            $dbColumnName[$i] = $fieldinfo->name;
            $dbColumnType[$i] = $fieldinfo->type;
            
            
            if ($fieldinfo->flags & MYSQLI_PRI_KEY_FLAG) {
                //it is a primary key!
                $primaryKey = $fieldinfo->name;
            }
            $i++;
        }
        
        //set $dbColumnTypeName and $propertyAttribute
        for($j=0;$j<sizeof($dbColumnName);$j++)
        {
            if($dbColumnType[$j] == 1)//tinyint
            {
                $dbColumnTypeName[$j] = "NSInteger";
                $propertyAttribute[$j] = "(nonatomic)";
            }
            else if($dbColumnType[$j] == 3)//int
            {
                $dbColumnTypeName[$j] = "NSInteger";
                $propertyAttribute[$j] = "(nonatomic)";
            }
            else if($dbColumnType[$j] == 4)//float
            {
                $dbColumnTypeName[$j] = "float";
                $propertyAttribute[$j] = "(nonatomic)";
            }
            else if($dbColumnType[$j] == 253)//varchar
            {
                $dbColumnTypeName[$j] = "NSString *";
                $propertyAttribute[$j] = "(retain, nonatomic)";
            }
            else if($dbColumnType[$j] == 12)//datetime
            {
                $dbColumnTypeName[$j] = "NSDate *";
                $propertyAttribute[$j] = "(retain, nonatomic)";
            }
        }
        
        
        
        //model m
        //import
        $import = '#import "' . getSharedTableNameFromPrimaryKey($primaryKey) . '.h"<br>';
        $import .= '#import "Utility.h"<br>';
        $import .= "<br><br>";
        
        
        //implement
        $implement = "@implementation " . getTableNameFromPrimaryKey($primaryKey) . "<br>";
        $implement .= "<br>";
        
        

        //init method
        $initMethod .= "-(" . getTableNameFromPrimaryKey($primaryKey) . " *)initWith";
        
        for($j=1;$j<sizeof($dbColumnName)-2;$j++)
        {
            if($j == 1)
            {
                $initMethod .= $dbColumnName[$j] . ":(" . $dbColumnTypeName[$j] . ")" . makeFirstLetterLowerCase($dbColumnName[$j]);
            }
            else
            {
                $initMethod .= " " . makeFirstLetterLowerCase($dbColumnName[$j]) . ":(" . $dbColumnTypeName[$j] . ")" . makeFirstLetterLowerCase($dbColumnName[$j]);
            }
        }
        
        
        
        $initMethod .= "<br>";
        $initMethod .= "{<br>";
        $initMethod .= tab() . "self = [super init];<br>";
        $initMethod .= tab() . "if(self)<br>";
        $initMethod .= tab() . "{<br>";
        for($j=0;$j<sizeof($dbColumnName);$j++)
        {
            if($j == 0)
            {
                $initMethod .= tab() . tab() . "self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = [" . getTableNameFromPrimaryKey($primaryKey) . " getNextID];<br>";
            }
            else if($j == sizeof($dbColumnName)-2)
            {
                $initMethod .= tab() . tab() . "self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = [Utility modifiedUser];<br>";
            }
            else if($j == sizeof($dbColumnName)-1)
            {
                $initMethod .= tab() . tab() . "self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = [Utility currentDateTime];<br>";
            }
            else
            {
                $initMethod .= tab() . tab() . "self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = " . makeFirstLetterLowerCase($dbColumnName[$j]) . ";<br>";
            }
        }
        
        
        $initMethod .= tab() . "}<br>";
        $initMethod .= tab() . "return self;<br>";
        $initMethod .= "}<br>";
        $initMethod .= "<br>";
        
        
        
        //get next id
        $getNextID .= "+(NSInteger)getNextID<br>";
        $getNextID .= "{<br>";
        $getNextID .= tab() . 'NSString *primaryKeyName = @"' . makeFirstLetterLowerCase($primaryKey) . '";<br>';
        $getNextID .= tab() . 'NSString *propertyName = [NSString stringWithFormat:@"_%@",primaryKeyName];<br>';
        $getNextID .= tab() . "NSMutableArray *dataList = [" . getSharedTableNameFromPrimaryKey($primaryKey) . " " . makeFirstLetterLowerCase(getSharedTableNameFromPrimaryKey($primaryKey)) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $getNextID .= "<br>";
        $getNextID .= "<br>";
        $getNextID .= tab() . "NSSortDescriptor *sortDescriptor = [[NSSortDescriptor alloc] initWithKey:propertyName ascending:YES];<br>";
        $getNextID .= tab() . "NSArray *sortDescriptors = [NSArray arrayWithObjects:sortDescriptor, nil];<br>";
        $getNextID .= tab() . "NSArray *sortArray = [dataList sortedArrayUsingDescriptors:sortDescriptors];<br>";
        $getNextID .= tab() . "dataList = [sortArray mutableCopy];<br>";
        $getNextID .= "<br>";
        $getNextID .= tab() . "if([dataList count] == 0)<br>";
        $getNextID .= tab() . "{<br>";
        $getNextID .= tab() . tab() . "return -1;<br>";
        $getNextID .= tab() . "}<br>";
        $getNextID .= tab() . "else<br>";
        $getNextID .= tab() . "{<br>";
        $getNextID .= tab() . tab() . "id value = [dataList[0] valueForKey:primaryKeyName];<br>";
        $getNextID .= tab() . tab() . "if([value integerValue]>0)<br>";
        $getNextID .= tab() . tab() . "{<br>";
        $getNextID .= tab() . tab() . tab() . "return -1;<br>";
        $getNextID .= tab() . tab() . "}<br>";
        $getNextID .= tab() . tab() . "else<br>";
        $getNextID .= tab() . tab() . "{<br>";
        $getNextID .= tab() . tab() . tab() . "return [value integerValue]-1;<br>";
        $getNextID .= tab() . tab() . "}<br>";
        $getNextID .= tab() . "}<br>";
        $getNextID .= "}<br>";
        $getNextID .= "<br>";
        
        
        
        
        //add object
        $addObject = "+(void)addObject:(" . getTableNameFromPrimaryKey($primaryKey) . " *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "<br>";
        $addObject .= "{<br>";
        $addObject .= tab() . "NSMutableArray *dataList = [" . getSharedTableNameFromPrimaryKey($primaryKey) . " " . makeFirstLetterLowerCase(getSharedTableNameFromPrimaryKey($primaryKey)) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $addObject .= tab() . "[dataList addObject:" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "];<br>";
        $addObject .= "}<br>";
        $addObject .= "<br>";




        //remove object
        $removeObject = "+(void)removeObject:(" . getTableNameFromPrimaryKey($primaryKey) . " *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "<br>";
        $removeObject .= "{<br>";
        $removeObject .= tab() . "NSMutableArray *dataList = [" . getSharedTableNameFromPrimaryKey($primaryKey) . " " . makeFirstLetterLowerCase(getSharedTableNameFromPrimaryKey($primaryKey)) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $removeObject .= tab() . "[dataList removeObject:" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "];<br>";
        $removeObject .= "}<br>";
        $removeObject .= "<br>";
        
        
        
        
        //add list
        $addList = "+(void)addList:(NSMutableArray *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List<br>";
        $addList .= "{<br>";
        $addList .= tab() . "NSMutableArray *dataList = [Shared" . getTableNameFromPrimaryKey($primaryKey) . " shared" . getTableNameFromPrimaryKey($primaryKey) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $addList .= tab() . "[dataList addObjectsFromArray:" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List];<br>";
        $addList .= "}<br>";
        $addList .= "<br>";
        
        
        
        
        //remove list
        $removeList = "+(void)removeList:(NSMutableArray *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List<br>";
        $removeList .= "{<br>";
        $removeList .= tab() . "NSMutableArray *dataList = [Shared" . getTableNameFromPrimaryKey($primaryKey) . " shared" . getTableNameFromPrimaryKey($primaryKey) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $removeList .= tab() . "[dataList removeObjectsInArray:" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List];<br>";
        $removeList .= "}<br>";
        $removeList .= "<br>";
        
        
        
        

        //get object
        $getObject = "+(" . getTableNameFromPrimaryKey($primaryKey) . " *)get" . getTableNameFromPrimaryKey($primaryKey) . ":(NSInteger)" . makeFirstLetterLowerCase($dbColumnName[0]) . "<br>";
        $getObject .= "{<br>";
        $getObject .= tab() . "NSMutableArray *dataList = [" . getSharedTableNameFromPrimaryKey($primaryKey) . " " . makeFirstLetterLowerCase(getSharedTableNameFromPrimaryKey($primaryKey)) . "]." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $getObject .= tab() . 'NSPredicate *predicate = [NSPredicate predicateWithFormat:@"_' . makeFirstLetterLowerCase($primaryKey) . ' = %ld",' . makeFirstLetterLowerCase($primaryKey) . '];<br>';
        $getObject .= tab() . "NSArray *filterArray = [dataList filteredArrayUsingPredicate:predicate];<br>";
        $getObject .= tab() . "if([filterArray count] > 0)<br>";
        $getObject .= tab() . "{<br>";
        $getObject .= tab() . tab() . "return  filterArray[0];<br>";
        $getObject .= tab() . "}<br>";
        $getObject .= tab() . "return nil;<br>";
        $getObject .= "}<br>";
        $getObject .= "<br>";
        
       
        
        //copyWithZone
        $copyWithZone = "-(id)copyWithZone:(NSZone *)zone<br>";
        $copyWithZone .= "{<br>";
        $copyWithZone .= tab() . "id copy = [[[self class] alloc] init];<br>";
        $copyWithZone .= "<br>";
        $copyWithZone .= tab() . "if (copy)<br>";
        $copyWithZone .= tab() . "{<br>";
        for($j=0;$j<sizeof($dbColumnName);$j++)
        {
            if($j == sizeof($dbColumnName)-2)
            {
                $copyWithZone .= tab() . tab() . "[copy set" . $dbColumnName[$j] . ":[Utility modifiedUser]];<br>";
            }
            else if($j == sizeof($dbColumnName)-1)
            {
                $copyWithZone .= tab() . tab() . "[copy set" . $dbColumnName[$j] . ":[Utility currentDateTime]];<br>";
            }
            else if($dbColumnType[$j] == 1 || $dbColumnType[$j] == 3 || $dbColumnType[$j] == 4)
            {
                $copyWithZone .= tab() . tab() . "((" . getTableNameFromPrimaryKey($primaryKey) . " *)copy)." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = self." . makeFirstLetterLowerCase($dbColumnName[$j]) . ";<br>";
            }
            else
            {
                $copyWithZone .= tab() . tab() . "[copy set" . $dbColumnName[$j] . ":self." . makeFirstLetterLowerCase($dbColumnName[$j]) . "];<br>";
            }
        }
        $copyWithZone .= tab() . tab() . "((" . getTableNameFromPrimaryKey($primaryKey) .  " *)copy).replaceSelf = self.replaceSelf;<br>";
        $copyWithZone .= tab() . tab() . "((" . getTableNameFromPrimaryKey($primaryKey) .  " *)copy).idInserted = self.idInserted;<br>";
        $copyWithZone .= tab() . "}<br>";
        $copyWithZone .= tab() . "<br>";
        $copyWithZone .= tab() . "return copy;<br>";
        $copyWithZone .= "}<br>";
        $copyWithZone .= "<br>";
        
        
        
        //is edit object
        $editObject = "-(BOOL)edit" . getTableNameFromPrimaryKey($primaryKey) . ":(" . getTableNameFromPrimaryKey($primaryKey) . " *)editing" . getTableNameFromPrimaryKey($primaryKey) . "<br>";
        $editObject .= "{<br>";
        for($j=0;$j<sizeof($dbColumnName)-2;$j++)
        {
            if($j == 0)
            {
                $editObject .= tab() . "if(self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " == editing" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . "<br>";
            }
            else
            {
                if($dbColumnType[$j] == 1 || $dbColumnType[$j] == 3 || $dbColumnType[$j] == 4)
                {
                    $editObject .= tab() . "&& self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " == editing" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . "<br>";
                }
                else if($dbColumnType[$j] == 253)
                {
                    $editObject .= tab() . "&& [self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " isEqualToString:editing" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . "]<br>";
                }
                else if($dbColumnType[$j] == 12)
                {
                    $editObject .= tab() . "&& [self." . makeFirstLetterLowerCase($dbColumnName[$j]) . " isEqual:editing" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . "]<br>";
                }
            }
        }
        $editObject .= tab() . ")<br>";
        $editObject .= tab() . "{<br>";
        $editObject .= tab() . tab() . "return NO;<br>";
        $editObject .= tab() . "}<br>";
        $editObject .= tab() . "return YES;<br>";
        $editObject .= "}<br>";
        $editObject .= "<br>";
        
        
        
        //copyObject  From
        $copyObjectFrom = "+(" . getTableNameFromPrimaryKey($primaryKey) . " *)copyFrom:(" . getTableNameFromPrimaryKey($primaryKey) . " *)from" . getTableNameFromPrimaryKey($primaryKey) . " to:(" . getTableNameFromPrimaryKey($primaryKey) . " *)to" . getTableNameFromPrimaryKey($primaryKey) . "<br>";
        $copyObjectFrom .= "{<br>";
        for($j=0;$j<sizeof($dbColumnName)-2;$j++)
        {
            $copyObjectFrom .= tab() . "to" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . " = from" . getTableNameFromPrimaryKey($primaryKey) . "." . makeFirstLetterLowerCase($dbColumnName[$j]) . ";<br>";
        }
        $copyObjectFrom .= tab() . "to" . getTableNameFromPrimaryKey($primaryKey) . ".modifiedUser = [Utility modifiedUser];<br>";
        $copyObjectFrom .= tab() . "to" . getTableNameFromPrimaryKey($primaryKey) . ".modifiedDate = [Utility currentDateTime];<br>";
        $copyObjectFrom .= tab() . "<br>";
        $copyObjectFrom .= tab() . "return to" . getTableNameFromPrimaryKey($primaryKey) . ";<br>";
        $copyObjectFrom .= "}<br>";
        $copyObjectFrom .= "<br>";
        
        
        
        //shared model m
        //property
        $propertyShared = "@synthesize " . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $propertyShared .= "<br>";
        
        
        
        //method
        $method = "+(Shared" . getTableNameFromPrimaryKey($primaryKey) . " *)shared" . getTableNameFromPrimaryKey($primaryKey) . " {<br>";
        $method .= tab() . "static dispatch_once_t pred;<br>";
        $method .= tab() . "static Shared" . getTableNameFromPrimaryKey($primaryKey) ." *shared = nil;<br>";
        $method .= tab() . "dispatch_once(&pred, ^{<br>";
        $method .= tab() . tab() . "shared = [[Shared" . getTableNameFromPrimaryKey($primaryKey) . " alloc] init];<br>";
        $method .= tab() . tab() . "shared." . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List = [[NSMutableArray alloc]init];<br>";
        $method .= tab() . "});<br>";
        $method .= tab() . "return shared;<br>";
        $method .= "}<br>";
    
        
        
        
        //utility
        //url address
        $urlAddress = "case url" . getTableNameFromPrimaryKey($primaryKey) . "Insert:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'Insert.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        $urlAddress .= "case url" . getTableNameFromPrimaryKey($primaryKey) . "Update:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'Update.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        $urlAddress .= "case url" . getTableNameFromPrimaryKey($primaryKey) . "Delete:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'Delete.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        $urlAddress .= "case url" . getTableNameFromPrimaryKey($primaryKey) . "InsertList:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'InsertList.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        $urlAddress .= "case url" . getTableNameFromPrimaryKey($primaryKey) . "UpdateList:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'UpdateList.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        $urlAddress .= "case url" . getTableNameFromPrimaryKey($primaryKey) . "DeleteList:<br>";
        $urlAddress .= tab() . 'url = @"/' . $projectName . '/' . $_GET['dbName'] . '/' . $projectName  . getTableNameFromPrimaryKey($primaryKey) . 'DeleteList.php";<br>';
        $urlAddress .= tab() . 'break;<br>';
        
        
        
        //homemodel
        $homeModel = '#import "' . getTableNameFromPrimaryKey($primaryKey) . '.h"<br>';
        $homeModel .= "<br><br>";
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . ":<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "noteDataString = [Utility getNoteDataString:data];<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "Insert]];<br>";
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . "List:<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "NSMutableArray *" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List = (NSMutableArray *)data;<br>";
        $homeModel .= tab() . "NSInteger count" . getTableNameFromPrimaryKey($primaryKey) . " = 0;<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . 'noteDataString = [NSString stringWithFormat:@"count' . getTableNameFromPrimaryKey($primaryKey) . '=%ld",[' . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . 'List count]];<br>';
        $homeModel .= tab() . "for(" . getTableNameFromPrimaryKey($primaryKey) . " *item in " . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List)<br>";
        $homeModel .= tab() . "{<br>";
        $homeModel .= tab() . tab() . 'noteDataString = [NSString stringWithFormat:@"%@&%@",noteDataString,[Utility getNoteDataString:item withRunningNo:count' . getTableNameFromPrimaryKey($primaryKey) . ']];<br>';
        $homeModel .= tab() . tab() . "count" . getTableNameFromPrimaryKey($primaryKey) . "++;<br>";
        
        $homeModel .= tab() . "}<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "InsertList]];<br>";
        
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "<br><br>";
        
        
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . ":<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "noteDataString = [Utility getNoteDataString:data];<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "Update]];<br>";
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . "List:<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "NSMutableArray *" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List = (NSMutableArray *)data;<br>";
        $homeModel .= tab() . "NSInteger count" . getTableNameFromPrimaryKey($primaryKey) . " = 0;<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . 'noteDataString = [NSString stringWithFormat:@"count' . getTableNameFromPrimaryKey($primaryKey) . '=%ld",[' . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . 'List count]];<br>';
        $homeModel .= tab() . "for(" . getTableNameFromPrimaryKey($primaryKey) . " *item in " . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List)<br>";
        $homeModel .= tab() . "{<br>";
        $homeModel .= tab() . tab() . 'noteDataString = [NSString stringWithFormat:@"%@&%@",noteDataString,[Utility getNoteDataString:item withRunningNo:count' . getTableNameFromPrimaryKey($primaryKey) . ']];<br>';
        $homeModel .= tab() . tab() . "count" . getTableNameFromPrimaryKey($primaryKey) . "++;<br>";
        
        $homeModel .= tab() . "}<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "UpdateList]];<br>";
        
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "<br><br>";
        
        
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . ":<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "noteDataString = [Utility getNoteDataString:data];<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "Delete]];<br>";
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "case db" . getTableNameFromPrimaryKey($primaryKey) . "List:<br>";
        $homeModel .= "{<br>";
        $homeModel .= tab() . "NSMutableArray *" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List = (NSMutableArray *)data;<br>";
        $homeModel .= tab() . "NSInteger count" . getTableNameFromPrimaryKey($primaryKey) . " = 0;<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . 'noteDataString = [NSString stringWithFormat:@"count' . getTableNameFromPrimaryKey($primaryKey) . '=%ld",[' . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . 'List count]];<br>';
        $homeModel .= tab() . "for(" . getTableNameFromPrimaryKey($primaryKey) . " *item in " . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List)<br>";
        $homeModel .= tab() . "{<br>";
        $homeModel .= tab() . tab() . 'noteDataString = [NSString stringWithFormat:@"%@&%@",noteDataString,[Utility getNoteDataString:item withRunningNo:count' . getTableNameFromPrimaryKey($primaryKey) . ']];<br>';
        $homeModel .= tab() . tab() . "count" . getTableNameFromPrimaryKey($primaryKey) . "++;<br>";
        
        $homeModel .= tab() . "}<br>";
        $homeModel .= "<br>";
        $homeModel .= tab() . "url = [NSURL URLWithString:[Utility url:url" . getTableNameFromPrimaryKey($primaryKey) . "DeleteList]];<br>";
        
        $homeModel .= "}<br>";
        $homeModel .= "break;<br>";
        $homeModel .= "<br><br>";
        
        
        
        
        
        

        
        // Free result set
        mysqli_free_result($result);
    }
    
    
    //model m
    echo $import . $implement . $initMethod . $getNextID . $addObject . $removeObject . $addList . $removeList . $getObject . $copyWithZone . $editObject . $copyObjectFrom . "<br><br>";

    //shared model m
    echo $propertyShared . $method . "<br><br>";
    
    
    //url address utility
    echo $urlAddress . "<br><br>";
    
    
    //homemodel
    echo $homeModel . "<br><br>";
    
    
?>
