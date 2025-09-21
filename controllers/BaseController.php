<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;

/**
 * Base controller for backend
 */
class BaseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Set layout
        $this->layout = 'main';

        return true;
    }

    /**
     * Check permission
     */
    protected function checkPermission($permission)
    {
        if (!Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException('You do not have permission to perform this action.');
        }
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message)
    {
        Yii::$app->session->setFlash($type, $message);
    }

    /**
     * Return JSON response
     */
    public function asJson($data)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $data;
    }
}
