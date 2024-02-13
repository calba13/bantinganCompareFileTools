<?php
namespace Models;

use Bantingan\Model;

class TabledemoModel extends Model
{
    // function with default value
    public function getdata($isactvice="notactive") {   
        $parameter = ($isactvice == "active")?1:0;
        // select * from tabledemo where isactive=1 order by id
        $listdataactive = $this->findAll("isactive=? order by id",[ $parameter ]);
        return $this->exportAll($listdataactive); // use exportAll to convert redbean listdata to array as datatables grid requirements
    }

    public function updatedata($postdata, $login) {                   
        // get existing data if exist, or create new        
        $data = $this->loadorcreate($postdata["id"]);
        // add audit data created        
        if (count($data) == 0) {
            $data->createdt = date("Y-m-d H:i:s");
            $data->createby = $login; // get login username from session
        }
        // assign new value to each column
        $data->compcode = $postdata["compcode"];
        // sample of assign value based on other value
        $data->compname = $postdata["compcode"] == "001" ? "Company 1":"Company 2";
        $data->branchcode = $postdata["branchcode"];
        $data->branchname = $postdata["branchname"];
        $data->address = $postdata["address"];
        $data->isactive = $postdata["isactive"];
        $data->username = $postdata["username"];
        $data->password = $postdata["password"];
        $data->salestarget = $postdata["salestarget"];
        $data->salesamount = $postdata["salesamount"];    
        // add audit data updated
        $data->updatedt = date("Y-m-d H:i:s");
        $data->createby = $login; // get login username from session
                
        // save data
        return $this->save($data);
    }
}