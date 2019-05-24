<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["decryptedMessage"]))
    {
        $decryptedMessage = $_POST["decryptedMessage"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $parts = explode("?",$decryptedMessage);
    if(sizeof($parts) > 0)
    {
        $paramPart = $parts[sizeof($parts)-1];
        
        
        //tableNo - ถ้ามี param: tableNo
        if(!(strpos($paramPart, "tableNo") === false))
        {
            $tableNoExplode = explode("=",$paramPart);
            $tableNoDecrypted = $tableNoExplode[1];
            $tableNoDecrypted = trim($tableNoDecrypted);
            
            
            $sql = "select aes_decrypt(unhex('$tableNoDecrypted'),'$encryptKey') as message;";
            $selectedRow = getSelectedRow($sql);
            $message = $selectedRow[0]["message"];
//            echo "<br>$sql";

        //    //test
//            $message = "Shop:11,TableNo:1";


            $arrMessage = explode(",", $message);
            if(sizeof($arrMessage) == 2)
            {
                $branchPart = $arrMessage[0];
                $customerTablePart = $arrMessage[1];
                
                //branch
                $arrBranchPart = explode(":", $branchPart);
                if(sizeof($arrBranchPart) == 2)
                {
                    $branchID = $arrBranchPart[1];
                }
                
                //customerTable
                $arrCustomerTablePart = explode(":", $customerTablePart);
                if(sizeof($arrCustomerTablePart) == 2)
                {
                    $customerTableID = $arrCustomerTablePart[1];
                }
            }
            

            $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
            $selectedRow =getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
            
            
            //note word เพิ่ม
            $sql = "select * from $dbName.setting where keyName = 'wordAdd'";
            $selectedRow = getSelectedRow($sql);
            $wordAdd = $selectedRow[0]["Value"];
            $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";

            //note word ไม่ใส่
            $sql = "select * from $dbName.setting where keyName = 'wordNo'";
            $selectedRow = getSelectedRow($sql);
            $wordNo = $selectedRow[0]["Value"];
            $wordNo = $wordNo?$wordNo:"ไม่ใส่";
            
            
            //select table -> branch
            $sql = "SELECT `BranchID`, `Name`, `TakeAwayFee`, `ImageUrl`,'$wordAdd' WordAdd, '$wordNo' WordNo FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
            

            //build sql statement for table
            $selectedRow = getSelectedRow($sql);
            if(sizeof($selectedRow)>0)
            {
                $eachDbName = $selectedRow[0]["DbName"];
                $sqlCustomerTable = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $dbName.CustomerTable where customerTableID = '$customerTableID'";
                $selectedRow = getSelectedRow($sqlCustomerTable);
                if(sizeof($selectedRow) == 0)
                {
                    $error = "QR Code ไม่ถูกต้อง";
                    writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
                    $response = array('success' => false, 'data' => null, 'error' => $error);
                    echo json_encode($response);
                    
                    
                    // Close connections
                    mysqli_close($con);
                    exit();
                }
            }
            else
            {
                $error = "QR Code ไม่ถูกต้อง";
                writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
                $response = array('success' => false, 'data' => null, 'error' => $error);
                echo json_encode($response);
                
         
                // Close connections
                mysqli_close($con);
                exit();
            }
            $sql .= $sqlCustomerTable . ";";
            
        }
        else if(!(strpos($paramPart, "shareMenuToOrder") === false))//shareMenuToOrder ถ้ามี param: shareMenuToOrder
        {
            $shareMenuToOrderExplode = explode("=",$paramPart);
            $shareMenuToOrderDecrypted = $shareMenuToOrderExplode[1];
            $shareMenuToOrderDecrypted = trim($shareMenuToOrderDecrypted);
            
            
            $sql = "select aes_decrypt(unhex('$shareMenuToOrderDecrypted'),'$encryptKey') as message;";
            $selectedRow = getSelectedRow($sql);
            $saveReceiptID = $selectedRow[0]["message"];
            
            
            
            //saveReceipt
            $sqlSaveReceipt = "select * from SaveReceipt where saveReceiptID = '$saveReceiptID';";
            $selectedRow = getSelectedRow($sqlSaveReceipt);
            $branchID = $selectedRow[0]["BranchID"];
            $customerTableID = $selectedRow[0]["CustomerTableID"];
            $buffetReceiptID = $selectedRow[0]["BuffetReceiptID"];
            
            //branch/////********************
            $sql = "select * from $jummumOM.Branch where branchID = '$branchID' and status = 1 and customerApp = 1;";
            $selectedRow = getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
            
            
            //note word เพิ่ม
            $sql = "select * from $dbName.setting where keyName = 'wordAdd'";
            $selectedRow = getSelectedRow($sql);
            $wordAdd = $selectedRow[0]["Value"];
            $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";

            //note word ไม่ใส่
            $sql = "select * from $dbName.setting where keyName = 'wordNo'";
            $selectedRow = getSelectedRow($sql);
            $wordNo = $selectedRow[0]["Value"];
            $wordNo = $wordNo?$wordNo:"ไม่ใส่";
            
            
            //select table -> branch, customerTable
            $sqlBranch = "SELECT `BranchID`, `Name`, `TakeAwayFee`, `ImageUrl`,'$wordAdd' WordAdd, '$wordNo' WordNo FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
            /////********************
            
            
            //customerTable
            $sqlCustomerTable = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $dbName.CustomerTable where status = 1 and customerTableID = '$customerTableID';";
            
            
            //saveOrderTaking
            $sqlSaveOrderTaking = "select saveOrderTaking.*,menu.MenuTypeID from saveOrderTaking left join $dbName.menu on saveOrderTaking.menuID = menu.menuID where saveReceiptID = '$saveReceiptID';";

            
            //saveOrderNote
            $sqlSaveOrderNote = "select * from saveOrderNote where saveOrderTakingID in (select saveOrderTakingID from saveOrderTaking where saveReceiptID = '$saveReceiptID');";
            
            
            //buffetReceipt
            $sqlBuffetReceipt = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$buffetReceiptID';";
            
            
            
            $sql = $sqlBranch;
            $sql .= $sqlCustomerTable;
            $sql .= $sqlSaveReceipt;
            $sql .= $sqlSaveOrderTaking;
            $sql .= $sqlSaveOrderNote;
            $sql .= $sqlBuffetReceipt;
        }
    }
    
    
    
    /* execute multi query */
    $arrMultiResult = executeMultiQueryArray($sql);
    
    
    $saveOrderNoteList = $arrMultiResult[4];
    for($i=0; $i<sizeof($saveOrderNoteList); $i++)
    {
        $noteID = $saveOrderNoteList[$i]->NoteID;
        $quantity = $saveOrderNoteList[$i]->Quantity;
        $sql = "select * from $dbName.Note where NoteID = '$noteID'";
        $arrNote = executeQueryArray($sql);
        $arrNote[0]->Quantity = $quantity;
        $saveOrderNoteList[$i]->Note = $arrNote;
    }
    
    
    $response = array('success' => true, 'data' => $arrMultiResult, 'error' => null);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
