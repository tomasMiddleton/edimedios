<?php
//header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');


$fileTmpPath = $_FILES['filepond']['tmp_name'];
$fileName = $_FILES['filepond']['name'];
$fileSize = $_FILES['filepond']['size'];
$fileType = $_FILES['filepond']['type'];
$fileNameCmps = explode(".", $fileName);
$fileExtension = strtolower(end($fileNameCmps));

/*
$fh = fopen("uploads/prueba.txt", 'w') or die("Se produjo un error al crear el archivo");
  
  $texto = "hollasda";
  
  fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
  
  fclose($fh);
  
  echo "Se ha escrito sin problemas";
*/


$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
$uploadFileDir = 'uploads/';
$dest_path = $uploadFileDir . $newFileName;


try {
    //echo $fileTmpPath . "  ";
    //echo $dest_path . "  ";
    if(move_uploaded_file($fileTmpPath, $dest_path)){
        $message ='File is successfully uploaded.';
    }else{
        $message = 'There was some error moving the file to upload directory. Please make sure the upload directory is writable by web server.';
    }

    //print_r($_FILES);
    
    echo $newFileName;
} catch (Exception $e) {
    echo $e->getMessage();
    die();
}

?>