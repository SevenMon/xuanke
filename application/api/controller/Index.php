<?php
namespace app\api\controller;

class Index
{
    public function index()
    {
        echo getTimeWeek(1569130034);
        echo date('w',1569130034);
    }
}
