<?php

namespace app\controllers;


use Yii;
// use yii\filters\AccessControl;
use yii\web\Controller;


class MyController extends Controller
{
    public function actionIndex(){
        // Yii::warning('abcdef', 'ak47');
        return 'hello';
    }
    public function action(){
        return 'hi';
    }
}

