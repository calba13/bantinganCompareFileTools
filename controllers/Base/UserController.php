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

namespace Controllers\Base;

use Bantingan\Controller;
use Modules\Common\PHPLogin\Login;
use Modules\Common\PHPLogin\Registration;

/**
 * Sample of Parent Controller
 * Used in controller with authorization
*/
class UserController extends Controller
{    
    private $_registration;
    private $_login;
    private $_jsonresponse = false;

    public function __construct()
	{        
        $this->viewBag = new \StdClass();
        if (isset($_SESSION['login']["user_name"])) {            
            $this->viewBag->user_name = ucfirst($_SESSION['login']["user_name"]);
        } else {
            $this->viewBag->user_name = "Guest";
        }
        parent::__construct();
    }

    protected function getregistration()
	{
        if ($this->_registration == null) {
            // set database connection name for user management database
            $this->_registration = new Registration("usermanagement");
        } 
        return $this->_registration;
    }

    protected function getlogin()
	{
        if ($this->_login == null) {
            // set database connection name for user management database
            $this->_login = new Login("usermanagement");
        } 
        return $this->_login;
    }

    protected function authjsonresponse()
	{
        $this->_jsonresponse = true;
    }

    protected function requirelogin()
	{		
        $login = $this->getlogin();
		if (!$login->isUserLoggedIn()) {
            if ($this->_jsonresponse) {
                return $this->Json(["success" => false, "messages" => "user must login!"]);
            } else {
                $this->flash("targeturl", $_SERVER['REQUEST_URI']);
                $this->RedirectToAction("login","auth");   
            }			
		}

        return $login;
	}
}