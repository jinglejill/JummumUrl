<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    header("Content-Type: application/json");
    
    // get the lower case rendition of the headers of the request
    
    $headers = array_change_key_case(getallheaders());
    
    // extract the content-type
    
    if (isset($headers["content-type"]))
    $content_type = $headers["content-type"];
    else
    $content_type = "";
    
    // if JSON, read and parse it
    
    if ($content_type == "application/json")
    {
        // read it
        
        $handle = fopen("php://input", "rb");
        $raw_post_data = '';
        while (!feof($handle)) {
            $raw_post_data .= fread($handle, 8192);
        }
        fclose($handle);
        
        // parse it
        
        $data = json_decode($raw_post_data, true);
    }
    else
    {
        // report non-JSON request and exit
    }
    
    // now use that `$data` variable here
    
    // if you wanted to report it back to the client for debugging purposes, you should
    // recreate JSON response:
    writeToLog("test username: " . $data["username"]);
    $raw_result = json_encode($data);
    
    // finally, write the body of the response
    
    writeToLog( $raw_result);
    

?>
