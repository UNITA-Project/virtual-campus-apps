<?php

use LimeSurvey\PluginManager\AuthPluginBase;

/*
* LimeSurvey
* Copyright (C) 2013 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/**
* User Controller
*
* This controller performs user actions
*
* @package        LimeSurvey
* @subpackage    Backend
*/
class UserAction extends SurveyCommonAction
{
    /**
     * Constructor
     * @param $controller
     * @param $id
     */
    public function __construct($controller, $id)
    {
        parent::__construct($controller, $id);

        Yii::app()->loadHelper('database');
    }

    /**
     * Get Post- or Paramvalue depending on where to get it
     * @param string $param
     * @return string
     */
    private function getPostOrParam(string $param)
    {
        $value = Yii::app()->request->getPost($param);
        if (!$value) {
            /* This already return GET or POST : : http://www.yiiframework.com/doc/api/1.1/CHttpRequest#getParam-detail
             * DB update need $_POST, then only Yii::app()->request->getPost or control Yii::app()->request->getIsPostRequest()
             **/
            $value = Yii::app()->request->getParam($param);
        }
        return $value;
    }

    /**
     * Show users table
     */
    public function index()
    {
        if (!Permission::model()->hasGlobalPermission('users', 'read')) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array("admin/"));
        }

        App()->getClientScript()->registerPackage('jquery-tablesorter');
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'users.js');

        $aData = array();
        // Page size
        if (Yii::app()->request->getParam('pageSize')) {
            Yii::app()->user->setState('pageSize', (int) Yii::app()->request->getParam('pageSize'));
        }
        $aData['pageSize'] = Yii::app()->user->getState('pageSize', (int) Yii::app()->params['defaultPageSize']);

        // Title Bar
        $aData['title_bar']['title'] = gT('User administration');
        $model = new User();

        // Search
        if (isset($_GET['User']['searched_value'])) {
            $model->searched_value = $_GET['User']['searched_value'];
        }

        $aData['model'] = $model;
        $aData['formUrl'] = 'admin/user/sa/index';
        $this->renderWrappedTemplate('user', 'editusers', $aData);
    }

    /**
     * @param array $user
     * @return int
     * @todo Not used?
     */
    private function getSurveyCountForUser(array $user)
    {
        return Survey::model()->countByAttributes(array('owner_id' => $user['uid']));
    }

    /**
     * Add a new survey administrator user
     *
     * @return void
     */
    public function adduser()
    {
        if (!Permission::model()->hasGlobalPermission('users', 'create')) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }

        $new_user = flattenText(Yii::app()->request->getPost('new_user'), false, true);
        $aViewUrls = array();
        if (empty($new_user)) {
            $aViewUrls['message'] = array(
                'title' => gT("Failed to add user"),
                'message' => gT("A username was not supplied or the username is invalid."),
                'class' => 'text-warning'
            );
        } elseif (User::model()->find("users_name=:users_name", array(':users_name' => $new_user))) {
            // TODO: If error, we want to keep the form values. Can't do it nicely without CActiveForm?
            Yii::app()->setFlashMessage(gT("The username already exists."), 'error');
            $this->getController()->redirect(array('/admin/user/sa/index'));
        } else {
            $event = new PluginEvent('createNewUser');
            $event->set('errorCode', AuthPluginBase::ERROR_NOT_ADDED);
            $event->set('errorMessageTitle', gT("Failed to add user"));
            $event->set('errorMessageBody', gT("Plugin is not active"));
            App()->getPluginManager()->dispatchEvent($event);

            if ($event->get('errorCode') != AuthPluginBase::ERROR_NONE) {
                $aViewUrls['message'] = array('title' => $event->get('errorMessageTitle'), 'message' => $event->get('errorMessageBody'), 'class' => 'text-warning');
            } else {
                $iNewUID = $event->get('newUserID');
                $new_pass = $event->get('newPassword');
                $new_email = $event->get('newEmail');
                $new_full_name = $event->get('newFullName');

                // add default template to template rights for user
                Permission::model()->insertSomeRecords(array('uid' => $iNewUID, 'permission' => getGlobalSetting('defaulttheme'), 'entity' => 'template', 'read_p' => 1, 'entity_id' => 0));
                // add default usersettings to the user
                SettingsUser::applyBaseSettings($iNewUID);

                // send Mail
                /* @todo : must move this to Plugin (or sendMail as boolean in plugin event) */
                $body = sprintf(gT("Hello %s,"), $new_full_name) . "<br /><br />\n";
                $body .= sprintf(gT("This is an automated email to notify that a user has been created for you on the site '%s'."), Yii::app()->getConfig("sitename")) . "<br /><br />\n";
                $body .= gT("You can use now the following credentials to log into the site:") . "<br />\n";
                $body .= gT("Username") . ": " . htmlspecialchars($new_user) . "<br />\n";
                // authent is not delegated to web server or LDAP server
                if (Yii::app()->getConfig("auth_webserver") === false && Permission::model()->hasGlobalPermission('auth_db', 'read', $iNewUID)) {
                    // send password (if authorized by config)
                    if (Yii::app()->getConfig("display_user_password_in_email") === true) {
                        $body .= gT("Password") . ": " . $new_pass . "<br />\n";
                    } else {
                        $body .= gT("Password") . ": " . gT("Please contact your LimeSurvey administrator for your password.") . "<br />\n";
                    }
                }

                $body .= "<a href='" . $this->getController()->createAbsoluteUrl("/admin") . "'>" . gT("Click here to log in.") . "</a><br /><br />\n";
                $body .= sprintf(gT('If you have any questions regarding this mail please do not hesitate to contact the site administrator at %s. Thank you!'), Yii::app()->getConfig("siteadminemail")) . "<br />\n";

                $mailer = new LimeMailer();
                $mailer->addAddress($new_email, $new_user);
                $mailer->Subject = sprintf(gT("User registration at '%s'", "unescaped"), Yii::app()->getConfig("sitename"));
                ;
                $mailer->Body = $body;
                $mailer->isHtml(true);
                $mailer->emailType = "addadminuser";
                if ($mailer->sendMessage()) {
                    $extra = CHtml::tag("p", array(), sprintf(gT("Username: %s - Email: %s"), $new_user, $new_email));
                    $extra .= CHtml::tag("p", array(), gT("An email with a generated password was sent to the user."));
                    $classMsg = 'text-success';
                    $sHeader = gT("Success");
                } else {
                    // has to be sent again or no other way
                    $extra = CHtml::tag("p", array(), sprintf(gT("Email to %s (%s) failed."), "<strong>" . $new_user . "</strong>", $new_email));
                    $extra .= CHtml::tag("p", array('class' => 'alert alert-danger'), $mailer->getError());
                    $classMsg = 'text-warning';
                    $sHeader = gT("Warning");
                }

                $aViewUrls['mboxwithredirect'][] = $this->messageBoxWithRedirect(
                    gT("Add user"),
                    $sHeader,
                    $classMsg,
                    $extra,
                    $this->getController()->createUrl("admin/user/sa/setuserpermissions"),
                    gT("Set user permissions"),
                    array('action' => 'setuserpermissions', 'user' => $new_user, 'uid' => $iNewUID)
                );
            }
        }

        $this->renderWrappedTemplate('user', $aViewUrls);
    }

    /**
     * Delete user
     */
    public function deluser()
    {
        if (Yii::app()->request->getIsPostRequest()) {
            /* DB action : need post request */
            if (!Permission::model()->hasGlobalPermission('superadmin', 'read') && !Permission::model()->hasGlobalPermission('users', 'delete')) {
                Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
            }

            $action = $this->getPostOrParam("action");

            $aViewUrls = array();

            // CAN'T DELETE ORIGINAL SUPERADMIN (with findByAttributes : found the first user without parent)
            $oInitialAdmin = User::model()->findByAttributes(array('parent_id' => 0));

            $postuserid = $this->getPostOrParam("uid");
            $postuser = flattenText($this->getPostOrParam("user"));

            if ($oInitialAdmin && $oInitialAdmin->uid == $postuserid) {
                // it's the original superadmin !!!
                Yii::app()->setFlashMessage(gT("Initial Superadmin cannot be deleted!"), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
                return;
            }

            //If there was no uid transferred
            if (!$postuserid) {
                Yii::app()->setFlashMessage(gT("Could not delete user. User was not supplied."), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
                return;
            }

            $sresultcount = 0; // 1 if I am parent of $postuserid
            if (!Permission::model()->hasGlobalPermission('superadmin', 'read')) {
                $sresult = User::model()->findAllByAttributes(array('parent_id' => Yii::app()->session['loginID']));
                $sresultcount = count($sresult);
            }

            if (Permission::model()->hasGlobalPermission('superadmin', 'read') || $sresultcount > 0 || $postuserid == Yii::app()->session['loginID']) {
                $transfer_surveys_to = 0;
                $ownerUser = User::model()->findAll();
                $aData = array();
                $aData['users'] = $ownerUser;

                $current_user = Yii::app()->session['loginID'];
                if (count($ownerUser) == 2) {
                    $action = "finaldeluser";
                    foreach ($ownerUser as &$user) {
                        if ($postuserid != $user['uid']) {
                            $transfer_surveys_to = $user['uid'];
                        }
                    }
                }

                $ownerUser = Survey::model()->findAllByAttributes(array('owner_id' => $postuserid));
                if (count($ownerUser) == 0) {
                    $action = "finaldeluser";
                }

                if ($action == "finaldeluser") {
                    $this->deleteFinalUser($ownerUser, $transfer_surveys_to);
                } else {
                    $aData['postuserid'] = $postuserid;
                    $aData['postuser'] = $postuser;
                    $aData['current_user'] = $current_user;

                    $aViewUrls['deluser'][] = $aData;
                    $this->renderWrappedTemplate('user', $aViewUrls);
                }
            } else {
                Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
            }

            return $aViewUrls;
        }
        /* No action done, come back to user/index */
        $this->getController()->redirect(array("admin/user/sa/index"));
    }

    /**
     * @param CActiveRecord[] $result TODO: Used at all?
     * @param $transfer_surveys_to  TODO: ?
     * @return void
     * @todo Delete what final user?
     */
    public function deleteFinalUser($result, $transfer_surveys_to)
    {
        if (!Permission::model()->hasGlobalPermission('superadmin', 'read') && !Permission::model()->hasGlobalPermission('users', 'delete')) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
        $postuserid = (int) Yii::app()->request->getPost("uid");
        if (!$postuserid) {
            $postuserid = (int) Yii::app()->request->getParam("uid");
        }
        $postuser = flattenText(Yii::app()->request->getPost("user"));
        // Never delete a forced super admin
        if (Permission::isForcedSuperAdmin($postuserid)) {
            Yii::app()->setFlashMessage(gT("This superadmin cannot be deleted."), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
        if (isset($_POST['transfer_surveys_to'])) {
            $transfer_surveys_to = sanitize_int(Yii::app()->request->getPost("transfer_surveys_to"));
        }
        if ($transfer_surveys_to > 0) {
            $iSurveysTransferred = Survey::model()->updateAll(array('owner_id' => $transfer_surveys_to), 'owner_id=' . $postuserid);
        }
        $sresult = User::model()->findByAttributes(array('uid' => $postuserid));
        $fields = $sresult;
        if (isset($fields['parent_id'])) {
            User::model()->updateAll(array('parent_id' => $fields['parent_id']), 'parent_id=' . $postuserid);
        }

        // Delete user permissions
        Permission::model()->deleteAllByAttributes(array('uid' => $postuserid));

        // Delete user
        User::model()->deleteByPk($postuserid);

        if ($postuserid == Yii::app()->session['loginID']) {
            session_destroy(); // user deleted himself
            $this->getController()->redirect(array("admin/authentication/sa/logout"));
        }

        $extra = "<br />" . sprintf(gT("User '%s' was successfully deleted."), $postuser) . "<br /><br />\n";
        if ($transfer_surveys_to > 0 && $iSurveysTransferred > 0) {
            $user = User::model()->findByPk($transfer_surveys_to);
            $sTransferred_to = $user->users_name;
            $extra = sprintf(gT("All of the user's surveys were transferred to %s."), $sTransferred_to);
        }

        $aViewUrls = array();
        $aViewUrls['mboxwithredirect'][] = $this->messageBoxWithRedirect("", gT("Success!"), "text-success", $extra);
        $this->renderWrappedTemplate('user', $aViewUrls);
    }

    /**
     * Modify User
     */
    public function modifyuser()
    {
        if (Yii::app()->request->getParam('uid') != '') {
            $postuserid = (int) Yii::app()->request->getParam("uid");
            if (
                /* @todo : move all this logic to Permission::model */
                Permission::model()->hasGlobalPermission('superadmin', 'read') // Super admin have all right on user
                || Yii::app()->session['loginID'] == $postuserid // User can edit himself
                || (Permission::model()->hasGlobalPermission('users', 'update') && User::model()->count("uid=:uid AND parent_id=:parent_id", array(':uid' => $postuserid, 'parent_id' => Yii::app()->session['loginID']))) // User with users update can only update own Users
            ) {
                $oUser = User::model()->findByPk($postuserid);
                $aData = array();
                $aData['oUser'] = $oUser;

                $aData['fullpagebar']['savebutton']['form'] = 'moduserform';
                // Close button, UrlReferrer;
                $aData['fullpagebar']['closebutton']['url_keep'] = true;
                $aData['fullpagebar']['closebutton']['url'] = Yii::app()->request->getUrlReferrer(Yii::app()->createUrl("admin/user/sa/index"));
                $aData['passwordHelpText'] = $oUser->getPasswordHelpText();

                $this->renderWrappedTemplate('user', 'modifyuser', $aData);
                return;
            } else {
                Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
            }
        }
        $this->getController()->redirect(array("admin/user/sa/index"));
    }

    /**
     * Modify User POST
     */
    public function moduser()
    {
        $postUser = Yii::app()->request->getPost("User");
        $user_uid = Yii::app()->request->getPost("uid");
        $oUser = User::model()->findByPk($user_uid);
        if (!$oUser) {
            throw new CHttpException(403); // Bad param (and not 404) because it's POST value
        }
        $newUsermail = empty($postUser['email']) ? null : $postUser['email'];
        $newUserFullName = empty($postUser['full_name']) ? null : $postUser['full_name'];
        $newPassword = empty($postUser['password']) ? null : $postUser['password'];
        $display_user_password_in_html = Yii::app()->getConfig("display_user_password_in_html");
        $aViewUrls = array();
        if (
            (
                Permission::model()->hasGlobalPermission('superadmin', 'read') // superadmin have this right
                || $user_uid == Yii::app()->session['loginID'] // always allow update himself
                || ($oUser->parent_id == Yii::app()->session['loginID'] && Permission::model()->hasGlobalPermission('users', 'update')) // Allow to updated created user
            )
            && !(Yii::app()->getConfig("demoMode") == true && $user_uid == 1)// Disallow update password in demo mode
        ) {
            $email = html_entity_decode($newUsermail, ENT_QUOTES, 'UTF-8');
            $sPassword = $newPassword;
            $full_name = html_entity_decode($newUserFullName, ENT_QUOTES, 'UTF-8');
            if (!validateEmailAddress($email)) {
                Yii::app()->setFlashMessage(gT("Could not modify user data.") . ' ' . gT("Email address is not valid."), 'error');
                $this->getController()->redirect(array("/admin/user/sa/modifyuser/uid/" . $user_uid));
            } else {
                $oUser->email = $email;
                $oUser->full_name = $full_name;

                if (!empty($sPassword)) {
                    $error = $oUser->checkPasswordStrength($sPassword);
                    if ($error) {
                        Yii::app()->setFlashMessage(gT($error), 'error');
                        $this->getController()->redirect(array("/admin/user/sa/modifyuser/uid/" . $user_uid));
                    }
                }

                if (!empty($sPassword)) {
                    $oUser->setPassword($sPassword);
                }
                $uresult = $oUser->save(); // store result of save in uresult

                if (empty($sPassword)) {
                    Yii::app()->setFlashMessage(gT("Success!") . ' <br/> ' . gT("Password") . ": (" . gT("Unchanged") . ")", 'success');
                    $this->getController()->redirect(array("/admin/user/sa/modifyuser/uid/" . $user_uid));
                } elseif ($uresult && !empty($sPassword)) {
                    // When saved successfully
                    Yii::app()->session['pw_notify'] = $sPassword != '';
                    if ($display_user_password_in_html === true) {
                        $displayedPwd = htmlentities($sPassword);
                    } else {
                        $displayedPwd = preg_replace('/./', '*', $sPassword);
                    }
                    Yii::app()->setFlashMessage(gT("Success!") . ' <br/> ' . gT("Password") . ": " . $displayedPwd, 'success');
                    $this->getController()->redirect(array("/admin/user/sa/modifyuser/uid/" . $user_uid));
                } else {
                    //Saving the user failed for some reason, message about email is not helpful here
                    // Username and/or email adress already exists.
                    Yii::app()->setFlashMessage(gT("Could not modify user data."), 'error');
                    $this->getController()->redirect(array("/admin/user/sa/modifyuser/uid/" . $user_uid));
                }
            }
        } else {
            Yii::app()->setFlashMessage(gT("Could not modify user data."), 'error');
            $this->getController()->redirect(array("/admin/"));
        }

        $aData = array();
        $aData['fullpagebar']['continuebutton']['url'] = 'admin/user/sa/index';
        $this->renderWrappedTemplate('user', $aViewUrls, $aData);
    }

    /**
     * Save Permissions
     */
    public function savepermissions()
    {
        if (!Permission::model()->hasGlobalPermission('users', 'update')) {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }

        $iUserID = (int) App()->request->getPost('uid');
        // A user may not modify his own permissions
        if (Yii::app()->session['loginID'] == $iUserID) {
            Yii::app()->setFlashMessage(gT("You are not allowed to edit your own user permissions."), "error");
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
        // Can not update forced superadmin  rights
        if (Permission::isForcedSuperAdmin($iUserID)) {
            Yii::app()->setFlashMessage(gT("The permissions of this superadmin cannot be updated!"), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
        $aBaseUserPermissions = Permission::model()->getGlobalBasePermissions();

        $aPermissions = array();
        foreach ($aBaseUserPermissions as $sPermissionKey => $aCRUDPermissions) {
            foreach ($aCRUDPermissions as $sCRUDKey => $CRUDValue) {
                if (!in_array($sCRUDKey, array('create', 'read', 'update', 'delete', 'import', 'export'))) {
                    continue;
                }
                if ($CRUDValue) {
                    $sPermissionPostValue = Yii::app()->getRequest()->getPost("perm_{$sPermissionKey}_{$sCRUDKey}", '');
                    $aPermissions[$sPermissionKey][$sCRUDKey] = $sPermissionPostValue == 'on' ? 1 : 0;
                }
            }
        }

        if (Permission::model()->setPermissions($iUserID, 0, 'global', $aPermissions)) {
            Yii::app()->session['flashmessage'] = gT("Permissions were successfully updated.");
            $this->getController()->redirect(array("admin/user/sa/index"));
        } else {
            Yii::app()->session['flashmessage'] = gT("There was a problem updating the user permissions.");
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
    }

    /**
     * Set User permissions
     */
    public function setuserpermissions()
    {
        $iUserID = (int) Yii::app()->request->getPost('uid');
        if ($iUserID) {
            //Only super admin (read) can update other user
            if (Permission::model()->hasGlobalPermission('superadmin', 'read')) {
                $oUser = User::model()->findByAttributes(array('uid' => $iUserID));
            } else {
                $oUser = User::model()->findByAttributes(array('uid' => $iUserID, 'parent_id' => Yii::app()->session['loginID']));
            }
        }

        // Check permissions
        $aBasePermissions = Permission::model()->getGlobalBasePermissions();
        if (!Permission::model()->hasGlobalPermission('superadmin', 'read')) {
            // if not superadmin filter the available permissions as no admin may give more permissions than he owns
            Yii::app()->session['flashmessage'] = gT("Note: You can only give limited permissions to other users because your own permissions are limited, too.");
            $aFilteredPermissions = array();
            foreach ($aBasePermissions as $PermissionName => $aPermission) {
                foreach ($aPermission as $sPermissionKey => &$sPermissionValue) {
                    if ($sPermissionKey != 'title' && $sPermissionKey != 'img' && !Permission::model()->hasGlobalPermission($PermissionName, $sPermissionKey)) {
                        $sPermissionValue = false;
                    }
                }
                // Only show a row for that permission if there is at least one permission he may give to other users
                if ($aPermission['create'] || $aPermission['read'] || $aPermission['update'] || $aPermission['delete'] || $aPermission['import'] || $aPermission['export']) {
                    $aFilteredPermissions[$PermissionName] = $aPermission;
                }
            }
            $aBasePermissions = $aFilteredPermissions;
        }

        if (isset($oUser)) {
            if ($oUser && (Permission::model()->hasGlobalPermission('superadmin', 'read') || Permission::model()->hasGlobalPermission('users', 'update') && Yii::app()->session['loginID'] != $iUserID)) {
                // Show superadmin right if create is set (review for delete too ?)
                if (!Permission::model()->hasGlobalPermission('superadmin', 'create')) {
                    unset($aBasePermissions['superadmin']);
                }
                $aData = array();
                $aData['aBasePermissions'] = $aBasePermissions;
                $aData['oUser'] = $oUser;

                App()->getClientScript()->registerPackage('jquery-tablesorter');
                App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'userpermissions.js');

                // Fullpage Bar
                $aData['fullpagebar']['savebutton']['form'] = 'savepermissions';
                $aData['fullpagebar']['closebutton']['url_keep'] = true;
                $aData['fullpagebar']['closebutton']['url'] = Yii::app()->request->getUrlReferrer(Yii::app()->createUrl("admin/user/sa/index"));

                $this->renderWrappedTemplate('user', 'setuserpermissions', $aData);
            } else {
                Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
                $this->getController()->redirect(array("admin/user/sa/index"));
            }
        } else {
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
    }

    /**
     * Set User Templates
     */
    public function setusertemplates()
    {
        App()->getClientScript()->registerPackage('jquery-tablesorter');
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'users.js');
        $postuserid = (int) Yii::app()->request->getPost("uid");
        $oUser = User::model()->findByAttributes(array('uid' => $postuserid));
        if (!$oUser) {
            // @todo : review to send a 403
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
        $aData['oUser'] = $oUser;
        $this->refreshtemplates();
        $templaterights = array();

        $trights = Permission::model()->findAllByAttributes(array('uid' => $oUser->uid, 'entity' => 'template'));
        foreach ($trights as $srow) {
            $templaterights[$srow["permission"]] = array("use" => $srow["read_p"]);
        }
        $templates = Template::model()->findAll();
        $aData['data'] = array('templaterights' => $templaterights, 'templates' => $templates);


        $aData['fullpagebar']['savebutton']['form'] = 'modtemplaterightsform';
        $aData['fullpagebar']['closebutton']['url_keep'] = true;
        $aData['fullpagebar']['closebutton']['url'] = Yii::app()->request->getUrlReferrer(Yii::app()->createUrl("admin/user/sa/index"));

        $this->renderWrappedTemplate('user', 'setusertemplates', $aData);
    }

    /**
     * User Templates
     */
    public function usertemplates()
    {
        $postuserid = (int) Yii::app()->request->getPost('uid');

        // SUPERADMINS AND MANAGE_TEMPLATE USERS CAN SET THESE RIGHTS
        if (Permission::model()->hasGlobalPermission('superadmin', 'read') || Permission::model()->hasGlobalPermission('templates', 'update')) {
            $aTemplatePermissions = array();
            $tresult = Template::model()->findAll();
            foreach ($tresult as $trow) {
                if (isset($_POST[$trow["folder"] . "_use"])) {
                    $aTemplatePermissions[$trow["folder"]] = $_POST[$trow["folder"] . "_use"];
                }
            }
            foreach ($aTemplatePermissions as $key => $value) {
                $oPermission = Permission::model()->findByAttributes(array('permission' => $key, 'uid' => $postuserid, 'entity' => 'template'));
                if (empty($oPermission)) {
                    $oPermission = new Permission();
                    $oPermission->uid = $postuserid;
                    $oPermission->permission = $key;
                    $oPermission->entity = 'template';
                    $oPermission->entity_id = 0;
                }
                $oPermission->read_p = $value;
                $uresult = $oPermission->save();
            }
            if ($uresult !== false) {
                Yii::app()->setFlashMessage(gT("Template permissions were updated successfully."));
            } else {
                Yii::app()->setFlashMessage(gT("Error while updating template permissions."), 'error');
            }
            $this->getController()->redirect(array("admin/user/sa/index"));
        } else {
            Yii::app()->setFlashMessage(gT("You do not have permission to access this page."), 'error');
            $this->getController()->redirect(array("admin/user/sa/index"));
        }
    }

    /**
     * Manage user personal settings
     */
    public function personalsettings()
    {
        // Save Data
        if (Yii::app()->request->getPost("action")) {
            $oUserModel = User::model()->findByPk(Yii::app()->session['loginID']);
            $uresult = true;

            if (Yii::app()->request->getPost('newpasswordshown') == "1") {
                if (Yii::app()->getConfig('demoMode')) {
                    Yii::app()->setFlashMessage(gT("You can't change password if demo mode is active."), 'error');
                    $this->getController()->redirect(array("admin/user/sa/personalsettings"));
                }

                $oldPassword = Yii::app()->request->getPost('oldpassword');

                // Check the current password
                $currentPasswordOk = $oUserModel->checkPassword($oldPassword);
                if (!$currentPasswordOk) {
                    Yii::app()->setFlashMessage(gT('The current password is not correct.'), 'error');
                    $this->getController()->redirect(array("admin/user/sa/personalsettings"));
                }

                $newPassword = Yii::app()->request->getPost('password');
                $repeatPassword = Yii::app()->request->getPost('repeatpassword');

                if ($newPassword !== '' && $repeatPassword !== '') {
                    $error = $oUserModel->validateNewPassword($newPassword, $oldPassword, $repeatPassword);

                    if ($error !== '') {
                        Yii::app()->setFlashMessage(gT($error), 'error');
                        $this->getController()->redirect(array("admin/user/sa/personalsettings"));
                    } else {
                        // We can update
                        $oUserModel->setPassword($newPassword);
                    }
                }
            }

            if (Yii::app()->request->getPost('newemailshown') == "1") {
                if (Yii::app()->getConfig('demoMode')) {
                    Yii::app()->setFlashMessage(gT("You can't change your email adress if demo mode is active."), 'error');
                    $this->getController()->redirect(array("admin/user/sa/personalsettings"));
                }

                $oldPassword = Yii::app()->request->getPost('oldpassword');

                // Check the current password
                $currentPasswordOk = $oUserModel->checkPassword($oldPassword);
                if (!$currentPasswordOk) {
                    Yii::app()->setFlashMessage(gT('The current password is not correct.'), 'error');
                    $this->getController()->redirect(array("admin/user/sa/personalsettings"));
                }

                $oUserModel->email = Yii::app()->request->getPost('newemail');
                $uresult = $oUserModel->save();
            }

            $oUserModel->lang                 = Yii::app()->request->getPost('lang');
            $oUserModel->dateformat           = Yii::app()->request->getPost('dateformat');
            $oUserModel->htmleditormode       = Yii::app()->request->getPost('htmleditormode');
            $oUserModel->questionselectormode = Yii::app()->request->getPost('questionselectormode');
            $oUserModel->templateeditormode   = Yii::app()->request->getPost('templateeditormode');
            $oUserModel->full_name            = Yii::app()->request->getPost('fullname');
            $uresult = $uresult && $oUserModel->save();
            if ($uresult) {
                if (Yii::app()->request->getPost('lang') == 'auto') {
                    $sLanguage = getBrowserLanguage();
                } else {
                    $sLanguage = Yii::app()->request->getPost('lang');
                }
                Yii::app()->session['adminlang'] = $sLanguage;
                Yii::app()->setLanguage($sLanguage);

                Yii::app()->session['htmleditormode'] = Yii::app()->request->getPost('htmleditormode');
                Yii::app()->session['questionselectormode'] = Yii::app()->request->getPost('questionselectormode');
                Yii::app()->session['templateeditormode'] = Yii::app()->request->getPost('templateeditormode');
                Yii::app()->session['dateformat'] = Yii::app()->request->getPost('dateformat');

                SettingsUser::setUserSetting('preselectquestiontype', Yii::app()->request->getPost('preselectquestiontype'));
                SettingsUser::setUserSetting('preselectquestiontheme', Yii::app()->request->getPost('preselectquestiontheme'));
                SettingsUser::setUserSetting('showScriptEdit', Yii::app()->request->getPost('showScriptEdit'));
                SettingsUser::setUserSetting('noViewMode', Yii::app()->request->getPost('noViewMode'));
                SettingsUser::setUserSetting('answeroptionprefix', Yii::app()->request->getPost('answeroptionprefix'));
                SettingsUser::setUserSetting('subquestionprefix', Yii::app()->request->getPost('subquestionprefix'));
                SettingsUser::setUserSetting('lock_organizer', Yii::app()->request->getPost('lock_organizer'));
                SettingsUser::setUserSetting('createsample', Yii::app()->request->getPost('createsample'));

                Yii::app()->setFlashMessage(gT("Your personal settings were successfully saved."));
            } else {
                // Show list of error if needed
                Yii::app()->setFlashMessage(CHtml::errorSummary($oUserModel, gT("There was an error when saving your personal settings.")), 'error');
            }

            if (Yii::app()->request->getPost("saveandclose")) {
                $this->getController()->redirect(array("admin/index"));
            }
        }

        // Page size
        if (App()->request->getParam('pageSize')) {
            App()->user->setState('pageSize', (int) App()->request->getParam('pageSize'));
        }

        // Get user lang
        unset($oUser);
        $oUser = User::model()->findByPk(Yii::app()->session['loginID']);

        $aLanguageData = array('auto' => gT("(Autodetect)"));
        foreach (getLanguageData(true, Yii::app()->session['adminlang']) as $langkey => $languagekind) {
            $aLanguageData[$langkey] = html_entity_decode($languagekind['nativedescription'] . ' - ' . $languagekind['description'], ENT_COMPAT, 'utf-8');
        }

        $aData = array();
        $aData['aLanguageData'] = $aLanguageData;
        $aData['sSavedLanguage'] = $oUser->lang;
        $aData['sUsername'] = $oUser->users_name;
        $aData['sFullname'] = $oUser->full_name;
        $aData['sEmailAdress'] = $oUser->email;
        $aData['passwordHelpText'] = $oUser->getPasswordHelpText();

        // Fullpager Bar
        $aData['fullpagebar']['savebutton']['form'] = 'personalsettings';
        $aData['fullpagebar']['saveandclosebutton']['form'] = 'personalsettings';
        $aData['fullpagebar']['white_closebutton']['url'] = Yii::app()->request->getUrlReferrer(Yii::app()->createUrl("admin"));

        // Green Bar Page Title
        $aData['pageTitle'] = gT('My Account');

        //Get data for personal menues
        $oSurveymenu = Surveymenu::model();
        $oSurveymenu->user_id = $oUser->uid;
        $oSurveymenuEntries = SurveymenuEntries::model();
        $oSurveymenuEntries->user_id = $oUser->uid;
        $aRawUserSettings = SettingsUser::model()->findAllByAttributes(['uid' => $oUser->uid]);

        $aUserSettings = [];
        array_walk($aRawUserSettings, function ($oUserSetting) use (&$aUserSettings) {
            $aUserSettings[$oUserSetting->stg_name] = $oUserSetting->stg_value;
        });

        $currentPreselectedQuestiontype = array_key_exists('preselectquestiontype', $aUserSettings) ? $aUserSettings['preselectquestiontype'] : App()->getConfig('preselectquestiontype');
        $currentPreselectedQuestionTheme = array_key_exists('preselectquestiontheme', $aUserSettings) ? $aUserSettings['preselectquestiontheme'] : App()->getConfig('preselectquestiontheme');

        $aData['currentPreselectedQuestiontype'] = $currentPreselectedQuestiontype;
        $aData['currentPreselectedQuestionTheme'] = $currentPreselectedQuestionTheme;
        $aData['aUserSettings'] = $aUserSettings;
        $aData['aQuestionTypeList'] = QuestionTheme::findAllQuestionMetaDataForSelector();
        $aData['selectedQuestion'] = QuestionTheme::findQuestionMetaData($currentPreselectedQuestiontype, $currentPreselectedQuestionTheme);

        $aData['surveymenu_data']['model'] = $oSurveymenu;
        $aData['surveymenuentry_data']['model'] = $oSurveymenuEntries;
        // Render personal settings view
        if (isset($_POST['saveandclose'])) {
            $this->getController()->redirect(array("admin/user/sa/index"));
        } else {
            $this->renderWrappedTemplate('user', 'personalsettings', $aData);
        }
    }

    /**
     * Toggle Setting
     * @param int $surveyid
     */
    public function togglesetting($surveyid = 0)
    {
        $setting  = Yii::app()->request->getPost('setting');
        $newValue = Yii::app()->request->getPost('newValue');

        $result = SettingsUser::setUserSetting($setting, $newValue);

        $this->renderJSON([
            "result" => SettingsUser::getUserSettingValue($setting)
        ]);
    }

    /**
     * @param int $uid
     * @return string|boolean
     * @todo Not used?
     */
    private function getUserNameFromUid(int $uid)
    {
        $uid = sanitize_int($uid);
        $result = User::model()->findByPk($uid);

        if (!empty($result)) {
            return $result->users_name;
        } else {
            return false;
        }
    }

    /**
     * Refresh templates
     */
    private function refreshtemplates()
    {
        $template_a = Template::getTemplateList();
        foreach ($template_a as $tp => $fullpath) {
            // check for each folder if there is already an entry in the database
            // if not create it with current user as creator (user with rights "create user" can assign template rights)
            $result = Template::model()->findByPk($tp);

            if ($result == null) {
                $post = new Template();
                $post->folder = $tp;
                $post->owner_id = Yii::app()->session['loginID'];

                try {
                    $post->save();
                } catch (Exception $ex) {
                    Yii::app()->setFlashMessage(
                        sprintf(
                            gT('Could not save theme %s: %s'),
                            $tp,
                            $ex->getMessage()
                        ),
                        'error'
                    );
                }
            }
        }
        return true;
    }

    /**
     * Escape string
     * @param string $str
     * @param bool   $like Default is false.
     * @return string
     * @todo Not used?
     */
    private function escapeStr(string $str, bool $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escapeStr($val, $like);
            }

            return $str;
        }

        // Escape single quotes
        $str = str_replace("'", "''", $this->removeInvisibleCharacters($str));

        return $str;
    }

    /**
     * Remove invisible characters.
     * @param string $str
     * @param bool   $url_encoded Default is true.
     * @return string
     * @todo Not used?
     */
    private function removeInvisibleCharacters($str, $url_encoded = true)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

    /**
     * Message Box with redirect
     * @param string $title
     * @param string $message
     * @param string $classMsg
     * @param string $extra
     * @param string $urlText
     * @param array  $hiddenVars
     * @param string $classMbTitle
     * @todo to many arguments
     */
    private function messageBoxWithRedirect($title, $message, $classMsg, $extra = "", $url = "", $urlText = "", $hiddenVars = array(), $classMbTitle = "header ui-widget-header")
    {
        $url = (!empty($url)) ? $url : $this->getController()->createUrl('admin/user/index');
        $urlText = (!empty($urlText)) ? $urlText : gT("Continue");

        $aData = array();
        $aData['title'] = $title;
        $aData['message'] = $message;
        $aData['url'] = $url;
        $aData['urlText'] = $urlText;
        $aData['classMsg'] = $classMsg;
        $aData['classMbTitle'] = $classMbTitle;
        $aData['extra'] = $extra;
        $aData['hiddenVars'] = $hiddenVars;

        return $aData;
    }

    /**
     * Renders template(s) wrapped in header and footer
     *
     * @param string       $sAction     Current action, the folder to fetch views from
     * @param string|array $aViewUrls   View url(s)
     * @param array        $aData       Data to be passed on. Optional.
     * @param bool         $sRenderFile
     */
    protected function renderWrappedTemplate($sAction = 'user', $aViewUrls = array(), $aData = array(), $sRenderFile = false)
    {
        parent::renderWrappedTemplate($sAction, $aViewUrls, $aData, $sRenderFile);
    }
}
