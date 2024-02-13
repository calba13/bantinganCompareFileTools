<?php
/*
Copyright (c) <2021> Susilo Nurcahyo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

namespace Controllers;

use Bantingan\Controller;


class HomeController extends Controller
{	
	public function index()
	{	
		// check out how we read value from settings
		$this->viewBag->data = "Welcome To ".APPLICATION_SETTINGS["SiteTitle"];
		$this->viewBag->pageTitle = "Home";
		$this->viewBag->activeMenu = "menu-dashboard";

		// checking required extension
		$this->viewBag->extensioncheck = [
			"mysqli" => extension_loaded('mysqli'),
			"pdo" => extension_loaded('pdo'),
			"gd" => extension_loaded('gd'),
			"mbstring" => extension_loaded('mbstring'),
			"zip" => extension_loaded('zip')			
		];		

		// sample to read environment variables
		$sampleEnvConfig = getenv("BANTINGAN3_SAMPLECUSTOM_SETTINGS");
		$this->viewBag->sampleEnvConfig = json_decode($sampleEnvConfig); // decode json string to object


		return $this->View();
	}		

	public function standardmenu()
	{
		$this->viewBag->data = "This function use the same HTML view file with function 'index', but passed different data";
		$this->viewBag->pageTitle = "Standard Menu";
		$this->viewBag->activeMenu = "menu-standard";
		// reuse existing index.html file
		return $this->View("Home/index.html");
	}

	public function info() {
		// flash message can also be used for showing one time information, for example process result or error messages
		$infoTitle = $this->flash("infoTitle");
		$infoMessage = $this->flash("infoMessage");
		$infoLink = $this->flash("infoLink");
		$infoLinkTitle = $this->flash("infoLinkTitle");
		$pageTitle = $this->flash("pageTitle");

		// default message should be defined in configuration, this is for example only
		$this->viewBag->infoTitle = $infoTitle ?? "Hi";
		$this->viewBag->infoMessage = $infoMessage ?? "Currently we don't have any information for you,<br>You can go back to home page.";
		$this->viewBag->infoLink = $infoLink ?? $this->baseUrl;
		$this->viewBag->infoLinkTitle = $infoLinkTitle ?? "Back To Home Page";
		$this->viewBag->pageTitle = $pageTitle ?? "Info";

		return $this->View();
	}
}