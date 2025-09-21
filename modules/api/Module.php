<?php

namespace app\modules\api;

use Yii;

/**
 * API module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\api\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Set JSON response format
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Configure CORS
        Yii::$app->response->headers->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->add('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        Yii::$app->response->headers->add('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle OPTIONS requests
        if (Yii::$app->request->method === 'OPTIONS') {
            Yii::$app->response->statusCode = 200;
            Yii::$app->end();
        }
    }
}
