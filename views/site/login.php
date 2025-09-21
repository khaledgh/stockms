<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login';
?>

<div class="site-login">
    <div class="card shadow-lg border-0" style="max-width: 400px; margin: 2rem auto;">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-3 fw-bold text-primary">Stock MS</h1>
                <p class="text-muted">Sign in to your account</p>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label fw-semibold'],
                    'inputOptions' => ['class' => 'form-control form-control-lg'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                ],
            ]); ?>

            <?= $form->field($model, 'email')->textInput([
                'autofocus' => true,
                'placeholder' => 'Enter your email address'
            ]) ?>

            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => 'Enter your password'
            ]) ?>

            <div class="mb-3">
                <?= $form->field($model, 'rememberMe')->checkbox([
                    'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                    'labelOptions' => ['class' => 'form-check-label'],
                    'inputOptions' => ['class' => 'form-check-input'],
                ]) ?>
            </div>

            <div class="d-grid">
                <?= Html::submitButton('Sign In', [
                    'class' => 'btn btn-primary btn-lg',
                    'name' => 'login-button'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Default login: admin@stockms.com / admin123
                </small>
            </div>
        </div>
    </div>
</div>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.site-login .card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.form-control:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
}
</style>
