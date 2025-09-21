<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use app\models\User;
use app\modules\api\components\JwtAuth;

/**
 * Authentication controller for API
 */
class AuthController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Rate limiting for login endpoint
        $behaviors['rateLimiter'] = [
            'class' => RateLimiter::class,
            'only' => ['login'],
            'user' => function() {
                return Yii::$app->request->userIP;
            },
            'request' => function() {
                return new \yii\web\Request();
            },
            'response' => function() {
                return Yii::$app->response;
            },
        ];
        
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'login' => ['POST'],
                'refresh' => ['POST'],
                'logout' => ['POST'],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Login action
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $email = $request->post('email');
        $password = $request->post('password');
        
        if (!$email || !$password) {
            throw new BadRequestHttpException('Email and password are required');
        }
        
        $user = User::findByEmail($email);
        if (!$user || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException('Invalid email or password');
        }
        
        if ($user->status !== User::STATUS_ACTIVE) {
            throw new UnauthorizedHttpException('Account is not active');
        }
        
        // Generate tokens
        $accessToken = JwtAuth::generateToken($user);
        $refreshToken = JwtAuth::generateRefreshToken($user);
        
        return [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => array_keys($user->getRoles()),
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => Yii::$app->params['jwt.expire'] ?? 3600,
            ],
        ];
    }
    
    /**
     * Refresh token action
     */
    public function actionRefresh()
    {
        $request = Yii::$app->request;
        $refreshToken = $request->post('refresh_token');
        
        if (!$refreshToken) {
            throw new BadRequestHttpException('Refresh token is required');
        }
        
        $user = JwtAuth::validateRefreshToken($refreshToken);
        if (!$user) {
            throw new UnauthorizedHttpException('Invalid refresh token');
        }
        
        // Generate new tokens
        $accessToken = JwtAuth::generateToken($user);
        $newRefreshToken = JwtAuth::generateRefreshToken($user);
        
        return [
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'expires_in' => Yii::$app->params['jwt.expire'] ?? 3600,
            ],
        ];
    }
    
    /**
     * Logout action
     */
    public function actionLogout()
    {
        // In a real application, you might want to blacklist the token
        // For now, we'll just return success
        return [
            'success' => true,
            'message' => 'Logged out successfully',
        ];
    }
}
