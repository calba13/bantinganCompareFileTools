<?php
namespace Bantingan;
/*      
    This is the application database ORM handler

    Bantingan Framework v0.3
    Copyright (C) 2020 by Susilo Nurcahyo
    susilonurcahyo@gmail.com    

    Some library are copyright to their respective owners.

    We use and love RedbeanPHP
    https://www.redbeanphp.com
    https://github.com/gabordemooij/redbean

    Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.   

    This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.
*/
use \RedBeanPHP\R as R;

class Model extends \RedBeanPHP\SimpleModel
{  
	public $selectedDB;
    public $dbType;
    public $dbConnection;

    public $tablename;

    public function __construct($_tablename = null)
    {           
        $this->dbConnection = DATABASE_SETTINGS;

        $tableName = explode("\\", get_called_class())[1];      
        $this->tablename = isset($_tablename)?$_tablename:lcfirst(str_replace("Model","",$tableName));      
        if ($this->tablename == "Base") {
            $this->tablename = null;
        }           

        // need to check if redbeans already started
        if (!isset($GLOBALS['redbeans'])) 
        {            
            $dbfreeze = APPLICATION_SETTINGS["RedBeanPHP_Freeze"]??false;
            foreach ($this->dbConnection as $key => $dbsettings) {   
                // check if no existing key
                if (!isset(R::$toolboxes[$key])) {
                    if ($dbsettings["type"] == "sqlite")
                    {
                        R::addDatabase($key,$dbsettings["type"].':'.$dbsettings["server"],$dbsettings["user"],$dbsettings["password"],$dbfreeze);    
                    } else if ($dbsettings["type"] == "postgresql" || $dbsettings["type"] == "mysql")    
                    {                                               
                        R::addDatabase($key,$dbsettings["type"].':host='.$dbsettings["server"].";dbname=".$dbsettings["database"],$dbsettings["user"],$dbsettings["password"],$dbfreeze);
                    } else if ($dbsettings["type"] == "sqlsrv")
                    {                                         
                        R::addDatabase($key,$dbsettings["type"].':Server='.$dbsettings["server"].";Database=".$dbsettings["database"],$dbsettings["user"],$dbsettings["password"],$dbfreeze);                        
                    } else {
                        R::addDatabase($key,$dbsettings["type"].':host='.$dbsettings["server"].";dbname=".$dbsettings["database"],$dbsettings["user"],$dbsettings["password"],$dbfreeze);
                    }
                } 
            }
                   
            $GLOBALS['redbeans'] = true;

            $this->setConnection();
        }
    }   

    public function __call($method,$arguments) {        
        if(method_exists($this, $method)) {
            $this->setConnection();                            
            return call_user_func_array(array($this,$method),$arguments);
        }
    }

    private function setConnection($selectedDB=null)
    {
        if (!isset($this->selectedDB))
        {
            $this->selectedDB = "default";
        }        
        $this->dbType = $this->dbConnection[$selectedDB??$this->selectedDB]["type"];        
        R::selectDatabase($selectedDB??$this->selectedDB);
    }

    private function close()
    {
        return R::close();
    }

    private function load($id, $tablename = null)
    {                       
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {          
            //$this->redbeans = R::load(strtolower($tablename), $id);
            return R::load(strtolower($tablename), $id);
        } 
        throw new \Exception('Cannot create object, table name is unknown.', 50);           
    }

    private function create($tablename = null)
    {               
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {         
            //$this->redbeans = R::dispense(strtolower($tablename));  
            return R::dispense(strtolower($tablename));  
        } 
        throw new \Exception('Cannot create object, table name is unknown.', 50);           
    } 

    private function loadorcreate($id, $tablename = null)
    {                       
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {        
            $redbeans = R::load(strtolower($tablename), $id);

            if (!isset($redbeans)) {
                $redbeans = R::dispense(strtolower($tablename));           
            }            
            return $redbeans; 
        } 
        throw new \Exception('Cannot create object, table name is unknown.', 50);           
    } 

    private function find($parameter, $parameterarray, $tablename=null)
    {                
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {            
            return R::findOne($tablename, $parameter, $parameterarray);            
        } 
        throw new \Exception('Cannot find object, table name is unknown.', 50);                           
    }

    private function findAll($parameter, $parameterarray, $tablename=null)
    {        
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) { 
            return R::findAll($tablename, $parameter, $parameterarray);
        }
        throw new \Exception('Cannot find object, table name is unknown.', 50);                   
    } 

    private function findlike($parameterarray, $tablename=null)
    {
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {          
            return R::findLike($tablename, $parameterarray);                                    
        } 
        throw new \Exception('Cannot find object, table name is unknown.', 50);                   
    } 

    private function findorcreate($parameterarray, $tablename=null)
    {
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        if (isset($tablename)) {      
            return R::findOrCreate($tablename, $parameterarray);            
        } 
        throw new \Exception('Cannot create object, table name is unknown.', 50);                   
    } 

    private function save($redbeansdata = null)
    {        
        if (isset($redbeansdata)) {                     
            return R::store($redbeansdata); 
        } 
        throw new \Exception('Cannot save data, object is empty.', 50);     
    }

    private function trash($redbeansdata = null)
    {
        if (isset($redbeansdata)) {         
            return R::trash($redbeansdata); 
        } 
        throw new \Exception('Cannot delete data, object is empty.', 50);
    }

    private function getall($query = null, $parameter = null) {  
    	if (isset($this->tablename)) {  
	        try {
	            if (!isset($query)) {
	                // limit protection
	                $limit = "";
	                if (isset(APPLICATION_SETTINGS["RedBeanPHP_Rowlimit"])) {
	                    if (APPLICATION_SETTINGS["RedBeanPHP_Rowlimit"] > 0) {
	                        $limit = $this->dbType =="postgresql"?"limit 1000":"limit 0,1000";                        
	                    }
	                }
	                $query = "select * from ".$this->tablename." ".$limit;
	            }            
	            return isset($parameter)?R::getAll($query, $parameter):R::getAll($query);   
	        }
	        catch(Exception $exc)
	        {
	            throw new Exception($exc->getMessage(), 50);            
	        }
	    } 

	    throw new \Exception('Cannot get object, table name is unknown.', 50);
    }

    private function getrow($query, $parameter=null) {           
        try {                                    
            return isset($parameter)?R::getRow($query, $parameter):R::getRow($query);
        }
        catch(Exception $exc)
        {
            throw new Exception($exc->getMessage(), 50);            
        }
    }

    private function getcell($query, $parameter=null) {    
        try {                        
            return isset($parameter)?R::getCell($query, $parameter):R::getCell($query);
        }
        catch(Exception $exc)
        {
            throw new Exception($exc->getMessage(), 50);            
        }
    }

    private function execsql($query, $parameter=null) {           
        try {                                    
            return isset($parameter)?R::exec($query, $parameter):R::exec($query);
        }
        catch(Exception $exc)
        {
            throw new Exception($exc->getMessage(), 50);            
        }
    }    

    private function getrowwithid($id) { 
        if (isset($this->tablename)) {
            return R::getRow("select * from $this->tablename where id = ?", [ $id ]);
        }           
        throw new \Exception('Cannot load data, table name is unknown.', 50);       
    }

    private function inspect($tablename = null) {
        if (!isset($tablename)) {
            $tablename = $this->tablename;
        }
        return R::inspect(strtolower($tablename));
    }

    private function updatemodel($olddata, $newdata) {             
        foreach ($newdata as $key => $value) {
            if ($key != "id") {
                $olddata->$key = $newdata->$key;
            }            
        }

        return $olddata;         
    }

    private function export($bean) {
        return R::export($bean);
    }

    private function exportAll($listbean) {
        return R::exportAll($listbean);
    }

    private function transaction($function) {
        return R::transaction($function);
    }

    private function begintrans() {
        R::begin();
    }

    private function committrans() {
        R::commit();
    }

    private function rollbacktrans() {
        R::rollback();
    }
}