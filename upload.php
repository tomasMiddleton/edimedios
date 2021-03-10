<?php
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
header('content-type: application/json; charset=utf-8');


$fileTmpPath = $_FILES['uploadedFile']['tmp_name'];
$fileName = $_FILES['uploadedFile']['name'];
$fileSize = $_FILES['uploadedFile']['size'];
$fileType = $_FILES['uploadedFile']['type'];
$fileNameCmps = explode(".", $fileName);
$fileExtension = strtolower(end($fileNameCmps));


$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
$uploadFileDir = './uploads/';
$dest_path = $uploadFileDir . $newFileName;

try {
    if(move_uploaded_file($fileTmpPath, $dest_path)){
        $message ='File is successfully uploaded.';
    }else{
        $message = 'There was some error moving the file to upload directory. Please make sure the upload directory is writable by web server.';
    }
       
    print_r($_FILES);
    
    echo $message;
} catch (Exception $e) {
    echo $e->getMessage();
    die();
}
?>