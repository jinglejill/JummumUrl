<?php
    include_once('dbConnect.php');
    setConnectionValue("");
    
    $contentAvailable = 1;
    $receiptID = 2;
    $data = null;
    $arrBody = array(
                     'alert' => 'test'//ข้อความ
                      ,'sound' => 'default'//,//เสียงแจ้งเตือน
//                      ,'badge' => 3 //ขึ้นข้อความตัวเลขที่ไม่ได้อ่าน
                     ,'category' => 'Print'
//                     ,'data' => $data
                     ,'content-available' => $contentAvailable
                     ,'receiptID' => $receiptID
                      );
    sendPushNotificationWithPath('bb6eac784aff3d9c8ade3c5c547092d77c72d8f7fbe6c0f28756d6c206755053',$arrBody,'./../../AdminApp/','jill');
    
//    sleep(5);
//
//
//    $arrBody = array(
//                     'alert' => 'เทสจิ๋ว 2
//                     เอ'//ข้อความ
//                     ,'sound' => 'default'//,//เสียงแจ้งเตือน
//                     //                      ,'badge' => 3 //ขึ้นข้อความตัวเลขที่ไม่ได้อ่าน
//                     );
//    sendTestApplePushNotification('1877301d04f677b7fcc415b7f0bcbd799bf679013b14f76ce746d778087a22f6',$arrBody);
?>

//<table><tr><td style="text-align: center;border: 1px solid black; padding-left: 10px;padding-right: 10px; border-radius: 15px;">x</td></tr></table>
