<?php
namespace Modules\Common\Tools;

class printMe 
{
	public static function shm($label, $data)
	{
		echo"<h4>".$label."</h4>"; echo "<pre>"; print_r($data); echo "</pre>";
	}

	public static function shmD($label, $data)
	{
		echo"<h4>".$label."</h4>"; echo "<pre>"; print_r($data); echo "</pre>"; die();
	}
}
?>