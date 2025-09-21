<?php

namespace app\modules\api\components;

use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\models\User;

/**
 * JWT Authentication filter
 */
class JwtAuth extends AuthMethod
{
    /**
     * {@inheritdoc}
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            
            try {
                $secret = Yii::$app->params['jwt.secret'];
                $decoded = JWT::decode($token, new Key($secret, 'HS256'));
                
                // Check if token is expired
                if ($decoded->exp < time()) {
                    throw new UnauthorizedHttpException('Token expired');
                }
                
                // Find user
                $identity = User::findOne($decoded->uid);
                if ($identity && $identity->status === User::STATUS_ACTIVE) {
                    return $identity;
                }
            } catch (\Exception $e) {
                // Invalid token
            }
        }
        
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', 'Bearer');
    }
    
    /**
     * Generate JWT token
     */
    public static function generateToken($user)
    {
        $secret = Yii::$app->params['jwt.secret'];
        $expire = Yii::$app->params['jwt.expire'] ?? 3600;
        
        $payload = [
            'uid' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + $expire,
        ];
        
        return JWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($user)
    {
        $secret = Yii::$app->params['jwt.secret'];
        $expire = Yii::$app->params['jwt.refresh_expire'] ?? 86400;
        
        $payload = [
            'uid' => $user->id,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + $expire,
        ];
        
        return JWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * Validate refresh token
     */
    public static function validateRefreshToken($token)
    {
        try {
            $secret = Yii::$app->params['jwt.secret'];
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            if ($decoded->exp < time() || $decoded->type !== 'refresh') {
                return false;
            }
            
            return User::findOne($decoded->uid);
        } catch (\Exception $e) {
            return false;
        }
    }
}
