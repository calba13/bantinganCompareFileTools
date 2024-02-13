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

use Gregwar\Captcha\CaptchaBuilder;

// extends Base\UserController for authentication handling
class AuthController extends Base\UserController
{
  public function index($parameter)
  {
    // to secure this function, we call requrelogin from Base\UserController
    $this->requirelogin();

    // to get login object
		$login = $this->getlogin();

		if ($parameter == "logout") {
			$login->doLogout();
			return $this->RedirectToAction('index','home');
		}

    $this->viewBag->login = $_SESSION['login'];
    // set menu variable for menu highlight non pjax page
    $this->viewBag->activeMenu = "menu-auth";

    return $this->View();
  }

  public function logout() {
    $login = $this->getlogin();
    $login->doLogout();
    return $this->RedirectToAction('index','home');
  }

  public function login()
	{
		$this->viewBag->pageTitle = "Login";

		$targetUrl = $this->flash('targeturl');
		$errorMsg = $this->flash("errormsg");
		
		$login = $this->getlogin();

		if ($login->isUserLoggedIn()) {
			if ($targetUrl != "" && $targetUrl != null) {						
				return $this->RedirectToURL($targetUrl);
			}
			return $this->RedirectToAction("index","home");
		} else {	
      if ($targetUrl == "" || $targetUrl == null) {			
        if (isset($_SERVER['HTTP_REFERER'])) {			
				  $targetUrl = $_SERVER['HTTP_REFERER'];
        }
			}
			$this->flash("targeturl", $targetUrl);
			if ($login->errors) {
				$this->flash("errormsg", join("<br>",$login->errors));				
				return $this->RedirectToAction("login");
			} 
			$this->viewBag->returnmessage = $errorMsg;
			return $this->View();
		}	
	}

	public function captcha()
	{		
		if (explode(".",phpversion())[0] == '8') {
			// Gregwar\Captcha would raise some PHP8 deprecated warning
			// so we disabled it for a while
			error_reporting(E_ERROR);
		}

		$captcha = new CaptchaBuilder();	
		$captcha->setDistortion(false);
		$captcha->setInterpolation(false);
		$captcha->build(
			200,
			60
		);	

		$_SESSION['registration']['captcha'] = $captcha->getPhrase();

        header('Content-type: image/jpeg');
        $captcha->output(60);
	}

}