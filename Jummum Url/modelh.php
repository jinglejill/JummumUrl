<?php
    include_once('dbConnect.php');
    setConnectionValue('dbName');
    
    
    function makeFirstLetterLowerCase($text)
    {
        return strtolower(substr($text,0,1)) . substr($text,1,strlen($text)-1);
    }
    
    function getTableNameFromPrimaryKey($text)
    {
        return substr($text,0,strlen($text)-2);
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
        
        
        //model h
        //property
        for($j=0;$j<sizeof($dbColumnName);$j++)
        {
            $property .= "@property " . $propertyAttribute[$j] . " " . $dbColumnTypeName[$j] . " " . makeFirstLetterLowerCase($dbColumnName[$j]) . ";<br>";
        }
        

        $property .= "@property (nonatomic) NSInteger replaceSelf;<br>";
        $property .= "@property (nonatomic) NSInteger idInserted;<br>";
        $property .= "<br>";
        

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
        $initMethod .= ";<br>";
        
        
        //get next id
        $getNextID = "+(NSInteger)getNextID;<br>";
        
        
        //add object
        $addObject = "+(void)addObject:(" . getTableNameFromPrimaryKey($primaryKey) . " *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . ";<br>";
        
        
        //remove object
        $removeObject = "+(void)removeObject:(" . getTableNameFromPrimaryKey($primaryKey) . " *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . ";<br>";
        
        
        //add list
        $addList = "+(void)addList:(NSMutableArray *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        
        
        //remove list
        $removeList = "+(void)removeList:(NSMutableArray *)" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        

        //get object
        $getObject = "+(" . getTableNameFromPrimaryKey($primaryKey) . " *)get" . getTableNameFromPrimaryKey($primaryKey) . ":(NSInteger)" . makeFirstLetterLowerCase($dbColumnName[0]) . ";<br>";
        
        
        //is edit object
        $editObject = "-(BOOL)edit" . getTableNameFromPrimaryKey($primaryKey) . ":(" . getTableNameFromPrimaryKey($primaryKey) . " *)editing" . getTableNameFromPrimaryKey($primaryKey) . ";<br>";
        
        
        //copyObjectFrom
        $copyObjectFrom = "+(" . getTableNameFromPrimaryKey($primaryKey) . " *)copyFrom:(" . getTableNameFromPrimaryKey($primaryKey) . " *)from" . getTableNameFromPrimaryKey($primaryKey) . " to:(" . getTableNameFromPrimaryKey($primaryKey) . " *)to" . getTableNameFromPrimaryKey($primaryKey) . ";<br>";
        
       
        
        //shared model*****************
        //property
        $propertyShared = "@property (retain, nonatomic) NSMutableArray *" . makeFirstLetterLowerCase(getTableNameFromPrimaryKey($primaryKey)) . "List;<br>";
        $propertyShared .= "<br>";
        
        
        //method
        $method = "+ (Shared" . getTableNameFromPrimaryKey($primaryKey) . " *)shared" . getTableNameFromPrimaryKey($primaryKey) . ";";
        
        
        
        
        //utility
        //db
        $dbEnum = "db" . getTableNameFromPrimaryKey($primaryKey) . ",<br>";
        $dbEnum .= "db" . getTableNameFromPrimaryKey($primaryKey) . "List<br>";
        
        
        
        //url enum
        $urlEnum = "url" . getTableNameFromPrimaryKey($primaryKey) . "Insert,<br>";
        $urlEnum .= "url" . getTableNameFromPrimaryKey($primaryKey) . "Update,<br>";
        $urlEnum .= "url" . getTableNameFromPrimaryKey($primaryKey) . "Delete,<br>";
        $urlEnum .= "url" . getTableNameFromPrimaryKey($primaryKey) . "InsertList,<br>";
        $urlEnum .= "url" . getTableNameFromPrimaryKey($primaryKey) . "UpdateList,<br>";
        $urlEnum .= "url" . getTableNameFromPrimaryKey($primaryKey) . "DeleteList<br>";
        
        
        
        
        
        
        
        // Free result set
        mysqli_free_result($result);
        
    }
    
    //model h
    echo $property . $initMethod . $getNextID . $addObject . $removeObject . $addList . $removeList . $getObject . $editObject . $copyObjectFrom . "<br><br>";
    //sharedmodel h
    echo $propertyShared . $method . "<br><br>";
    //db enum utility
    echo $dbEnum . "<br><br>";
    //url enum utility
    echo $urlEnum . "<br><br>";
    
    

?>
