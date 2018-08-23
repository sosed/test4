<?php

namespace api\modules\v1\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property string $id
 * @property string $username
 * @property string $email
 * @property string $encrypted_password
 * @property string $access_token
 * @property string $updated_at
 * @property string $created_at
 *
 * @property UserAnswer[] $answers
 */
class User extends ActiveRecord implements IdentityInterface
{
    public function extraFields()
    {
        return ['answers'];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return false;
    }

    /**
     * @var string Unencrypted password
     */
    public $password;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // Remove fields that contain sensitive information
        unset($fields['encrypted_password'], $fields['access_token']);

        // Only the current logged user can see it's own access_token
        $identity = Yii::$app->user->identity;
        if (!empty($identity) && ($identity->getId() == $this->id))
            $fields['access_token'] = 'access_token';

        $fields['answers'] = function () {
            return $this->answers;
        };
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'email', 'password'], 'required', 'on' => ['create', 'update']],
            [['name', 'email'], 'required', 'on' => 'update'],
            [['updated_at', 'created_at'], 'safe'],
            [['username', 'email'], 'string', 'max' => 255],
            [['password'], 'string', 'min' => 5, 'max' => 32],
            [['encrypted_password'], 'string', 'max' => 60],
            [['email', 'name'], 'unique'],
            [['email'], 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Login',
            'email' => 'Email',
            'password' => 'Password',
            'encrypted_password' => 'Encrypted Password',
            'access_token' => 'Access Token',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!empty($this->password))
            $this->encrypted_password = Yii::$app->getSecurity()->generatePasswordHash($this->password);

        if ($this->scenario == 'create') {
            $this->id = uniqid();
            $this->created_at = date('Y-m-d H:i:s');
            $this->access_token = Yii::$app->getSecurity()->generateRandomString();
        }

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(UserAnswer::className(), ['user_id' => 'id']);
    }
}
