<?php
namespace Rider\Domain;

use Rider\Model\Token as Model_Token;
class Token {

    public function getInfo($where,$field='*'){

        $model = new Model_Token();
        $info = $model->getInfo($where,$field);
        return $info;
    }

    public function add($data) {

        $model = new Model_Token();
        $result = $model->add($data);
        return $result;
    }

    public function up($where,$data) {

        $model = new Model_Token();
        $result = $model->up($where,$data);

        return $result;
    }

    public function del($where){

        $model = new Model_Token();
        $list = $model->del($where);

        return $list;
    }



}
