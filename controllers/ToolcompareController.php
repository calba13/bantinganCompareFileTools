<?php
/*
SAMPLE SYNTAX :
-------------------
printMe::shmD("get_current_user", get_current_user());

*/

namespace Controllers;

use Bantingan\Controller;
use Modules\Common\Tools\printMe;
use Modules\Common\Tools\GUID;

class ToolcompareController extends Controller
{	
	public function index()
	{	
		
		$this->viewBag->get_current_user = get_current_user();
        return $this->view();
	}		

	public function comparefolder(){
	
        // printMe::shm("POST", $_POST);

		// $dataPath1 = $this->convertPath($_POST["folderSatu"]);
		// $dataPath2 = $this->convertPath($_POST["folderDua"]); 

		$_SESSION["path1"] = $this->convertPath($_POST["folderSatu"]);
		$_SESSION["path2"] = $this->convertPath($_POST["folderDua"]); 

		if(isset($_POST["flagAction"])) {
			// printMe::shm("flagAction", "KE ISI");
			$this->RedirectToAction('rsltcomparexlsx','toolcompare');	
		}else{
			$this->RedirectToAction('rsltcompare','toolcompare');	
		}

    }

	public function convertPath($path){
		return str_replace('\\','/', $path);
	}


	public function rsltcompare(){

		$dataPath1 = $_SESSION["path1"];
		$dataPath2 = $_SESSION["path2"]; 

		$data = $this->compareprc($dataPath1, $dataPath2);

		$this->viewBag->pathSatu = $dataPath1;
        $this->viewBag->pathDua = $dataPath2;

        $this->viewBag->rltData = $data["data"];

        $this->viewBag->totalDataF1 = $data["p1total"];
        $this->viewBag->totalDataF2 = $data["p2total"];

        return $this->view();

	}

	public function rsltcomparexlsx(){
		
		$dataPath1 = $_SESSION["path1"];
		$dataPath2 = $_SESSION["path2"];

		$data = $this->compareprc($dataPath1, $dataPath2, true);

		$this->viewBag->pathSatu = $dataPath1;
        $this->viewBag->pathDua = $dataPath2;

        $this->viewBag->rltData = $data["data"];

        $this->viewBag->totalDataF1 = $data["p1total"];
        $this->viewBag->totalDataF2 = $data["p2total"];
        $html = $this->page();

        // instantiate and use the dompdf class
        $dompdf = new \Dompdf\Dompdf();

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        $output = $dompdf->output();

        // file_put_contents($destinationPath."QCPASSFORM".$id.".pdf", $output);
        $filename = "Result_Compare_Folder.pdf";

		$guid = GUID::get();
        $path = APPLICATION_SETTINGS["UploadDir"]."/".$guid;   
        
        // PROSES DOWNLOAD
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
            // echo "FOLDER Temp in Server PATH is Created... ^_^ <br>";
        }

		$pathFile = $path."/".$filename;
        // PROSES DOWNLOAD (Created FIle)
        file_put_contents($pathFile, $output);

		
		$this->download($guid, $pathFile);

        $this->RedirectToAction('','toolcompare');

	}


	public function download($guid, $file_path) {

		if (file_exists($file_path)) {
            $filename = basename($file_path);
			header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
			header("Cache-Control: public"); // needed for internet explorer
			header("Content-Type: application/pdf");
			header("Content-Transfer-Encoding: Binary");
			header("Content-Length:".filesize($file_path));
			header("Content-Disposition: attachment; filename=".$filename);
			readfile($file_path);

			unlink($file_path);
			if (is_dir(APPLICATION_SETTINGS["UploadDir"]."/".$guid)) {
				// Hapus folder jika ada
				if (!rmdir(APPLICATION_SETTINGS["UploadDir"]."/".$guid)) {
					// echo "Gagal menghapus folder.";
					printMe::shm("FOLDER", "Gagal menghapus folder.");
				} else {
					printMe::shm("FOLDER", "Folder berhasil dihapus.");
				}
			} else {
				printMe::shm("FOLDER", "Folder tidak ditemukan.");
			}
            //die(); 
		}
	}



	public function compareprc($path1, $path2, $is_download=null){

		//printMe::shm("USER", get_current_user());
		
		$dataPath1 = $this->scan_dir($path1, $is_download);
		$dataPath2 = $this->scan_dir($path2, $is_download);

		// printMe::shm("PATH #1", $dataPath1);
		// printMe::shm("PATH #2", $dataPath2);

			# Filter Data by FILEName yang kondisinya KEY sama
			$tmpfinaldata = array();
			foreach ($dataPath1["data"] as $key => $value){
				if (array_key_exists($key, $dataPath2['data'])) {

					// Jika key tersebut ada di kedua array, Anda dapat membandingkan nilainya
					if ($dataPath1['data'][$key] === $dataPath2['data'][$key]) {
						// echo $key. " : SAMA";
						$trStyle = "";
						$flag = "";
					} else {
						// echo $key. " : BEDA";
						$trStyle = "table-danger";
						$flag = "X";
					}

					$item = [
								// $flag,
								$key, 
								$dataPath1['data'][$key]["icon"], 
								$dataPath1['data'][$key]["size"], 
								$dataPath1['data'][$key]["dtmod"], 
								$key, 
								$dataPath2['data'][$key]["icon"], 
								$dataPath2['data'][$key]["size"], 
								$dataPath2['data'][$key]["dtmod"]
						];

					$tmpfinaldata[] = ["trStyle" => $trStyle, "item" => $item];
					
					unset($dataPath1["data"][$key]);
					unset($dataPath2["data"][$key]);
				}
			}


			#SISA ARRAY 1
			foreach ($dataPath1['data'] as $key => $val){
				$tmpfinaldata[] = [
					"trStyle" => "table-danger",
					"item" => [ 
								// "X",
								$key, 
								$val["icon"], 
								$val["size"], 
								$val["dtmod"], 
								null, 
								null, 
								null, 
								null
							]	
				];
	
			}
	
			#SISA ARRAY 2
			foreach ($dataPath2['data'] as $key => $val){
				$tmpfinaldata[] = [
					"trStyle" => "table-danger",
					"item" => [
								// "X",
								null, 
								null, 
								null, 
								null, 
								$key, 
								$val["icon"], 
								$val["size"], 
								$val["dtmod"]
							]
				];
	
			}


			// printMe::shmD("returnData #1", $returnData);

			$returnData = array();
			$returnData["p1total"] = ["totalfolder" => $dataPath1["totalfolder"], "totalfile" => $dataPath1["totalfile"]];
			$returnData["p2total"] = ["totalfolder" => $dataPath2["totalfolder"], "totalfile" => $dataPath2["totalfile"]];
			$returnData["data"] = $tmpfinaldata; // execute query for data

			// printMe::shmD("returnData #1", $returnData);
			return $returnData;
			

	}


	private function scan_dir($dir, $is_download=null){

		$tmpfiles = array_values(array_diff(scandir($dir), array('..', '.')));

		$data = [];
		$totalFolder = 0;
		$totalFile = 0;

		for($i=0; $i < count($tmpfiles); $i++){
			
			if (filetype($dir."/".$tmpfiles[$i]) == "dir"){
				$type = $is_download ? 'Folder' : '<i class="fa fa-folder-o"></i>';
				$size = "";
				$totalFolder++;
			}else{
				$type = $is_download ? 'File' : '<i class="fa fa-file-text-o"></i>';
				$size = number_format(ceil((filesize($dir."/".$tmpfiles[$i])/1024)))." Kb";
				$totalFile++;
			}


			// printMe::shm("filename #1", $tmpfiles[$i]);
			// printMe::shm("size FORMAt #1", number_format(ceil((filesize($dir."/".$tmpfiles[$i])/1024)))." Kb" );
			// printMe::shm("size #1", ceil((filesize($dir."/".$tmpfiles[$i])/1024)));
			


			$data[$tmpfiles[$i]] = [
				"type" => filetype($dir."/".$tmpfiles[$i]),
				"icon" => $type,
				"size" => $size,
				"dtmod" => @date('F d, Y, h:i A', filemtime($dir."/".$tmpfiles[$i])),
				// "dtmod" => filemtime($dir."/".$tmpfiles[$i]),

			];

		}

		$returnData = array();
        $returnData["totalfolder"] = $totalFolder;
        $returnData["totalfile"] = $totalFile;
        $returnData["data"] = $data; // execute query for data

		return $returnData;
	}
	
}