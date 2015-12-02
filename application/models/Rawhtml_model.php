<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11/10/2015
 * Time: 4:12 PM
 */

class Rawhtml_model extends CI_Model{

    public  $html,
            $url,
            $type;

    public function __construct()
    {
        $this->load->database();
    }



}