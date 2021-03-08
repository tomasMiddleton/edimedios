<?php
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
    header('content-type: application/json; charset=utf-8');
    
    
    //datos del arhivo
    $nombre_archivo = $_FILES['file']['name'];
    $tipo_archivo = $_FILES['file']['type'];
    $tamano_archivo = $_FILES['file']['size'];
        
    //compruebo si las características del archivo son las que deseo
    if (!((strpos($tipo_archivo, "gif") || strpos($tipo_archivo, "jpeg")) && ($tamano_archivo < 100000))) {
        echo "La extensión o el tamaño de los archivos no es correcta. <br><br><table><tr><td><li>Se permiten archivos .gif o .jpg<br><li>se permiten archivos de 100 Kb máximo.</td></tr></table>";
    }else{
        if (move_uploaded_file($_FILES['file']['tmp_name'],  $nombre_archivo)){
                echo "El archivo ha sido cargado correctamente.";
        }else{
                echo "Ocurrió algún error al subir el fichero. No pudo guardarse.";
        }
    }
    echo "Uploaded";
?>