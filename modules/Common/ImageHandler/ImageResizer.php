<?php
namespace Modules\Common\ImageHandler;

class ImageResizer 
{
	private static function Resize($filepath, $option=null)
	{
		$maxwidth = 200;

		// Get new sizes
		//list($width, $height, $mime) = getimagesize($filepath);
		if (!file_exists($filepath)) {
			$filepath = APPLICATION_SETTINGS["UploadDir"]. "images/noimage.png";
		}
		$img = getimagesize($filepath);
		$sourcemime = $img["mime"];
		$width = $img[0];
		$height = $img[1];

		if (isset($option["maxheight"]) && (int)$option["maxheight"] > 0) {
			$maxheight = (int)$option["maxheight"];
			$imageratio = $maxheight / $height;
			$maxwidth = $imageratio * $width;
		} else if (isset($option["maxwidth"]) && (int)$option["maxwidth"] > 0) {
			$maxwidth = (int)$option["maxwidth"];
			$imageratio = $maxwidth  / $width;
			$maxheight = $imageratio * $height;
		} else {
			$imageratio = $maxwidth  / $width;
			$maxheight = $imageratio * $height;
		}

		// default quality
		$quality = 60;
		if (isset($option["quality"]) && (int)$option["quality"] > 0) {
			$quality = (int)$option["quality"];
		}

		$maxwidth = (int)$maxwidth;
		$maxheight = (int)$maxheight;

		// Load
		$outputimage = imagecreatetruecolor($maxwidth, $maxheight);
		$source = null;
		switch ($sourcemime) {
			case "image/png":
				imagealphablending($outputimage, false);
				imagesavealpha($outputimage, true);
				$transparent = imagecolorallocatealpha($outputimage, 255, 255, 255, 127);
				imagefilledrectangle($outputimage, 0, 0, $width, $height, $transparent);
				//imagecopyresampled($newImg, $im, 0, 0, 0, 0, $dst_width, $dst_height, $width, $height);

				$source = imagecreatefrompng($filepath);
				break;
			case "image/jpg":
				$source = imagecreatefromjpeg($filepath);
				break;
			case "image/jpeg":
				$source = imagecreatefromjpeg($filepath);
				break;
			case "image/gif":
				$source = imagecreatefromgif($filepath);
				break;
			case "image/bmp":
				$source = imagecreatefrombmp($filepath);
				break;
			case "image/webp":
				$source = imagecreatefromwebp($filepath);
				break;
			case "image/vnd.wap.wbmp":
				$source = imagecreatefromwbmp($filepath);
				break;
		}

		if ($source != null) {
			// Resize
			imagecopyresampled($outputimage, $source, 0, 0, 0, 0, $maxwidth, $maxheight, $width, $height);
		} else {			
			$textcolor = imagecolorallocate($outputimage, 255, 255, 255);
			imagestring($outputimage, 5, 10, 10, 'Not Supported!', $textcolor);
		}

		imagedestroy($source);
		
		return $outputimage;
	}

	public static function Save($filesource, $fileoutput, $option=null)
	{
		$outputimage = self::Resize($filesource, $option);

		// default quality
		$quality = 60;
		if (isset($option["quality"]) && (int)$option["quality"] > 0) {
			$quality = (int)$option["quality"];
		}

		$img = getimagesize($filesource);
		$sourcemime = $img["mime"];

		switch ($sourcemime) {
			case "image/png":
				imagepng($outputimage, $fileoutput, 9);
				break;
			case "image/jpg":
				imagejpeg($outputimage, $fileoutput, $quality);
				break;
			case "image/jpeg":
				imagejpeg($outputimage, $fileoutput, $quality);
				break;
			case "image/gif":
				imagegif($outputimage, $fileoutput);
				break;
			case "image/bmp":
				imagebmp($outputimage, $fileoutput);
				break;
			case "image/webp":
				$source = imagecreatefromwebp($filepath);
				imagewebp($outputimage, $fileoutput, $quality);
				break;
			case "image/vnd.wap.wbmp":
				imagewbmp($outputimage, $fileoutput);
				break;
		}
		
		imagedestroy($outputimage);
	}

	public static function View($filesource, $option=null)
	{		
		$outputimage = self::Resize($filesource, $option);

		$img = getimagesize($filesource);
		$sourcemime = $img["mime"];

		// default quality
		$quality = 60;
		if (isset($option["quality"]) && (int)$option["quality"] > 0) {
			$quality = (int)$option["quality"];
		}

		// Content type
		header('Content-Type: '.$sourcemime);
		switch ($sourcemime) {
			case "image/png":
				imagepng($outputimage, null, 9);
				break;
			case "image/jpg":
				imagejpeg($outputimage, null, $quality);
				break;
			case "image/jpeg":
				imagejpeg($outputimage, null, $quality);
				break;
			case "image/gif":
				imagegif($outputimage, null);
				break;
			case "image/bmp":
				imagebmp($outputimage, null);
				break;
			case "image/webp":
				$source = imagecreatefromwebp($filepath);
				imagewebp($outputimage, null, $quality);
				break;
			case "image/vnd.wap.wbmp":
				imagewbmp($outputimage, null);
				break;
		}
		
		imagedestroy($outputimage);
	}
}
?>