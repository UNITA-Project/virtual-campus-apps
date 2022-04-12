<?php

/*
* LimeSurvey
* Copyright (C) 2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
*/

/**
 * Class User
 *
 * @property integer $uid User ID - primary key
 * @property string $users_name Users username
 * @property string $password User's password hash
 * @property string $full_name User's full name
 * @property integer $parent_id
 * @property string $lang User's preferred language: (auto: automatic | languagecodes eg 'en')
 * @property string $email User's e-mail address
 * @property string $htmleditormode User's prefferred HTML editor mode:(default|inline|popup|none)
 * @property string $templateeditormode User's prefferred template editor mode:(default|full|none)
 * @property string $questionselectormode User's prefferred Question type selector:(default|full|none)
 * @property string $one_time_pw User's one-time-password hash
 * @property integer $dateformat Date format type 1-12
 * @property string $created Time created Time user was created as 'YYYY-MM-DD hh:mm:ss'
 * @property string $modified Time modified Time created Time user was modified as 'YYYY-MM-DD hh:mm:ss'
 * @property string $validation_key  used for email link to reset or create a password for a survey participant
 *                                   Link is send when user is created or password has been reset
 * @property string $validation_key_expiration datetime when the validation key expires
 * @property string $last_forgot_email_password datetime when user send email for forgot pw the last time (prevent bot)
 *
 * @property Permission[] $permissions
 * @property User $parentUser Parent user
 * @property string $parentUserName  Parent user's name
 * @property string $last_login
 * @property Permissiontemplates[] $roles
 * @property UserGroup[] $groups
 */
class User extends LSActiveRecord
{
    /** @var int maximum time the validation_key is valid*/
    const MAX_EXPIRATION_TIME_IN_HOURS = 48;

    /** @var int maximum days the validation key is valid */
    const MAX_EXPIRATION_TIME_IN_DAYS = 2;

    /** @var int  maximum length for the validation_key*/
    const MAX_VALIDATION_KEY_LENGTH = 38;

    /**
     * @var string $lang Default value for user language
     */
    public $lang = 'auto';
    public $searched_value;

    /**
     * @inheritdoc
     * @return User
     */
    public static function model($className = __CLASS__)
    {
        /** @var self $model */
        $model = parent::model($className);
        return $model;
    }

    /** @inheritdoc */
    public function tableName()
    {
        return '{{users}}';
    }

    /** @inheritdoc */
    public function primaryKey()
    {
        return 'uid';
    }
    /** @inheritdoc */
    public function relations()
    {
        return array(
            'permissions' => array(self::HAS_MANY, 'Permission', 'uid'),
            'parentUser' => array(self::HAS_ONE, 'User', array('uid' => 'parent_id')),
            'settings' => array(self::HAS_MANY, 'SettingsUser', 'uid'),
            'groups' => array(self::MANY_MANY, 'UserGroup', '{{user_in_groups}}(uid,ugid)'),
            'roles' => array(self::MANY_MANY, 'Permissiontemplates', '{{user_in_permissionrole}}(uid,ptid)')
        );
    }

    /** @inheritdoc */
    public function rules()
    {
        return array(
            array('users_name, password, email', 'required'),
            array('users_name', 'unique'),
            array('users_name', 'length','max' => 64),
            array('full_name', 'length','max' => 50),
            array('email', 'email'),
            array('full_name', 'LSYii_Validators'), // XSS if non super-admin
            array('parent_id', 'default', 'value' => 0),
            array('parent_id', 'numerical', 'integerOnly' => true),
            array('lang', 'default', 'value' => Yii::app()->getConfig('defaultlang')),
            array('lang', 'LSYii_Validators', 'isLanguage' => true),
            array('htmleditormode', 'default', 'value' => 'default'),
            array('htmleditormode', 'in', 'range' => array('default', 'inline', 'popup', 'none'), 'allowEmpty' => true),
            array('questionselectormode', 'default', 'value' => 'default'),
            array('questionselectormode', 'in', 'range' => array('default', 'full', 'none'), 'allowEmpty' => true),
            array('templateeditormode', 'default', 'value' => 'default'),
            array('templateeditormode', 'in', 'range' => array('default', 'full', 'none'), 'allowEmpty' => true),
            array('dateformat', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),

            // created as datetime default current date in create scenario ?
            // modifier as datetime default current date ?
            array('validation_key', 'length','max' => self::MAX_VALIDATION_KEY_LENGTH),
            //todo: write a rule for date (can also be null)
            //array('lastForgotPwEmail', 'numerical', 'integerOnly' => true, 'allowEmpty' => true),
        );
    }

    public function attributeLabels()
    {
        return [
            'uid' => gT('User ID'),
            'users_name' => gT('Username'),
            'password' => gT('Password'),
            'full_name' => gT('Full name'),
            'parent_id' => gT('Parent user'),
            'lang' => gT('Language'),
            'email' => gT('Email'),
            'htmleditormode' => gT('Editor mode'),
            'templateeditormode' => gT('Template editor mode'),
            'questionselectormode' => gT('Question selector mode'),
            'one_time_pw' => gT('One-time password'),
            'dateformat' => gT('Date format'),
            'created' => gT('Created at'),
            'modified' => gT('Modified at'),
            'last_login' => gT('Last recorded login'),
        ];
    }

    /**
     * @return string
     */
    public function getSurveysCreated()
    {
        $noofsurveys = Survey::model()->countByAttributes(array("owner_id" => $this->uid));
        return $noofsurveys;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        $dateFormat = getDateFormatData(Yii::app()->session['dateformat']);
        return $dateFormat['phpdate'];
    }

    /**
     * @todo Not used?
     */
    public function getFormattedDateCreated()
    {
        $dateCreated = $this->created;
        $date = new DateTime($dateCreated);
        return $date->format($this->dateformat);
    }

    /**
     * Creates new user
     *
     * @access public
     * @param string $new_user
     * @param string $new_pass
     * @param string $new_full_name
     * @param string $new_email
     * @param int $parent_user
     * @return integer|boolean User ID if success
     */
    public static function insertUser($new_user, $new_pass, $new_full_name, $parent_user, $new_email)
    {
        $oUser = new self();
        $oUser->users_name = $new_user;
        $oUser->setPassword($new_pass);
        $oUser->full_name = $new_full_name;
        $oUser->parent_id = $parent_user;
        $oUser->lang = 'auto';
        $oUser->email = $new_email;
        $oUser->created = date('Y-m-d H:i:s');
        $oUser->modified = date('Y-m-d H:i:s');
        if ($oUser->save()) {
            return $oUser->uid;
        } else {
            return false;
        }
    }

    /**
     * Finds user by username
     * @param string $sUserName
     * @return User
     */
    public static function findByUsername($sUserName)
    {
        /** @var User $oUser */
        $oUser = User::model()->findByAttributes(array(
            'users_name' => $sUserName
        ));
        return $oUser;
    }

    /**
     * Updates user password hash
     *
     * @param int $iUserID The User ID
     * @param string $sPassword The clear text password
     * @return int number of rows updated
     */
    public static function updatePassword($iUserID, $sPassword)
    {
        return User::model()->updateByPk($iUserID, array('password' => password_hash($sPassword, PASSWORD_DEFAULT)));
    }

    /**
     * Set user password with hash
     *
     * @param string $sPassword The clear text password
     * @return \User
     */
    public function setPassword($sPassword, $save = false)
    {
        $this->password = password_hash($sPassword, PASSWORD_DEFAULT);
        if ($save) {
            $this->save();
        }
        return $this; // Return current object
    }

    /**
     * Check if password is OK for current \User
     *
     * @param string $sPassword The clear password
     * @return boolean
     */
    public function checkPassword($sPassword)
    {
        // password can not be empty
        if (empty($this->password)) {
            return false;
        }
        // Password is OK
        if (password_verify($sPassword, $this->password)) {
            return true;
        }
        // It can be an old password
        if ($this->password == hash('sha256', $sPassword)) {
            $this->setPassword($sPassword, true);
            return true;
        }
        return false;
    }

    /**
     * @todo document me
     */
    public function checkPasswordStrength($password)
    {
        $settings = Yii::app()->getConfig("passwordValidationRules");
        $length = strlen($password);
        $lowercase = preg_match_all('@[a-z]@', $password);
        $uppercase = preg_match_all('@[A-Z]@', $password);
        $number    = preg_match_all('@[0-9]@', $password);
        $specialChars = preg_match_all('@[^\w]@', $password);

        $error = "";
        if ((int) $settings['min'] > 0) {
            if ($length < $settings['min']) {
                $error = sprintf(ngT('Password must be at least %d character long|Password must be at least %d characters long', $settings['min']), $settings['min']);
            }
        }
        if ((int) $settings['max'] > 0) {
            if ($length > $settings['max']) {
                $error = sprintf(ngT('Password must be at most %d character long|Password must be at most %d characters long', $settings['max']), $settings['max']);
            }
        }
        if ((int) $settings['lower'] > 0) {
            if ($lowercase < $settings['lower']) {
                $error = sprintf(ngT('Password must include at least %d lowercase letter|Password must include at least %d lowercase letters', $settings['lower']), $settings['lower']);
            }
        }
        if ((int) $settings['upper'] > 0) {
            if ($uppercase < $settings['upper']) {
                $error = sprintf(ngT('Password must include at least %d uppercase letter|Password must include at least %d uppercase letters', $settings['upper']), $settings['upper']);
            }
        }
        if ((int) $settings['numeric'] > 0) {
            if ($number < $settings['numeric']) {
                $error = sprintf(ngT('Password must include at least %d number|Password must include at least %d numbers', $settings['numeric']), $settings['numeric']);
            }
        }
        if ((int) $settings['symbol'] > 0) {
            if ($specialChars < $settings['symbol']) {
                $error = sprintf(ngT('Password must include at least %d special character|Password must include at least %d special characters', $settings['symbol']), $settings['symbol']);
            }
        }

        return($error);
    }

    /**
     * Checks if
     *  -- password strength
     *  -- oldpassword is correct
     *  -- oldpassword and newpassword are identical
     *  -- newpassword and repeatpassword are identical
     *  -- newpassword is not empty
     *
     * @param $newPassword
     * @param $oldPassword
     * @param $repeatPassword
     * @return string empty string means everything is ok, otherwise error message is returned
     */
    public function validateNewPassword($newPassword, $oldPassword, $repeatPassword)
    {
        $errorMsg = '';

        if (!empty($newPassword)) {
            $errorMsg = $this->checkPasswordStrength($newPassword);
        }

        if ($errorMsg === '') {
            if (!$this->checkPassword($oldPassword)) {
                // Always check password
                $errorMsg = gT("Your new password was not saved because the old password was wrong.");
            } elseif (trim($oldPassword) === trim($newPassword)) {
                //First test if old and new password are identical => no need to save it (or ?)
                $errorMsg = gT("Your new password was not saved because it matches the old password.");
            } elseif (trim($newPassword) !== trim($repeatPassword)) {
                //Then test the new password and the repeat password for identity
                $errorMsg = gT("Your new password was not saved because the passwords did not match.");
                //Now check if the old password matches the old password saved
            } elseif (empty(trim($newPassword))) {
                $errorMsg = gT("The new password can not be empty.");
            }
        }

        return $errorMsg;
    }

    /**
     * @todo document me
     */
    public function getPasswordHelpText()
    {
        $settings =  Yii::app()->getConfig("passwordValidationRules");
        $txt = gT('A password must meet the following requirements: ');
        if ((int) $settings['min'] > 0) {
            $txt .= sprintf(ngT('At least %d character long.|At least %d characters long.', $settings['min']), $settings['min']) . ' ';
        }
        if ((int) $settings['max'] > 0) {
            $txt .= sprintf(ngT('At most %d character long.|At most %d characters long.', $settings['max']), $settings['max']) . ' ';
        }
        if ((int) $settings['min'] > 0 && (int) $settings['max'] > 0) {
            if ($settings['min'] == $settings['max']) {
                $txt .= sprintf(ngT('Exactly %d character long.|Exactly %d characters long.', $settings['min']), $settings['min']) . ' ';
            } elseif ($settings['min'] < $settings['max']) {
                $txt .= sprintf(gT('Between %d and %d characters long.'), $settings['min'], $settings['max']) . ' ';
            }
        }
        if ((int) $settings['lower'] > 0) {
            $txt .= sprintf(ngT('At least %d lower case letter.|At least %d lower case letters.', $settings['lower']), $settings['lower']) . ' ';
        }
        if ((int) $settings['upper'] > 0) {
            $txt .= sprintf(ngT('At least %d upper case letter.|At least %d upper case letters.', $settings['upper']), $settings['upper']) . ' ';
        }
        if ((int) $settings['numeric'] > 0) {
            $txt .= sprintf(ngT('At least %d number.|At least %d numbers.', $settings['numeric']), $settings['numeric']) . ' ';
        }
        if ((int) $settings['symbol'] > 0) {
            $txt .= sprintf(ngT('At least %d special character.|At least %d special characters.', $settings['symbol']), $settings['symbol']) . ' ';
        }
        return($txt);
    }

    /**
     * Adds user record
     *
     * @access public
     * @param array $data
     * @deprecated : just don't use it
     * @return string
     */
    public function insertRecords($data)
    {
        return $this->getDb()->insert('users', $data);
    }

    /**
     * Returns User ID common in Survey_Permissions and User_in_groups
     * @param $surveyid
     * @param $postusergroupid
     * @return CDbDataReader
     */
    public function getCommonUID($surveyid, $postusergroupid)
    {
        $query2 = "SELECT b.uid FROM (SELECT uid FROM {{permissions}} WHERE entity_id = :surveyid AND entity = 'survey') AS c RIGHT JOIN {{user_in_groups}} AS b ON b.uid = c.uid WHERE c.uid IS NULL AND b.ugid = :postugid";
        return Yii::app()->db->createCommand($query2)->bindParam(":surveyid", $surveyid, PDO::PARAM_INT)->bindParam(":postugid", $postusergroupid, PDO::PARAM_INT)->query(); //Checked
    }

    /**
     * @todo document me
     */
    public function getGroupList()
    {
        $collector = array_map(function ($oUserInGroup) {
            return $oUserInGroup->name;
        }, $this->groups);
        return join(', ', $collector);
    }

    /**
     * Return all super admins in the system
     * @return User[]
     */
    public function getSuperAdmins()
    {
        // TODO should be static
        $criteria = new CDbCriteria();
        $criteria->join = ' JOIN {{permissions}} AS p ON p.uid = t.uid';
        $criteria->addCondition('p.permission = \'superadmin\'');
        /** @var User[] $users */
        $users = $this->findAll($criteria);
        return $users;
    }

    /**
     * Gets the buttons for the GridView
     * @return string
     * TODO: this seems to not be used anymore - see getManagementButtons()
     */
    public function getButtons()
    {
        $editUser = "";
        $deleteUser = "";
        $setPermissionsUser = "";
        $setTemplatePermissionUser = "";
        $changeOwnership = "";

        $editUrl = Yii::app()->getController()->createUrl('admin/user/sa/modifyuser');
        $setPermissionsUrl = Yii::app()->getController()->createUrl('admin/user/sa/setuserpermissions');
        $setTemplatePermissionsUrl = Yii::app()->getController()->createUrl('admin/user/sa/setusertemplates');
        $changeOwnershipUrl = Yii::app()->getController()->createUrl('admin/user/sa/setasadminchild');

        $oUser = self::model()->findByPK($this->uid);
        if ($this->uid == Yii::app()->user->getId()) {
            // Edit self
            $editUser = "<button
                data-toggle='tooltip'
                title='" . gT("Edit this user") . "'
                data-url='" . $editUrl . "'
                data-uid='" . $this->uid . "'
                data-user='" . htmlspecialchars($oUser['full_name']) . "'
                data-action='modifyuser'
                class='btn btn-default btn-sm green-border action_usercontrol_button'>
                    <span class='fa fa-pencil text-success'></span>
                </button>";
        } else {
            if (
                Permission::model()->hasGlobalPermission('superadmin', 'read')
                || $this->uid == Yii::app()->session['loginID']
                || (Permission::model()->hasGlobalPermission('users', 'update')
                    && $this->parent_id == Yii::app()->session['loginID']
                )
            ) {
                $editUser = "<button data-toggle='tooltip' data-url='" . $editUrl . "' data-user='" . htmlspecialchars($oUser['full_name']) . "' data-uid='" . $this->uid . "' data-action='modifyuser' title='" . gT("Edit this user") . "' type='submit' class='btn btn-default btn-sm green-border action_usercontrol_button'><span class='fa fa-pencil text-success'></span></button>";
            }

            if (
                ((Permission::model()->hasGlobalPermission('superadmin', 'read') &&
                $this->uid != Yii::app()->session['loginID']) ||
                (Permission::model()->hasGlobalPermission('users', 'update') &&
                $this->parent_id == Yii::app()->session['loginID'])) && !Permission::isForcedSuperAdmin($this->uid)
            ) {
                //'admin/user/sa/setuserpermissions'
                    $setPermissionsUser = "<button data-toggle='tooltip' data-user='" . htmlspecialchars($this->full_name) . "' data-url='" . $setPermissionsUrl . "' data-uid='" . $this->uid . "' data-action='setuserpermissions' title='" . gT("Set global permissions for this user") . "' type='submit' class='btn btn-default btn-xs action_usercontrol_button'><span class='icon-security text-success'></span></button>";
            }
            if (
                (Permission::model()->hasGlobalPermission('superadmin', 'read')
                || Permission::model()->hasGlobalPermission('templates', 'read'))
                && !Permission::isForcedSuperAdmin($this->uid)
            ) {
                //'admin/user/sa/setusertemplates')
                    $setTemplatePermissionUser = "<button type='submit' data-user='" . htmlspecialchars($this->full_name) . "' data-url='" . $setTemplatePermissionsUrl . "' data-uid='" . $this->uid . "' data-action='setusertemplates' data-toggle='tooltip' title='" . gT("Set template permissions for this user") . "' class='btn btn-default btn-xs action_usercontrol_button'><span class='icon-templatepermissions text-success'></span></button>";
            }
            if (
                (Permission::model()->hasGlobalPermission('superadmin', 'read')
                    || (Permission::model()->hasGlobalPermission('users', 'delete')
                    && $this->parent_id == Yii::app()->session['loginID'])) && !Permission::isForcedSuperAdmin($this->uid)
            ) {
                $deleteUrl = Yii::app()->getController()->createUrl('admin/user/sa/deluser', array(
                    "action" => "deluser",
                    "uid" => $this->uid,
                    "user" => htmlspecialchars(Yii::app()->user->getId())
                ));

                    //'admin/user/sa/deluser'
                $deleteUser = "<span style='margin:0;padding:0;display: inline-block;' data-toggle='tooltip' title='" . gT('Delete this user') . "'>
                    <button
                        id='delete_user_" . $this->uid . "'
                        data-toggle='modal'
                        data-target='#confirmation-modal'
                        data-url='" . $deleteUrl . "'
                        data-uid='" . $this->uid . "'
                        data-user='" . htmlspecialchars($this->full_name) . "'
                        data-action='deluser'
                        data-onclick='triggerRunAction($(\"#delete_user_" . $this->uid . "\"))'
                        data-message='" . gT("Do you want to delete this user?") . "'
                        class='btn btn-default btn-sm'>
                            <span class='fa fa-trash text-danger'></span>
                        </button>
                    </span>";
            }
            if (
                Permission::isForcedSuperAdmin(Yii::app()->session['loginID'])
                    && $this->parent_id != Yii::app()->session['loginID']
            ) {
                //'admin/user/sa/setasadminchild'
                $changeOwnership = "<button data-toggle='tooltip' data-url='" . $changeOwnershipUrl . "' data-user='" . htmlspecialchars($oUser['full_name']) . "' data-uid='" . $this->uid . "' data-action='setasadminchild' title='" . gT("Take ownership") . "' class='btn btn-default btn-xs action_usercontrol_button' type='submit'><span class='icon-takeownership text-success'></span></button>";
            }
        }
        return "<div>"
            . $editUser
            . $deleteUser
            . $setPermissionsUser
            . $setTemplatePermissionUser
            . $changeOwnership
            . "</div>";
    }

    /**
     * Gets the buttons for the GridView
     * @return string
     */
    public function getManagementButtons()
    {
        $detailUrl = Yii::app()->getController()->createUrl('userManagement/viewUser', ['userid' => $this->uid]);
        $editUrl = Yii::app()->getController()->createUrl('userManagement/addEditUser', ['userid' => $this->uid]);
        $setPermissionsUrl = Yii::app()->getController()->createUrl('userManagement/userPermissions', ['userid' => $this->uid]);
        $setRoleUrl = Yii::app()->getController()->createUrl('userManagement/addRole', ['userid' => $this->uid]);
        $changeOwnershipUrl = Yii::app()->getController()->createUrl('userManagement/takeOwnership');
        $setTemplatePermissionsUrl = Yii::app()->getController()->createUrl('userManagement/userTemplatePermissions', ['userid' => $this->uid]);
        $deleteUrl = Yii::app()->getController()->createUrl('userManagement/deleteConfirm', ['userid' => $this->uid, 'user' => $this->full_name]);

        $iconBtnRow = "<div class='icon-btn-row'>";
        $iconBtnRowEnd = "</div>";

        $userDetail = ""
            . "<button 
                data-toggle='tooltip' 
                title='" . gT("User details") . "'    
                class='btn btn-sm btn-default UserManagement--action--openmodal UserManagement--action--userdetail' 
                data-href='" . $detailUrl . "'
                >
                <i class='fa fa-search'></i>
                </button>";

        $editPermissionButton = ""
            . "<button 
                data-toggle='tooltip' 
                title='" . gT("Edit permissions") . "'  
                class='btn btn-sm btn-default UserManagement--action--openmodal UserManagement--action--permissions' 
                data-href='" . $setPermissionsUrl . "'
                data-modalsize='modal-lg'
                ><i class='fa fa-lock'></i></button>";
        $addRoleButton = ""
            . "<button 
                data-toggle='tooltip' 
                title='" . gT("User role") . "'
                class='btn btn-sm btn-default UserManagement--action--openmodal UserManagement--action--addrole' 
                data-href='" . $setRoleUrl . "'><i class='fa fa-users'></i></button>";
        $editUserButton = ""
            . "<button 
                data-toggle='tooltip' 
                title='" . gT("Edit user") . "'
                class='btn btn-sm btn-default UserManagement--action--openmodal UserManagement--action--edituser green-border' 
                data-href='" . $editUrl . "'><i class='fa fa-pencil'></i></button>";
        $editTemplatePermissionButton = ""
            . "<button 
        data-toggle='tooltip' 
        title='" . gT("Template permissions") . "'
        class='btn btn-sm btn-default UserManagement--action--openmodal UserManagement--action--templatepermissions' 
        data-href='" . $setTemplatePermissionsUrl . "'><i class='fa fa-paint-brush'></i></button>";
        $takeOwnershipButton = ""
        . "<button 
                id='UserManagement--takeown-" . $this->uid . "'
                class='btn btn-sm btn-default' 
                data-toggle='modal' 
                data-target='#confirmation-modal' 
                data-url='" . $changeOwnershipUrl . "' 
                data-userid='" . $this->uid . "' 
                data-user='" . $this->full_name . "' 
                data-action='deluser' 
                data-onclick='LS.UserManagement.triggerRunAction(\"#UserManagement--takeown-" . $this->uid . "\")' 
                data-message='" . gT('Do you want to take ownerschip of this user?') . "'>
                <span data-toggle='tooltip' title='" . gT("Take ownership") . "'>
                    <i class='fa fa-hand-rock-o'></i>
                </span>    
              </button>";
        $deleteUserButton = ""
            . "<button 
                id='UserManagement--delete-" . $this->uid . "' 
                class='btn btn-default btn-sm UserManagement--action--openmodal UserManagement--action--delete red-border'
                data-toggle='tooltip' 
                title='" . gT("Delete User") . "' 
                data-href='" . $deleteUrl . "'><i class='fa fa-trash text-danger'></i></button>";

        // Superadmins can do everything, no need to do further filtering
        if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            //Prevent users to modify original superadmin. Original superadmin can change his password on his account setting!
            if ($this->uid == 1) {
                $editUserButton = "";
            }

            // and Except deleting themselves and changing permissions when they are forced superadmin
            if (Permission::isForcedSuperAdmin($this->uid) || $this->uid == Yii::app()->user->getId()) {
                return implode("", [$iconBtnRow, $userDetail, $editUserButton, $iconBtnRowEnd]);
            }
            return implode("", [
                $iconBtnRow,
                $editUserButton,
                $editPermissionButton,
                $addRoleButton,
                "\n",
                $userDetail,
                $editTemplatePermissionButton,
                $this->parent_id != Yii::app()->session['loginID'] ? $takeOwnershipButton : '',
                $deleteUserButton,
                $iconBtnRowEnd]);
        }

        $buttonArray = [];
        $buttonArray[] = $iconBtnRow;
        // Check if user can see detail (must have probably but better save than sorry)
        if (
            $this->uid == Yii::app()->user->getId()                             //You can see yourself of course
            || (
                Permission::model()->hasGlobalPermission('users', 'update')     //Global permission to view users given
                && $this->parent_id == Yii::app()->session['loginID']           //AND User is owned or created by you
            )
        ) {
            $buttonArray[] = $userDetail;
        }
        // Check if user is editable
        if (
            $this->uid == Yii::app()->user->getId()                             //One can edit onesself of course
            || (
                Permission::model()->hasGlobalPermission('users', 'update')     //Global permission to edit users given
                && $this->parent_id == Yii::app()->session['loginID']           //AND User is owned by admin
            )
        ) {
            $buttonArray[] = $editUserButton;
        }

        //Check if user can set permissions
        if (
            ($this->uid != Yii::app()->session['loginID'])                      //Can't change your own permissions
            &&  (
                Permission::model()->hasGlobalPermission('users', 'update')     //Global permission to edit users given
                && $this->parent_id == Yii::app()->session['loginID']           //AND User is owned by admin
            )
            && !Permission::isForcedSuperAdmin($this->uid)                      //Can't change forced Superadmins permissions
        ) {
            $buttonArray[] = $editPermissionButton;
        }

        //Check if user can take ownership
        if (
            Permission::isForcedSuperAdmin(Yii::app()->session['loginID'])      //Is not a forced superadmin
            && $this->parent_id != Yii::app()->session['loginID']               //AND is not yet owned by one
        ) {
            $buttonArray[] = $takeOwnershipButton;
        }

        //Check if user can delete
        if (
            ($this->uid != Yii::app()->session['loginID'])                      //One cant delete onesself
            && (
                Permission::model()->hasGlobalPermission('users', 'delete')     //Global permission to delete users
                && $this->parent_id == Yii::app()->session['loginID']           //AND User is owned by admin
            )
            && !Permission::isForcedSuperAdmin($this->uid)                      //Can't delete forced superadmins, ever
        ) {
            $buttonArray[] = $deleteUserButton;
        }
        $buttonArray[] = $iconBtnRowEnd;

        return implode("", $buttonArray);
    }

    public function getParentUserName()
    {
        if ($this->parentUser) {
            return $this->parentUser->users_name;
        }
        // root user, no parent
        return null;
    }

    public function getRoleList()
    {
        $list = array_map(
            function ($oRoleMapping) {
                return $oRoleMapping->name;
            },
            $this->roles
        );
        return join(', ', $list);
    }

    /**
     * @todo Not used?
     */
    public function getLastloginFormatted()
    {
        $lastLogin = $this->last_login;
        if ($lastLogin == null) {
            return '---';
        }

        $date = new DateTime($lastLogin);
        return $date->format($this->dateformat) . ' ' . $date->format('H:i');
    }

    public function getManagementCheckbox()
    {
        return "<input type='checkbox' class='usermanagement--selector-userCheckbox' name='selectedUser[]' value='" . $this->uid . "'>";
    }
    /**
     * @return array
     */
    public function getManagementColums()
    {
        // TODO should be static
        $cols = array(
            array(
                'name' => 'managementCheckbox',
                'type' => 'raw',
                'header' => "<input type='checkbox' id='usermanagement--action-toggleAllUsers' />",
                'filter' => false
            ),
            array(
                "name" => 'managementButtons',
                "type" => 'raw',
                "header" => gT("Action"),
                'filter' => false,
                'htmlOptions' => [
                    // "style" => "white-space: pre;",
                    "class" => "text-center button-column"
                ]
            ),
            array(
                "name" => 'uid',
                "header" => gT("User ID")
            ),
            array(
                "name" => 'users_name',
                "header" => gT("Username")
            ),
            array(
                "name" => 'email',
                "header" => gT("Email")
            ),
            array(
                "name" => 'full_name',
                "header" => gT("Full name")
            ),
            array(
                "name" => "created",
                "header" => gT("Created on"),
                "value" => '$data->formattedDateCreated',
            ),
            array(
                "name" => "parentUserName",
                "header" => gT("Created by"),
            )
        );

        if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            $cols[] = array(
                "name" => 'surveysCreated',
                "header" => gT("No of surveys"),
                'filter' => false
            );
            $cols[] = array(
                "name" => 'groupList',
                "header" => gT("Usergroups"),
                'filter' => false
            );
            $cols[] = array(
                "name" => 'roleList',
                "header" => gT("Applied role"),
                'filter' => false
            );
        }

        return $cols;
    }

    /**
     * @return array
     */
    public function getColums()
    {
        // TODO should be static
        $cols = array(
            array(
                "name" => 'buttons',
                "type" => 'raw',
                "header" => gT("Action")
            ),
            array(
                "name" => 'uid',
                "header" => gT("User ID")
            ),
            array(
                "name" => 'users_name',
                "header" => gT("Username")
            ),
            array(
                "name" => 'email',
                "header" => gT("Email")
            ),
            array(
                "name" => 'full_name',
                "header" => gT("Full name")
            )
        );
        if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            $cols[] = array(
                "name" => 'surveysCreated',
                "header" => gT("No of surveys")
            );
        }

        $cols[] = array(
            "name" => "parentUserName",
            "header" => gT("Created by"),
        );

        $cols[] = array(
            "name" => "created",
            "header" => gT("Created on"),
            "value" => '$data->formattedDateCreated',

        );
        return $cols;
    }

    /** @inheritdoc */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.
        $pageSize = Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']);
        $criteria = new CDbCriteria();

        $criteria->compare('t.uid', $this->uid);
        $criteria->compare('t.full_name', $this->full_name, true);
        $criteria->compare('t.users_name', $this->users_name, true, 'OR');
        $criteria->compare('t.email', $this->email, true, 'OR');

        //filter for 'created' date comparison
        $dateformatdetails = getDateFormatData(Yii::app()->session['dateformat']);
        if ($this->created) {
            try {
                $dateTimeInput = $this->created . ' 00:00'; //append time
                $s = DateTime::createFromFormat($dateformatdetails['phpdate'] . ' H:i', $dateTimeInput);
                if ($s) {
                    $s2 = $s->format('Y-m-d H:i');
                    $criteria->addCondition('t.created >= \'' . $s2 . '\'');
                } else {
                    throw new Exception('wrong date format.');
                }
            } catch (Exception $e) {
                //could only mean wrong input from user ...reset filter value
                $this->created = '';
            }
        }

        $getUser = Yii::app()->request->getParam('User');
        if (!empty($getUser['parentUserName'])) {
             $getParentName = $getUser['parentUserName'];
            $criteria->join = "LEFT JOIN {{users}} u ON t.parent_id = u.uid";
            $criteria->compare('u.users_name', $getParentName, true, 'OR');
        }

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => $pageSize
            )
        ));
    }

    /**
     * Creates a validation key and saves it in table user for this user.
     *
     * @return bool true if validation_key could be saved in db, false otherwise
     */
    public function setValidationKey()
    {
        $this->validation_key = randomChars(self::MAX_VALIDATION_KEY_LENGTH);

        return $this->save();
    }

    /**
     * Creates the validation key expiration date and save it in db
     *
     * @return bool true if datetime could be saved, false otherwise
     * @throws Exception
     */
    public function setValidationExpiration()
    {
        $datePlusMaxExpiration = new DateTime();
        $datePlusString = 'P' . self::MAX_EXPIRATION_TIME_IN_DAYS . 'D';
        $dateInterval = new DateInterval($datePlusString);
        $datePlusMaxExpiration->add($dateInterval);

        $this->validation_key_expiration = $datePlusMaxExpiration->format('Y-m-d H:i:s');

        return $this->save();
    }
}
