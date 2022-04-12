<?php

/*
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
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
* Translate Controller
*
* This controller performs translation actions
*
* @package      LimeSurvey
* @subpackage   Backend
*/
class Translate extends SurveyCommonAction
{
    public function index($surveyid)
    {
        /* existing + read (survey) already checked in SurveyCommonAction : existing use model : then if surveyid is not valid : return a 404 */
        /* survey : read OK, not survey:tranlations:read … */
        if (!Permission::model()->hasSurveyPermission($surveyid, 'translations', 'read')) {
            throw new CHttpException(401, "401 Unauthorized");
        }

        $oSurvey = Survey::model()->findByPk($surveyid);

        //KCFINDER SETTINGS
        Yii::app()->session['FileManagerContext'] = "edit:survey:{$oSurvey->sid}";
        Yii::app()->loadHelper('admin.htmleditor');
        initKcfinder();

        $tolang = Yii::app()->getRequest()->getParam('lang');
        if (!empty($tolang) && !in_array($tolang, $oSurvey->getAllLanguages())) {
            Yii::app()->setFlashMessage(gT("Invalid language"), 'warning');
            $tolang = null;
        }
        $action = Yii::app()->getRequest()->getParam('action');
        $actionvalue = Yii::app()->getRequest()->getPost('actionvalue');

        if ($action == "ajaxtranslategoogleapi") {
            echo $this->translateGoogleApi();
            return;
        }
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'translation.js');

        $baselang = $oSurvey->language;
        $langs = $oSurvey->additionalLanguages;

        Yii::app()->loadHelper("database");
        Yii::app()->loadHelper("admin.htmleditor");

        if (empty($tolang) && count($langs) > 0) {
            $tolang = $langs[0];
        }

        // TODO need to do some validation here on surveyid
        $survey_title = $oSurvey->defaultlanguage->surveyls_title;

        Yii::app()->loadHelper("surveytranslator");
        $supportedLanguages = getLanguageData(false, Yii::app()->session['adminlang']);

        $baselangdesc = $supportedLanguages[$baselang]['description'];

        $aData = array(
            "surveyid" => $surveyid,
            "survey_title" => $survey_title,
            "tolang" => $tolang,
            "adminmenu" => $this->showTranslateAdminmenu($surveyid, $survey_title, $tolang)
        );
        $aViewUrls['translateheader_view'][] = $aData;

        $tab_names = array("title", "welcome", "group", "question", "subquestion", "answer",
                        "emailinvite", "emailreminder", "emailconfirmation", "emailregistration",
                        "emailbasicadminnotification", "emaildetailedadminnotification");

        if (!empty($tolang)) {
            // Only save if the administration user has the correct permission
            if ($actionvalue == "translateSave" && Permission::model()->hasSurveyPermission($surveyid, 'translations', 'update')) {
                $this->translateSave($surveyid, $tolang, $baselang, $tab_names);
                Yii::app()->setFlashMessage(gT("Saved"), 'success');
            }

            $tolangdesc = $supportedLanguages[$tolang]['description'];
            // Display tabs with fields to translate, as well as input fields for translated values
            $aViewUrls = array_merge($aViewUrls, $this->displayUntranslatedFields($surveyid, $tolang, $baselang, $tab_names, $baselangdesc, $tolangdesc));
            //var_dump(array_keys($aViewUrls));die();
        }

        $aData['sidemenu']['state'] = false;
        $aData['title_bar']['title'] = $oSurvey->currentLanguageSettings->surveyls_title . " (" . gT("ID") . ":" . $surveyid . ")";
        if (Permission::model()->hasSurveyPermission($surveyid, 'translations', 'update')) {
            $aData['surveybar']['savebutton']['form'] = 'frmeditgroup';
            $aData['surveybar']['closebutton']['url'] = 'surveyAdministration/view/surveyid/' . $surveyid; // Close button
            $aData['topBar']['showSaveButton'] = true;
        }

        $this->renderWrappedTemplate('translate', $aViewUrls, $aData);
    }

    /**
     * @param string[] $tab_names
     */
    private function translateSave($iSurveyID, $tolang, $baselang, $tab_names)
    {
        $tab_names_full = $tab_names;

        foreach ($tab_names as $type) {
            $amTypeOptions = $this->setupTranslateFields($type);
            $type2 = $amTypeOptions["associated"];

            if (!empty($type2)) {
                $tab_names_full[] = $type2;
            }
        }

        foreach ($tab_names_full as $type) {
            $size = (int) Yii::app()->getRequest()->getPost("{$type}_size");
            // start a loop in order to update each record
            $i = 0;
            while ($i <= $size) {
                // define each variable
                if (Yii::app()->getRequest()->getPost("{$type}_newvalue_{$i}")) {
                    $old = Yii::app()->getRequest()->getPost("{$type}_oldvalue_{$i}");
                    $new = Yii::app()->getRequest()->getPost("{$type}_newvalue_{$i}");

                    // check if the new value is different from old, and then update database
                    if ($new != $old) {
                        $id1 = Yii::app()->getRequest()->getPost("{$type}_id1_{$i}");
                        $id2 = Yii::app()->getRequest()->getPost("{$type}_id2_{$i}");
                        $iScaleID = Yii::app()->getRequest()->getPost("{$type}_scaleid_{$i}");
                        $this->query($type, 'queryupdate', $iSurveyID, $tolang, $baselang, $id1, $id2, $iScaleID, $new);
                    }
                }
                $i++;
            } // end while
        } // end foreach
    }

    /**
     * @param string[] $tab_names
     */
    private function displayUntranslatedFields($iSurveyID, $tolang, $baselang, $tab_names, $baselangdesc, $tolangdesc)
    {
        // Define aData
        $aData['surveyid'] = $iSurveyID;
        $aData['tab_names'] = $tab_names;
        $aData['tolang'] = $tolang;
        $aData['baselang'] = $baselang;
        $aData['baselangdesc'] = $baselangdesc;
        $aData['tolangdesc'] = $tolangdesc;
        //This is for the tab navbar
        $aData['amTypeOptions'] = array_map(array($this, 'setupTranslateFields'), $tab_names);
        $aViewUrls['translateformheader_view'][] = $aData;

        //Set the output as empty
        $aViewUrls['output'] = '';
        // Define content of each tab

        //iterate through all tabs
        $allTabNames = count($tab_names);
        for ($i = 0; $i < $allTabNames; $i++) {
            $type = $tab_names[$i];
            $amTypeOptions = $this->setupTranslateFields($type);
            // Setup form
            $evenRow = false; //deprecated => using css

            $all_fields_empty = true;

            $resultbase = $this->query($type, "querybase", $iSurveyID, $tolang, $baselang);
            $resultto = $this->query($type, "queryto", $iSurveyID, $tolang, $baselang);

            $type2 = $amTypeOptions["associated"];
            $associated = false;
            if (!empty($type2)) {
                $associated = true;
                //get type otions again again
                $amTypeOptions2 = $this->setupTranslateFields($type2);
                $resultbase2 = $this->query($type, "querybase", $iSurveyID, $tolang, $baselang);
                $resultto2 = $this->query($type, "queryto", $iSurveyID, $tolang, $baselang);
            } else {
                $resultbase2 = $resultbase;
                $resultto2 = $resultto;
            }

            $aData['type'] = $type;
            $aData['activeTab'] = ($i < 1);
            $aData['translateTabs'] = $this->displayTranslateFieldsHeader($baselangdesc, $tolangdesc, $type);
            $aViewUrls['output'] .= $this->getController()->renderPartial("/admin/translate/translatetabs_view", $aData, true);

            $countResultBase = count($resultbase);
            for ($j = 0; $j < $countResultBase; $j++) {
                $oRowfrom = $resultbase[$j];
                $oResultBase2 = $resultbase2[$j];
                $oResultTo = $resultto[$j];
                $oResultTo2 = $resultto2[$j];

                $aRowfrom = array();
                $aResultBase2 = array();
                $aResultTo = array();
                $aResultTo2 = array();

                $class = get_class($oRowfrom);
                if ($class == 'QuestionGroup') {
                    $aRowfrom = $oRowfrom->questiongroupl10ns[$baselang]->getAttributes();
                    $aResultBase2 = !empty($type2) ? $oResultBase2->questiongroupl10ns[$baselang]->getAttributes() : $aRowfrom;
                    $aResultTo = $oResultTo->questiongroupl10ns[$tolang]->getAttributes();
                    $aResultTo2 = !empty($type2) ? $oResultTo2->questiongroupl10ns[$tolang]->getAttributes() : $aResultTo;
                } elseif ($class == 'Question' || $class == 'Subquestion') {
                    $aRowfrom = $oRowfrom->questionl10ns[$baselang]->getAttributes();
                    if (!empty($oRowfrom['parent_qid'])) {
                        $aRowfrom['parent'] = $oRowfrom->parent->getAttributes();
                    }
                    $aResultBase2 = !empty($type2) ? $oResultBase2->questionl10ns[$baselang]->getAttributes() : $aRowfrom;
                    $aResultTo = $oResultTo->questionl10ns[$tolang]->getAttributes();
                    $aResultTo2 = !empty($type2) ? $oResultTo2->questionl10ns[$tolang]->getAttributes() : $aResultTo;
                } elseif ($class == 'Answer') {
                    $aRowfrom = $oRowfrom->answerl10ns[$baselang]->getAttributes();
                    $aResultBase2 = !empty($type2) ? $oResultBase2->answerl10ns[$baselang]->getAttributes() : $aRowfrom;
                    $aResultTo = $oResultTo->answerl10ns[$tolang]->getAttributes();
                    $aResultTo2 = !empty($type2) ? $oResultTo2->answerl10ns[$tolang]->getAttributes() : $aResultTo;
                }
                $aRowfrom = array_merge($aRowfrom, $oRowfrom->getAttributes());
                $aResultBase2 = array_merge($aResultBase2, $oResultBase2->getAttributes());
                $aResultTo = array_merge($aResultTo, $oResultTo->getAttributes());
                $aResultTo2 = array_merge($aResultTo2, $oResultTo2->getAttributes());

                $textfrom = htmlspecialchars_decode($aRowfrom[$amTypeOptions["dbColumn"]]);
//
                $textto = $aResultTo[$amTypeOptions["dbColumn"]];
                if ($associated) {
                    $textfrom2 = htmlspecialchars_decode($aResultBase2[$amTypeOptions2["dbColumn"]]);
                    $textto2 = $aResultTo2[$amTypeOptions2["dbColumn"]];
                }

                $gid = ($amTypeOptions["gid"] == true) ? $gid = $aRowfrom['gid'] : null;
                $qid = ($amTypeOptions["qid"] == true) ? $qid = $aRowfrom['qid'] : null;

                $textform_length = strlen(trim($textfrom));

                $all_fields_empty = !($textform_length > 0);

                $aData = array_merge($aData, array(
                                'textfrom' => $this->cleanup($textfrom, array()),
                                'textfrom2' => $this->cleanup($textfrom2, array()),
                                'textto' => $this->cleanup($textto, array()),
                                'textto2' => $this->cleanup($textto2, array()),
                                'rowfrom' => $aRowfrom,
                                'rowfrom2' => $aResultBase2,
                                'evenRow' => $evenRow,
                                'gid' => $gid,
                                'qid' => $qid,
                                'amTypeOptions' => $amTypeOptions,
                                'amTypeOptions2' => $amTypeOptions2,
                                'i' => $j,
                                'type' => $type,
                                'type2' => $type2,
                                'associated' => $associated,
                            ));

                $aData['translateFields'] = $this->displayTranslateFields(
                    $iSurveyID,
                    $gid,
                    $qid,
                    $type,
                    $amTypeOptions,
                    $baselangdesc,
                    $tolangdesc,
                    $textfrom,
                    $textto,
                    $j,
                    $aRowfrom,
                    $evenRow
                );
                if ($associated && strlen(trim((string) $textfrom2)) > 0) {
                    $aData['translateFields'] .= $this->displayTranslateFields(
                        $iSurveyID,
                        $gid,
                        $qid,
                        $type2,
                        $amTypeOptions2,
                        $baselangdesc,
                        $tolangdesc,
                        $textfrom2,
                        $textto2,
                        $j,
                        $aResultBase2,
                        $evenRow
                    );
                }

                $aViewUrls['output'] .= $this->getController()->renderPartial("/admin/translate/translatefields_view", $aData, true);
            } // end while

            $aData['all_fields_empty'] = $all_fields_empty;
            $aData['translateFieldsFooter'] = $this->displayTranslateFieldsFooter();
            $aData['bReadOnly'] = !Permission::model()->hasSurveyPermission($iSurveyID, 'translations', 'update');
            $aViewUrls['output'] .= $this->getController()->renderPartial("/admin/translate/translatefieldsfooter_view", $aData, true);
        } // end foreach
        // Submit buttonrender
        $aViewUrls['translatefooter_view'][] = $aData;
        // var_dump($aViewUrls);
        return $aViewUrls;
    }

    /**
     * showTranslateAdminmenu() creates the main menu options for the survey translation page
     * @param string $iSurveyID The survey ID
     * @param string $survey_title @deprecated
     * @param string $tolang
     * @return string
     */
    private function showTranslateAdminmenu($iSurveyID, $survey_title, $tolang)
    {
        return $this->getLanguageList($iSurveyID, $tolang);
    }

    /*
    * getLanguageList() returns survey language list
    * @param string $iSurveyID Survey id
    * @param string $tolang The target translation code
    */
    private function getLanguageList($iSurveyID, $tolang)
    {
        $language_list = "";
        $oSurvey = Survey::model()->findByPk($iSurveyID);

        $langs = $oSurvey->additionalLanguages;
        $supportedLanguages = getLanguageData(false, Yii::app()->session['adminlang']);

        $language_list .= CHtml::openTag('div', array('class' => 'form-group')); // Opens .menubar-right div

        $language_list .= CHtml::tag('label', array('for' => 'translationlanguage', 'class' => 'control-label'), gT("Translate to") . ":");
        $language_list .= CHtml::openTag(
            'select',
            array(
                'id' => 'translationlanguage',
                'name' => 'lang',
                'class' => 'form-control',
                'onchange' => "$(this).closest('form').submit();"
            )
        );
        if (count($oSurvey->additionalLanguages) > 1) {
            $language_list .= CHtml::tag(
                'option',
                array(
                    'selected' => empty($tolang),
                    'value' => ''
                ),
                gT("Please choose...")
            );
        }

        foreach ($langs as $lang) {
            $tolangtext = $supportedLanguages[$lang]['description'];
            $language_list .= CHtml::tag(
                'option',
                array(
                    'selected' => ($tolang == $lang),
                    'value' => $lang
                ),
                $tolangtext
            );
        }
        $language_list .= CHtml::closeTag('select');
        $language_list .= CHtml::closeTag('div'); // form-group

        return $language_list;
    }

    private function cleanup($string, $options = array())
    {
        if (extension_loaded('tidy')) {
            $oTidy = new tidy();

            $cleansedString = $oTidy->repairString($string, array(), 'utf8');
        } else {
            //We should check for tidy on Installation!
            $cleansedString = $string;
        }

        return $cleansedString;
    }

    /**
     * setupTranslateFields() creates a customised array with database query
     * information for use by survey translation
     * @param string $type Type of database field that is being translated, e.g. title, question, etc.
     * @return array
     */
    private function setupTranslateFields($type)
    {
        $aData = array();

        switch ($type) {
            case 'title':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_title',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Survey title and description"),
                    'HTMLeditorType' => "title",
                    'HTMLeditorDisplay' => "Inline",
                    'associated' => "description"
                );
                break;

            case 'description':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_description',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Description:"),
                    'HTMLeditorType' => "description",
                    'HTMLeditorDisplay' => "Inline",
                    'associated' => ""
                );
                break;

            case 'welcome':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_welcometext',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Welcome and end text"),
                    'HTMLeditorType' => "welcome",
                    'HTMLeditorDisplay' => "Inline",
                    'associated' => "end"
                );
                break;

            case 'end':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_endtext',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("End message:"),
                    'HTMLeditorType' => "end",
                    'HTMLeditorDisplay' => "Inline",
                    'associated' => ""
                );
                break;

            case 'group':
                $aData = array(
                    'type' => 2,
                    'dbColumn' => 'group_name',
                    'id1' => 'gid',
                    'id2' => '',
                    'gid' => true,
                    'qid' => false,
                    'description' => gT("Question groups"),
                    'HTMLeditorType' => "group",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => "group_desc"
                );
                break;

            case 'group_desc':
                $aData = array(
                    'type' => 2,
                    'dbColumn' => 'description',
                    'id1' => 'gid',
                    'id2' => '',
                    'gid' => true,
                    'qid' => false,
                    'description' => gT("Group description"),
                    'HTMLeditorType' => "group_desc",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'question':
                $aData = array(
                    'type' => 3,
                    'dbColumn' => 'question',
                    'id1' => 'qid',
                    'id2' => '',
                    'gid' => true,
                    'qid' => true,
                    'description' => gT("Questions"),
                    'HTMLeditorType' => "question",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => "question_help"
                );
                break;

            case 'question_help':
                $aData = array(
                    'type' => 3,
                    'dbColumn' => 'help',
                    'id1' => 'qid',
                    'id2' => '',
                    'gid' => true,
                    'qid' => true,
                    'description' => gT("Question help"),
                    'HTMLeditorType' => "question_help",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'subquestion':
                $aData = array(
                    'type' => 4,
                    'dbColumn' => 'question',
                    'id1' => 'qid',
                    'id2' => '',
                    'gid' => true,
                    'qid' => true,
                    'description' => gT("Subquestions"),
                    'HTMLeditorType' => "question",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'answer': // TODO not touched
                $aData = array(
                    'type' => 5,
                    'dbColumn' => 'answer',
                    'id1' => 'qid',
                    'id2' => 'code',
                    'scaleid' => 'scale_id',
                    'gid' => false,
                    'qid' => true,
                    'description' => gT("Answer options"),
                    'HTMLeditorType' => "subquestion",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'emailinvite':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_invite_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Invitation email subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emailinvitebody"
                );
                break;

            case 'emailinvitebody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_invite',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Invitation email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'emailreminder':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_remind_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Reminder email subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emailreminderbody"
                );
                break;

            case 'emailreminderbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_remind',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Reminder email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'emailconfirmation':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_confirm_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Confirmation email subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emailconfirmationbody"
                );
                break;

            case 'emailconfirmationbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_confirm',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Confirmation email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'emailregistration':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_register_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Registration email subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emailregistrationbody"
                );
                break;

            case 'emailregistrationbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_register',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Registration email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'email_confirm':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_confirm_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Confirmation email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "email_confirmbody"
                );
                break;

            case 'email_confirmbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'surveyls_email_confirm',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Confirmation email"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => ""
                );
                break;

            case 'emailbasicadminnotification':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'email_admin_notification_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Basic admin notification subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emailbasicadminnotificationbody"
                );
                break;

            case 'emailbasicadminnotificationbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'email_admin_notification',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Basic admin notification"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;

            case 'emaildetailedadminnotification':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'email_admin_responses_subj',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Detailed admin notification subject"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "",
                    'associated' => "emaildetailedadminnotificationbody"
                );
                break;

            case 'emaildetailedadminnotificationbody':
                $aData = array(
                    'type' => 1,
                    'dbColumn' => 'email_admin_responses',
                    'id1' => '',
                    'id2' => '',
                    'gid' => false,
                    'qid' => false,
                    'description' => gT("Detailed admin notification"),
                    'HTMLeditorType' => "email",
                    'HTMLeditorDisplay' => "Modal",
                    'associated' => ""
                );
                break;
        }
        return $aData;
    }

    /**
     * @param string $action
     * @param string $type
     */
    private function query($type, $action, $iSurveyID, $tolang, $baselang, $id1 = "", $id2 = "", $iScaleID = "", $new = "")
    {
        $amTypeOptions = array();
        // TODO: Fallthru on purpose or not?
        switch ($action) {
            case "queryto":
                $baselang = $tolang;
                /* FALLTHRU */
            case "querybase":
                switch ($type) {
                    case 'title':
                    case 'description':
                    case 'welcome':
                    case 'end':
                    case 'emailinvite':
                    case 'emailinvitebody':
                    case 'emailreminder':
                    case 'emailreminderbody':
                    case 'emailconfirmation':
                    case 'emailconfirmationbody':
                    case 'emailregistration':
                    case 'emailregistrationbody':
                    case 'email_confirm':
                    case 'email_confirmbody':
                    case 'emailbasicadminnotification':
                    case 'emailbasicadminnotificationbody':
                    case 'emaildetailedadminnotification':
                    case 'emaildetailedadminnotificationbody':
                        return SurveyLanguageSetting::model()->resetScope()->findAllByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $baselang));
                    case 'group':
                    case 'group_desc':
                        return QuestionGroup::model()->with('questiongroupl10ns', array('condition' => 'language = ' . $baselang))->findAllByAttributes(array('sid' => $iSurveyID), array('order' => 't.gid'));
                    case 'question':
                    case 'question_help':
                        return Question::model()->with('questionl10ns', array('condition' => 'language = ' . $baselang))->with('parent', 'group')->findAllByAttributes(array('sid' => $iSurveyID, 'parent_qid' => 0), array('order' => 'group_order, t.question_order, t.scale_id'));
                    case 'subquestion':
                        return Question::model()
                        ->with('questionl10ns', array('condition' => 'language = ' . $baselang))
                        ->with('parent', array('condition' => 'language = ' . $baselang))
                        ->with('group', array('condition' => 'language = ' . $baselang))
                        ->findAllByAttributes(array('sid' => $iSurveyID), array('order' => 'group_order, parent.question_order, t.scale_id, t.question_order', 'condition' => 't.parent_qid>0', 'params' => array()));
                    case 'answer':
                        return Answer::model()
                        ->with('answerl10ns', array('condition' => 'language = ' . $baselang))
                        ->with('question')
                        ->with('group')
                        ->findAllByAttributes(array(), array('order' => 'group_order, question.question_order, t.scale_id, t.sortorder', 'condition' => 'question.sid=:sid', 'params' => array(':sid' => $iSurveyID)));
                }
                /* FALLTHRU */
            case "queryupdate":
                switch ($type) {
                    case 'title':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_title' => substr($new, 0, 200)));
                    case 'description':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_description' => $new));
                    case 'welcome':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_welcometext' => $new));
                    case 'end':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_endtext' => $new));
                    case 'emailinvite':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_invite_subj' => $new));
                    case 'emailinvitebody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_invite' => $new));
                    case 'emailreminder':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_remind_subj' => $new));
                    case 'emailreminderbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_remind' => $new));
                    case 'emailconfirmation':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_confirm_subj' => $new));
                    case 'emailconfirmationbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_confirm' => $new));
                    case 'emailregistration':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_register_subj' => $new));
                    case 'emailregistrationbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_register' => $new));
                    case 'emailbasicadminnotification':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('email_admin_notification_subj' => $new));
                    case 'emailbasicadminnotificationbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('email_admin_notification' => $new));
                    case 'emaildetailedadminnotification':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('email_admin_responses_subj' => $new));
                    case 'emaildetailedadminnotificationbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('email_admin_responses' => $new));
                    case 'email_confirm':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_confirm_subject' => $new));
                    case 'email_confirmbody':
                        return SurveyLanguageSetting::model()->updateByPk(array('surveyls_survey_id' => $iSurveyID, 'surveyls_language' => $tolang), array('surveyls_email_confirm' => $new));
                    case 'group':
                        return QuestionGroupL10n::model()->updateAll(array('group_name' => mb_substr($new, 0, 100)), 'gid = :gid and language = :language', array(':gid' => $id1, ':language' => $tolang));
                    case 'group_desc':
                        return QuestionGroupL10n::model()->updateAll(array('description' => $new), 'gid = :gid and language = :language', array(':gid' => $id1, ':language' => $tolang));
                    case 'question':
                        return QuestionL10n::model()->updateAll(array('question' => $new), 'qid = :qid and language = :language', array(':qid' => $id1, ':language' => $tolang));
                    case 'question_help':
                        return QuestionL10n::model()->updateAll(array('help' => $new), 'qid = :qid and language = :language', array(':qid' => $id1, ':language' => $tolang));
                    case 'subquestion':
                        return QuestionL10n::model()->updateAll(array('question' => $new), 'qid = :qid and language = :language', array(':qid' => $id1, ':language' => $tolang));
                    case 'answer':
                        $oAnswer = Answer::model()->find('qid = :qid and code = :code and scale_id = :scale_id', array(':qid' => $id1, ':code' => $id2, ':scale_id' => $iScaleID));
                        return AnswerL10n::model()->updateAll(array('answer' => $new), 'aid = :aid and language = :language', array(':aid' => $oAnswer->aid, ':language' => $tolang));
                }
        }
    }

    /**
     * displayTranslateFieldsHeader() Formats and displays header of translation fields table
     * @param string $baselangdesc The source translation language, e.g. "English"
     * @param string $tolangdesc The target translation language, e.g. "German"
     * @param string $type
     * @return string $translateoutput
     */
    private function displayTranslateFieldsHeader($baselangdesc, $tolangdesc, $type)
    {

        $translateoutput = "<table class='table table-striped'>";
            $translateoutput .= '<thead>';
            $threeRows = ($type == 'question' || $type == 'subquestion' || $type == 'question_help' || $type == 'answer');
            $translateoutput .= $threeRows ? '<th class="col-md-2 text-strong">' . gT('Question code / ID') . "</th>" : '';
            $translateoutput .= '<th class="' . ($threeRows ? "col-sm-5 text-strong" : "col-sm-6") . '" >' . $baselangdesc . "</th>";
            $translateoutput .= '<th class="' . ($threeRows ? "col-sm-5 text-strong" : "col-sm-6") . '" >' . $tolangdesc . "</th>";
            $translateoutput .= '</thead>';

        return $translateoutput;
    }

    /**
     * displayTranslateFields() Formats and displays translation fields (base language as well as to language)
     * @param string $iSurveyID Survey id
     * @param string $gid Group id
     * @param string $qid Question id
     * @param string $type Type of database field that is being translated, e.g. title, question, etc.
     * @param array $amTypeOptions Array containing options associated with each $type
     * @param string $baselangdesc The source translation language, e.g. "English"
     * @param string $tolangdesc The target translation language, e.g. "German"
     * @param string $textfrom The text to be translated in source language
     * @param string $textto The text to be translated in target language
     * @param integer $i Counter
     * @param string $rowfrom Contains current row of database query
     * @param boolean $evenRow TRUE for even rows, FALSE for odd rows
     * @return string $translateoutput
     */
    private function displayTranslateFields(
        $iSurveyID,
        $gid,
        $qid,
        $type,
        $amTypeOptions,
        $baselangdesc,
        $tolangdesc,
        $textfrom,
        $textto,
        $i,
        $rowfrom,
        $evenRow
    ) {
        $translateoutput = "";
        $translateoutput .= "<tr>";
            $value1 = (!empty($amTypeOptions["id1"])) ? $rowfrom[$amTypeOptions["id1"]] : "";
            $value2 = (!empty($amTypeOptions["id2"])) ? $rowfrom[$amTypeOptions["id2"]] : "";
            $iScaleID = (!empty($amTypeOptions["scaleid"])) ? $rowfrom[$amTypeOptions["scaleid"]] : "";
            // Display text in original language
            // Display text in foreign language. Save a copy in type_oldvalue_i to identify changes before db update
        if ($type == 'answer') {
            $translateoutput .= "<td class='col-sm-2'>" . htmlspecialchars($rowfrom['answer']) . " (" . $rowfrom['qid'] . ") </td>";
        }
        if ($type == 'question_help' || $type == 'question') {
            $translateoutput .= "<td class='col-sm-2'>" . htmlspecialchars($rowfrom['title']) . " ({$rowfrom['qid']}) </td>";
        } elseif ($type == 'subquestion') {
            $translateoutput .= "<td class='col-sm-2'>" . htmlspecialchars($rowfrom['parent']['title']) . " ({$rowfrom['parent']['qid']}) </td>";
        }

            $translateoutput .= "<td class='_from_ col-sm-5' id='" . $type . "_from_" . $i . "'><div class='question-text-from'>"
                                    . showJavaScript($textfrom)
                                . "</div></td>";

            $translateoutput .= "<td class='col-sm-5'>";

            $translateoutput .= CHtml::hiddenField("{$type}_id1_{$i}", $value1);
            $translateoutput .= CHtml::hiddenField("{$type}_id2_{$i}", $value2);
        if (is_numeric($iScaleID)) {
            $translateoutput .= CHtml::hiddenField("{$type}_scaleid_{$i}", $iScaleID);
        }
            $nrows = max($this->calcNRows($textfrom), $this->calcNRows($textto));
            $translateoutput .= CHtml::hiddenField("{$type}_oldvalue_{$i}", $textto);

            $minHeight = 'auto';
        if ($amTypeOptions["HTMLeditorDisplay"] == "Popup") {
            $minHeight = "25px";
        } elseif ($amTypeOptions["HTMLeditorDisplay"] == "Modal") {
            $minHeight = "30px";
        }
            $aDisplayOptions = array(
                'class' => 'col-sm-10',
                'cols' => '75',
                'rows' => $nrows,
                'readonly' => !Permission::model()->hasSurveyPermission($iSurveyID, 'translations', 'update')
            );
            if ($type == 'group') {
                $aDisplayOptions['maxlength'] = 100;
            }

            $translateoutput .= CHtml::textArea("{$type}_newvalue_{$i}", $textto, $aDisplayOptions);
            $htmleditor_data = array(
                "edit" . $type,
                $type . "_newvalue_" . $i,
                htmlspecialchars($textto),
                $iSurveyID,
                $gid,
                $qid,
                "translate" . $amTypeOptions["HTMLeditorType"]
            );
            $translateoutput .= $this->loadEditor($amTypeOptions, $htmleditor_data);

            $translateoutput .= "</td>";
            $translateoutput .= "</tr>";

            return $translateoutput;
    }

    /**
     * @param $htmleditor
     * @param string[] $aData
     * @return mixed
     */
    private function loadEditor($htmleditor, $aData)
    {
        $editor_function = "";
        $displayType = strtolower($htmleditor["HTMLeditorDisplay"]);
        $displayTypeIsEmpty = empty($displayType);

        if ($displayType == "inline" || $displayTypeIsEmpty) {
            $editor_function = "getEditor";
        } elseif ($displayType == "popup") {
            $editor_function = "getPopupEditor";
            $aData[2] = urlencode($htmleditor['description']);
        } elseif ($displayType == "modal") {
            $editor_function = "getModalEditor";
            $aData[2] = $htmleditor['description'];
        }
        return call_user_func_array($editor_function, $aData);
    }

    /**
     * calcNRows($subject) calculates the vertical size of textbox for survey translation.
     * The function adds the number of line breaks <br /> to the number of times a string wrap occurs.
     * @param string $subject The text string that is being translated
     * @return double
     */
    private function calcNRows($subject)
    {
        // Determines the size of the text box
        // A proxy for box sixe is string length divided by 80
        $pattern = "(<br..?>)";
        $pattern = '[(<br..?>)|(/\n/)]';

        $nrows_newline = preg_match_all($pattern, $subject, $matches);

        $subject_length = strlen((string) $subject);
        $nrows_char = ceil($subject_length / 80);

        return $nrows_newline + $nrows_char;
    }

    /**
     * displayTranslateFieldsFooter() Formats and displays footer of translation fields table
     * @return string $translateoutput
     */
    private function displayTranslateFieldsFooter()
    {
        $translateoutput = "</table>";
        return $translateoutput;
    }

    /**
     * menuItem() creates a menu item with text and image in the admin screen menus
     * @param string $jsMenuText
     * @param string $menuImageText
     * @param string $menuIconClasses
     * @param string $scriptname
     * @return string
     */
    private function menuItem($jsMenuText, $menuImageText, $menuIconClasses, $scriptname)
    {
        //$imageurl = Yii::app()->getConfig("adminimageurl");

        //$img_tag = CHtml::image($imageurl . "/" . $menuImageFile, $jsMenuText, array('name'=>$menuImageText));
        $icon_tag = '<span class="' . $menuIconClasses . '"></span>' . $jsMenuText;
        $menuitem = CHtml::link($icon_tag, '#', array(
            'onclick' => "window.open('{$scriptname}', '_top')"
        ));
        return $menuitem;
    }

    /**
     * menuSeparator() creates a separator bar in the admin screen menus
     * @return string
     */
    private function menuSeparator()
    {

        $imageurl = Yii::app()->getConfig("adminimageurl");

        $image = CHtml::image($imageurl . "/separator.gif", '');
        return $image;
    }

    public function ajaxtranslategoogleapi()
    {
        // Ensure YII_CSRF_TOKEN, we are in admin, then only user with admin rigth can post
        /* No Permission check on survey, seems unneded (return a josn with current string posted */
        if (Yii::app()->request->isPostRequest) {
            echo self::translateGoogleApi();
        }
    }

    /*
     * translateGoogleApi.php
     * Creates a JSON interface for the auto-translate feature
     */
    private function translateGoogleApi()
    {
        $sBaselang   = Yii::app()->getRequest()->getPost('baselang');
        $sTolang     = Yii::app()->getRequest()->getPost('tolang');
        $sToconvert  = Yii::app()->getRequest()->getPost('text');

        $aSearch     = array('zh-Hans', 'zh-Hant-HK', 'zh-Hant-TW', 'nl-informal', 'de-informal', 'de-easy', 'it-formal', 'pt-BR', 'es-MX', 'nb', 'nn');
        $aReplace    = array('zh-CN', 'zh-TW', 'zh-TW', 'nl', 'de', 'de', 'it', 'pt', 'es', 'no', 'no');
        $sBaselang = str_replace($aSearch, $aReplace, $sBaselang);
        $sTolang = str_replace($aSearch, $aReplace, $sTolang);

        $error = false;

        try {
            require_once(APPPATH . '/third_party/gtranslate-api/GTranslate.php');
            $gtranslate = new Gtranslate();
            $objGt = $gtranslate;

            // Gtranslate requires you to run function named XXLANG_to_XXLANG
            $sProcedure = $sBaselang . "_to_" . $sTolang;

            $parts = LimeExpressionManager::SplitStringOnExpressions($sToconvert);

            $sparts = array();
            foreach ($parts as $part) {
                if ($part[2] == 'EXPRESSION') {
                    $sparts[] = $part[0];
                } else {
                    $convertedPart = $objGt->$sProcedure($part[0]);
                    $convertedPart  = str_replace("<br>", "\r\n", $convertedPart);
                    $convertedPart  = html_entity_decode(stripcslashes($convertedPart));
                    $sparts[] = $convertedPart;
                }
            }
            $sOutput = implode(' ', $sparts);
        } catch (GTranslateException $ge) {
            // Get the error message and build the ouput array
            $error = true;
            $sOutput = $ge->getMessage();
        }

        $aOutput = array(
            'error'     =>  $error,
            'baselang'  =>  $sBaselang,
            'tolang'    =>  $sTolang,
            'converted' =>  $sOutput
        );

        header('Content-type: application/json');
        return ls_json_encode($aOutput);
    }

    /**
     * Renders template(s) wrapped in header and footer
     *
     * @param string $sAction Current action, the folder to fetch views from
     * @param string|array $aViewUrls View url(s)
     * @param array $aData Data to be passed on. Optional.
     */
    protected function renderWrappedTemplate($sAction = 'translate', $aViewUrls = array(), $aData = array(), $sRenderFile = false)
    {
        $aData['display']['menu_bars'] = false;
        parent::renderWrappedTemplate($sAction, $aViewUrls, $aData, $sRenderFile);
    }
}
