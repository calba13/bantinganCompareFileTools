<?php
namespace Modules\Common\PHPLogin;

use Bantingan\Model;
use Modules\Common\Mailer\Mail;
use Modules\Common\Tools\GUID;

class Registration
{
    private $model;

    private $userId;

    public $errors = array();

    public $messages = array();

    public function __construct($dbConnectionName)
    {
		$this->model = new Model("users");    
        $this->model->selectedDB = $dbConnectionName;
    }

    public function registerNewUser()
    {
        // clean the input
        $user_name = strip_tags($_POST['user_name']);
        $user_email = strip_tags($_POST['user_email']);
        $user_email_repeat = strip_tags($_POST['user_email_repeat']);
        $user_password_new = $_POST['user_password_new'];
        $user_password_repeat = $_POST['user_password_repeat'];

        // stop registration flow if registrationInputValidation() returns false (= anything breaks the input check rules)
        $validation_result = self::registrationInputValidation($_POST['captcha'], $user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat);
        if (!$validation_result) {
            return false;
        }

        // crypt the password with the PHP 5.5's password_hash() function, results in a 60 character hash string.
        // @see php.net/manual/en/function.password-hash.php for more, especially for potential options
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);

        // make return a bool variable, so both errors can come up at once if needed
        $return = true;

        // check if username already exists
        if (self::doesUsernameAlreadyExist($user_name)) {
            $this->errors[] = MESSAGE_USERNAME_EXISTS;            
            $return = false;
        }

        // check if email already exists
        if (self::doesEmailAlreadyExist($user_email)) {
            $this->errors[] = MESSAGE_EMAIL_ALREADY_EXISTS;            
            $return = false;
        }

        // if Username or Email were false, return false
        if (!$return) return false;

        // generate random hash for email verification (40 char string)
        $user_activation_hash = sha1(uniqid(mt_rand(), true));

        // write user data to database
        $registrationtimestamp = time();
        if (!self::writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $registrationtimestamp, $user_activation_hash)) {            
            $this->errors[] = MESSAGE_REGISTRATION_FAILED;
            return false; // no reason not to return false here
        }

        // get user_id of the user that has been created, to keep things clean we DON'T use lastInsertId() here
        $user_id = self::getUserIdByUsername($user_name);

        if (!$user_id) {
            $this->errors[] = MESSAGE_REGISTRATION_FAILED;
            return false;
        }

        // send verification email     
        $registrationtimestampverification = $user_id.'rEts'.$registrationtimestamp;   
        if (self::sendVerificationEmail($registrationtimestampverification, $user_email, $user_activation_hash)) {
            $this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT;    
            return true;
        } else {
            // if verification email sending failed: instantly delete the user
            self::rollbackRegistrationByUserId($user_id);        
            $this->errors[] = MESSAGE_REGISTRATION_FAILED;    
            return false;
        }
    }

    /**
     * Validates the registration input
     *
     * @param $captcha
     * @param $user_name
     * @param $user_password_new
     * @param $user_password_repeat
     * @param $user_email
     * @param $user_email_repeat
     *
     * @return bool
     */
    public function registrationInputValidation($captcha, $user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat)
    {
        $return = true;

        // perform all necessary checks
        if (!self::checkCaptcha($captcha)) {            
            $this->errors[] = MESSAGE_CAPTCHA_WRONG;
            $return = false;
        }

        $validatepassword = self::validateUserPassword($user_password_new, $user_password_repeat);
        if ($validatepassword != "") {
            $this->errors[] = $validatepassword;
            $return = false;
        }

        // if username, email and password are all correctly validated, but make sure they all run on first sumbit
        if (self::validateUserName($user_name) AND self::validateUserEmail($user_email, $user_email_repeat) AND $return) {
            return true;
        }

        // otherwise, return false
        return false;
    }

    /**
     * Validates the username
     *
     * @param $user_name
     * @return bool
     */
    public function validateUserName($user_name)
    {
        if (empty($user_name)) {            
            $this->errors[] = MESSAGE_USERNAME_EMPTY;
            return false;
        }

        // if username is too short (2), too long (64) or does not fit the pattern (aZ09)
        if (!preg_match('/^[a-zA-Z0-9]{2,64}$/', $user_name)) {            
            $this->errors[] = MESSAGE_USERNAME_INVALID;
            return false;
        }

        return true;
    }

    /**
     * Validates the email
     *
     * @param $user_email
     * @param $user_email_repeat
     * @return bool
     */
    public function validateUserEmail($user_email, $user_email_repeat)
    {
        if (empty($user_email)) {            
            $this->errors[] = MESSAGE_EMAIL_EMPTY;
            return false;
        }

        if ($user_email !== $user_email_repeat) {            
            $this->errors[] = MESSAGE_EMAIL_REPEAT_WRONG;
            return false;
        }

        // validate the email with PHP's internal filter
        // side-fact: Max length seems to be 254 chars
        // @see http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
        if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {            
            $this->errors[] = MESSAGE_EMAIL_INVALID;
            return false;
        }

        return true;
    }

    /**
     * Validates the password
     *
     * @param $user_password_new
     * @param $user_password_repeat
     * @return bool
     */
    public static function validateUserPassword($user_password_new, $user_password_repeat)
    {
        if (empty($user_password_new) OR empty($user_password_repeat)) {            
            //$this->errors[] = MESSAGE_PASSWORD_EMPTY;
            return MESSAGE_PASSWORD_EMPTY;
        }

        if ($user_password_new !== $user_password_repeat) {
            //$this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
            return MESSAGE_PASSWORD_BAD_CONFIRM;
        }

        if (strlen($user_password_new) < 7) {            
            //$this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
            return MESSAGE_PASSWORD_TOO_SHORT;
        }

        return "";
    }

    /**
     * Writes the new user's data to the database
     *
     * @param $user_name
     * @param $user_password_hash
     * @param $user_email
     * @param $user_creation_timestamp
     * @param $user_activation_hash
     *
     * @return bool
     */
    public function writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $user_creation_timestamp, $user_activation_hash)
    {        
        $user = $this->model->create();
        $user->user_name = $user_name;
        $user->user_password_hash = $user_password_hash;
        $user->user_email = $user_email;
        $user->user_active = 0;
        $user->user_deleted = 0;
        $user->user_creation_timestamp =$user_creation_timestamp;
        $user->user_activation_hash = $user_activation_hash;
        $user->user_provider_type = 'DEFAULT';
        $user->user_uuid = GUID::get();

        $userid = $this->model->save($user);

        if ($userid > 0) {
            $this->userId = $userid;
            return true;
        }

        return false;
    }

    /**
     * Deletes the user from users table. Currently used to rollback a registration when verification mail sending
     * was not successful.
     *
     * @param $user_id
     */
    public function rollbackRegistrationByUserId($user_id)
    {
        $userdata = $this->model->load($user_id);
        $this->model->trash($userdata);
    }

    /**
     * Sends the verification email (to confirm the account).
     * The construction of the mail $body looks weird at first, but it's really just a simple string.
     *
     * @param int $user_id user's id
     * @param string $user_email user's email
     * @param string $user_activation_hash user's mail verification hash string
     *
     * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
     */
    public function sendVerificationEmail($registrationtimestamp, $user_email, $user_activation_hash)
    {
        // send reset password token email                  
        //$mailContent = "<html><body><b>You are registering in our website.</b><p>Please click following link to activate your account <a href='http://localhost:8090/disman/useractivation/".$registrationtimestamp."/".$user_activation_hash."'>http://localhost:8090/disman/useractivation/".$registrationtimestamp."/".$user_activation_hash."</a>.</p></body></html>";		
        $dummy = array("_registration_timestamp", "_user_activation_hash");
        $real = array($registrationtimestamp, $user_activation_hash);
        $mailContent = str_replace($dummy, $real, LOGIN_SETTINGS["activation_mail_body"]);
        $result = Mail::send([$user_email],
            LOGIN_SETTINGS["activation_mail_from_email"], //'devteam@piapiastudio.web.id', 
            LOGIN_SETTINGS["activation_mail_from_name"], //'Piapia Dev - Mailtrap',
            LOGIN_SETTINGS["activation_mail_subject"], //'User Activation for Piapia',
            $mailContent
        );

        if ($result["success"]) {
            //$this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT." to ".$_POST["user_email"];    
            return true;
        } else {
            //$this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT." to ".$_POST["user_email"];    
            //$this->errors[] = $result["messages"];
            return false;
        }            
    }

    public function resendVerificationEmail($user_id)
    {
        $userdata = $this->model->find('id=? and user_active=0',[$user_id]);
        if ($userdata != null) {
            $registrationtimestamp = $user_id.'rEts'.$userdata->user_creation_timestamp;
            // send verification email        
            if (self::sendVerificationEmail($registrationtimestamp, $userdata->user_email, $userdata->user_activation_hash)) {
                $this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT;    
                return true;
            } else {           
                $this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT;    
                return false;
            }
        } else {
            $this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;    
            return false;
        }        
    }

    /**
     * checks the email/verification code combination and set the user's activation status to true in the database
     *
     * @param int $user_id user id
     * @param string $user_activation_verification_code verification token
     *
     * @return bool success status
     */
    public function verifyNewUser($user_creation_timestamp, $user_activation_verification_code)
    {
        $sql = "UPDATE users SET user_active = 1, user_activation_hash = NULL
                WHERE user_creation_timestamp = ? AND user_activation_hash = ? AND user_active = 0 LIMIT 1";
        $result = $this->model->execsql($sql, [$user_creation_timestamp, $user_activation_verification_code]);      

        if ($result == 1) {
            $this->messages[] = MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL;   
            return true;
        }

        $this->errors[] = MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL;
        return false;
    }

    public static function checkCaptcha($captcha) 
    {
        if (isset($_SESSION['registration']['captcha']))
        {
            return $captcha == $_SESSION['registration']['captcha'];
        } else {
            return false;
        }
    }

    private function doesUsernameAlreadyExist($user_name)
    {
        $userdata = $this->model->find('user_name=?',[$user_name]);
        if (isset($userdata->user_name)) {            
            return true;
        }
        return false;
    }

    private function doesEmailAlreadyExist($user_email)
    {
        $userdata = $this->model->find('user_email=?',[$user_email]);
        if (isset($userdata->user_email)) {
            return true;
        }
        return false;
    }

    private function getUserIdByUsername($user_name)
    {
        $userdata = $this->model->find('user_name=?',[$user_name]);
        if (isset($userdata->user_name)) {
            return $userdata->id;
        }
        return null;
    }

    
}
