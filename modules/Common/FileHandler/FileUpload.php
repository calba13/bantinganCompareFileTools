<?php
namespace Modules\Common\FileHandler;

class FileUpload
{
    public static function Save($files, $target_dir)
	{
        $success = false;
        $messages = "process not started";
        $target_file = [];

        if (is_array($files["name"])) {
            // uploading many files            
            $ctr = 0;		
            foreach($files["name"] as $key => $fileToUpload) {
                $savefileas = $target_dir . basename($fileToUpload);													
                if (!move_uploaded_file($files["tmp_name"][$key], $savefileas)) {			                    
                    $success = false;
                    $messages = "error uploading file ".basename($fileToUpload);                    
                    break;
                } else {                    
                    $target_file[$key] = $savefileas;
                    $ctr++;
                    $success = true;
                    $messages = "$ctr file(s) uploaded";
                }
            }            
        } else {
            // uploading single files
            $savefileas = $target_dir . basename($files["name"]);
            if (!move_uploaded_file($files["tmp_name"], $savefileas)) {                
                $success = false;
                $messages = "error uploading file ".basename($fileToUpload);
            } else {                
                $target_file[] = $savefileas;
                $success = true;
                $messages = "file uploaded";
            }
        }

        return [
            "success" => $success,
            "messages" => $messages,
            "files" => $target_file
        ];
    }
}