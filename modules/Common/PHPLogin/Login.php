<?php
namespace Modules\Common\PHPLogin;

use Bantingan\Model;
use Modules\Common\Mailer\Mail;
use Modules\Common\Tools\GUID;

class Login 
{
    private $model;

    public $errors = array();

    public $messages = array();

    public function __construct($dbConnectionName)
    {
		$this->model = new Model("users");    
        $this->model->selectedDB = $dbConnectionName;

        if (isset($_GET["logout"])) {
            $this->doLogout();           
        } elseif (isset($_POST["requestPasswordReset"])) {
            // reset password request
            $this->requestPasswordReset();
        } else if (isset($_COOKIE['rememberme'])) {
            // login with cookie
            $this->loginWithCookieData();
        } elseif (isset($_POST["login"])) {
            // form login
            $this->loginWithPostData();
        } elseif (isset($_SERVER["PHP_AUTH_USER"])) {
            // login with windows 
            $this->loginWithWindows();
        } 
    }

    private function getUserData($username)
    {
        $sql = "SELECT id, user_name, user_email, user_password_hash, user_active, user_deleted, user_uuid
                    FROM users
                    WHERE (user_name = ? OR user_email = ?);";
        return $this->model->getrow($sql, [$username, $username]);
    }

    private function getUserModel($username)
    {        
        return $this->model->find("user_name = ? or user_email = ? ", [$username, $username]);
    }

    private function setUserSession($userdata)
    {
        $user = $this->model->load($userdata["id"]);
        if (isset($user->user_name)) {
            $user->user_failed_logins = 0;
            $date = new \DateTime();        
            $user->user_last_login_timestamp = $date->getTimestamp();

            if ($user->user_uuid == null) {
                $user->user_uuid = GUID::get();
            }

            $this->model->save($user);
        }  

        $_SESSION['login']['user_id'] = $userdata["id"];
        $_SESSION['login']['user_name'] = $userdata["user_name"];
        $_SESSION['login']['user_email'] = $userdata["user_email"];
        $_SESSION['login']['user_login_status'] = 1;
        $_SESSION['login']['user_uuid'] = $userdata["user_uuid"];
    }

    private function setLoginFailed($userdata)
    {
        $user = $this->model->load($userdata["id"]);
        if (isset($user->user_name)) {
            $user->user_failed_logins = $user->user_failed_logins + 1; 
            $date = new \DateTime();        
            $user->user_last_failed_logins = $date->getTimestamp();
            $this->model->save($user);
        }        
    }

    private function loginWithPostData()
    {
        if (empty($_POST['user_name'])) {
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
        } elseif (empty($_POST['user_password'])) {
            $this->errors[] = MESSAGE_PASSWORD_EMPTY;
        } elseif (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {        
            $result_row = $this->getUserData($_POST['user_name']);

            if ($result_row != null) {
                if (password_verify($_POST['user_password'], $result_row["user_password_hash"])) {
                    if ($result_row["user_active"] == 0 || $result_row["user_deleted"] == 1) {
                        $this->errors[] = MESSAGE_ACCOUNT_NOT_ACTIVATED;     
                    } else {
                        $this->setUserSession($result_row);
                    }                    
                } else {
                    $this->errors[] = MESSAGE_LOGIN_FAILED;
                    $this->setLoginFailed($result_row);
                }
            } else {
                $this->errors[] = MESSAGE_LOGIN_FAILED;
            }
        }
    }

    private function loginWithCookieData()
    {
        if (isset($_COOKIE['rememberme'])) {            
            list ($user_id, $token, $hash) = explode(':', $_COOKIE['rememberme']);            
            if ($hash == hash('sha256', $user_id . ':' . $token . COOKIE_SECRET_KEY) && !empty($token)) {   
                $sql = "SELECT id, user_name, user_email, user_password_hash
                FROM users
                WHERE id = ? OR user_rememberme_token = ? and user_rememberme_token is not null;";
                $result_row = $this->model->getrow($sql, [$user_id, $token]);

                if ($datauser != null)
                {                                     
                    $this->setUserSession($result_row);                    
                    $this->newRememberMeCookie();
                    return true;
                }                
            }            
            $this->deleteRememberMeCookie();
            $this->errors[] = MESSAGE_COOKIE_INVALID;
        }
        return false;
    }

    private function newRememberMeCookie()
    {        
        $random_token_string = hash('sha256', mt_rand());
        $remembermeuser = $this->model->load([$_SESSION['login']['user_id']]);
        $remembermeuser->user_rememberme_token = $random_token_string;                        
        $this->model->save($remembermeuser);
        
        $cookie_string_first_part = $_SESSION['login']['user_id'] . ':' . $random_token_string;
        $cookie_string_hash = hash('sha256', $cookie_string_first_part . APPLICATION_SETTINGS["Cookie_Secret_Key"]);
        $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;
        
        setcookie('rememberme', $cookie_string, time() + APPLICATION_SETTINGS["Cookie_Runtime"], "/", APPLICATION_SETTINGS["Cookie_Domain"]);        
    }

    private function deleteRememberMeCookie()
    {
        $remembermeuser = $this->model->load([$_SESSION['login']['user_id']]);
        if ($remembermeuser != null)
        {
            $remembermeuser->user_rememberme_token = null;                        
            $this->model->save($remembermeuser);    
        }

        setcookie('rememberme', false, time() - (3600 * 3650), '/', APPLICATION_SETTINGS["Cookie_Domain"]);
    }

    private function loginWithWindows()
    {
        $username = $_SERVER["PHP_AUTH_USER"];
        $result_row = $this->getUserData($_POST['user_name']);

        if ($result_row != null) {
            $this->setUserSession($result_row);
        }
    }

    public static function info($key) {
        if (isset($_SESSION['login'][$key])) {
            return $_SESSION['login'][$key];
        } 
        return null;        
    }

    public function doLogout()
    {
        $_SESSION["login"] = array();        
        $this->messages[] = MESSAGE_LOGGED_OUT;
    }

    public function isUserLoggedIn()
    {
        if (isset($_SESSION['login']['user_login_status']) AND $_SESSION['login']['user_login_status'] == 1) {
            return true;
        }
        return false;
    }

    public function requestPasswordReset()
    {        
        $captha = isset($_POST['captcha'])?$_POST['captcha']:"";
        if (!Registration::checkCaptcha($captha)) {            
            $this->errors[] = MESSAGE_CAPTCHA_WRONG;
            return;
        }

        if (empty($_POST['user_email'])) {
            $this->errors[] = MESSAGE_EMAIL_EMPTY;
            return;
        } else {
            $result_model = $this->getUserModel($_POST['user_email']);

            if ($result_model != null)
            {                                       
                // set reset password hash
                $user_password_reset_hash = sha1(uniqid(mt_rand(), true));
                $result_model->user_password_reset_hash = $user_password_reset_hash;
                $result_model->user_password_reset_timestamp = time();
                $result = $this->model->save($result_model);
                if ($result == 0) {
                    $this->errors[] = MESSAGE_PASSWORD_RESET_FAILED;
                    return;
                }

                // send reset password token email      
                /*    
                $mailContent = "<html><body><b>You are requesting password reset.</b>
                    <p>Please click following link to continue <a href='http://localhost:8090/disman/resetpassword/".$result_model->user_name."/".$user_password_reset_hash."'>http://localhost:8090/disman/resetpassword/".$result_model->user_name."/".$user_password_reset_hash."</a>.</p></body></html>";		
                $result = Mail::send([$_POST["user_email"]],
                    'devteam@piapiastudio.web.id', 
                    'Piapia Dev - Mailtrap',
                    'Request Reset Password for Piapia',
                    $mailContent
                );*/
                $dummy = array("_user_name", "_user_password_reset_hash");
                $real = array($result_model->user_name, $user_password_reset_hash);
                $mailContent = str_replace($dummy, $real, LOGIN_SETTINGS["resetpassword_mail_body"]);
                $result = Mail::send([$_POST["user_email"]],
                    LOGIN_SETTINGS["resetpassword_mail_from_email"], //'devteam@piapiastudio.web.id', 
                    LOGIN_SETTINGS["resetpassword_mail_from_name"], //'Piapia Dev - Mailtrap',
                    LOGIN_SETTINGS["resetpassword_mail_subject"], //'User Activation for Piapia',
                    $mailContent
                );

                if ($result["success"]) {
                    $this->messages[] = MESSAGE_PASSWORD_RESET_MAIL_SUCCESSFULLY_SENT." to ".$_POST["user_email"];    
                } else {
                    $this->errors[] = MESSAGE_PASSWORD_RESET_MAIL_FAILED." to ".$_POST["user_email"];    
                    $this->errors[] = $result["messages"];
                }                
                
                return;
            } else {
                $this->errors[] = MESSAGE_USER_DOES_NOT_EXIST; // we will send if registered
                return;
            }
        }                
    }    

    private function getUserResetPassword($user_name, $verification_code)
    {        
        return $this->model->find("user_name=? and user_password_reset_hash=? and user_provider_type='DEFAULT'", [$user_name, $verification_code]);
    }

    public function verifyPasswordReset($user_name, $verification_code)
    {
        // check if user-provided username + verification code combination exists
        $result_model = $this->getUserResetPassword($user_name, $verification_code);

        // if this user with exactly this verification hash code does NOT exist
        if (!$result_model) {
            $this->errors[] = MESSAGE_PASSWORD_RESET_LINK_INVALID;
            return false;
        }

        // 3600 seconds are 1 hour
        $timestamp_one_hour_ago = time() - 3600;

        // if password reset request was sent within the last hour (this timeout is for security reasons)
        if ($result_model->user_password_reset_timestamp > $timestamp_one_hour_ago) {

            // verification was successful
            $this->messages[] = MESSAGE_PASSWORD_RESET_LINK_VALID;
            return true;
        } else {
            $this->errors[] = MESSAGE_PASSWORD_RESET_LINK_INVALID;
            return false;
        }
    }

    public function resetPassword($user_name, $captcha, $user_password_new, $user_password_repeat)
    {
        // check captcha value
        if (!Registration::checkCaptcha($captcha)) {                        
            $this->errors[] = MESSAGE_CAPTCHA_WRONG;
            return false;
        }

        // check password rule
        $passwordisvalid = Registration::validateUserPassword($user_password_new, $user_password_repeat);        
        if ($passwordisvalid != "") {
            $this->errors[] = $passwordisvalid;
            return false;
        }        

        //$result_model = $this->getUserResetPassword($user_name, $user_password_reset_hash);
        // if this user with exactly this verification hash code does NOT exist
        //if (!$result_model) {
        //    $this->errors[] = MESSAGE_PASSWORD_RESET_LINK_INVALID;
        //    return false;
        //} 
        $result_model = $this->getUserModel($user_name);                
        $result_model->user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);
        $result_model->user_password_reset_hash = null;
        $result_model->user_password_reset_timestamp = null;
        $result = $this->model->save($result_model);
        if ($result == 0) {
            $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
            return false;
        }

        $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
        return true;
    }
}