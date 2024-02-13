<?php
namespace Bantingan;
/*		
    This is the application page generator

    Bantingan Framework v0.3
    Copyright (C) 2020 by Susilo Nurcahyo
    susilonurcahyo@gmail.com

    Some library are copyright to their respective owners.

    We use awesome Smarty Template Engine, many thank's to all Smarty Team
    https://www.smarty.net/

	Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.	

	This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.
*/ 
use Smarty;

class PageGenerator
{	
	public $viewBag;
	public $viewHtml;
	public $smarty;
	public $contents;
	public $baseUrl;
	public $baseController;

	public function __construct()
	{
		$this->viewBag = new \StdClass();
	}

	public function Create($viewPathArg)
	{				
		if (!$viewPathArg) {
			// then sets the view according to the caller function, not the url controller path
			$this->viewHtml = BANTINGAN_ACTION_NAME. ".html";	;
			//Detertime the full path to the view file
			$current_view_path = ucfirst(BANTINGAN_CONTROLLER_NAME) . "/";
			$viewPathArg=$current_view_path . $this->viewHtml;
		} 

		$viewPath=__DIR__ . '/../'.APPLICATION_SETTINGS["Views"].'/'.$viewPathArg;	
		
		if	(file_exists($viewPath))	{
			//If the file exists, call the smarty engine			
			//include APPLICATION_SETTINGS["Smarty_Bootstrap_Path"].'bootstrap.php';
			$this->smarty = new Smarty(); 
			$this->smarty->caching = 0;

			// predefined variable, overrideable from controller
			$this->smarty->assign("Shared", APPLICATION_SETTINGS["SharedViewFolder"]);
			$baseUrl = !empty(APPLICATION_SETTINGS["BaseUrl"])?'/'.APPLICATION_SETTINGS["BaseUrl"]:"";
			$this->smarty->assign("baseUrl", '//'.$_SERVER['HTTP_HOST'].$baseUrl);
			$this->smarty->assign("siteTitle", APPLICATION_SETTINGS["SiteTitle"]);
			$this->smarty->assign("pageTitle", ucfirst(BANTINGAN_ACTION_NAME));

			// set variable bag from controller
			foreach($this->viewBag as $key => $value) {
				$this->smarty->assign($key, $value);
			}
			
			// render the page
			$this->contents = $this->smarty->fetch($viewPath);	
		}
		else	{
			// view not available
		    throw new \Exception("Page not available", 404);				
		}			
		return $this->contents;
	}

	public function Render($viewPathArg)
	{	
		echo $this->Create($viewPathArg);
	}
}