<?php
$languages = $oSurvey->allLanguages;
$permissions = [];
$buttons = [];
$topbar = [
    'alignment' => [
        'left' => [
            'buttons' => [],
        ],
        'right' => [
            'buttons' => [],
        ],
    ],
];
$topbarextended = [
    'alignment' => [
        'left' => [
            'buttons' => [],
        ],
        'right' => [
            'buttons' => [],
        ],
    ],
];

// Preview Survey Button
$title = ($oSurvey->active == 'N') ? 'preview_survey' : 'execute_survey';
$name = ($oSurvey->active == 'N') ? gT('Preview survey') : gT('Run survey');

if (count($languages) > 1) {
    foreach ($languages as $language) {
        $survey_preview_buttons[$title.'_'.$language] = [
            'url' => $this->createAbsoluteUrl(
                "survey/index",
                array(
                    'sid' => $sid,
                    'newtest' => "Y",
                    'lang' => $language
                )
            ),
            'icon' => 'fa fa-cog',
            'iconclass' => 'fa fa-external-link',
            'name' => $name.' ('.getLanguageNameFromCode($language, false).')',
            'class' => ' external',
            'target' => '_blank'
        ];
    }

    $buttonsurvey_preview_dropdown = [
        'class' => 'btn-group',
        'id' => 'preview_survey_dropdown',
        'main_button' => [
            'class' => 'dropdown-toggle',
            'datatoggle' => 'dropdown',
            'ariahaspopup' => true,
            'ariaexpanded' => false,
            'icon' => 'fa fa-cog',
            'name' => $name,
            'iconclass' => 'caret',
        ],
        'dropdown' => [
            'class' => 'dropdown-menu',
            'items' => $survey_preview_buttons,
        ],
    ];
    array_push($topbar['alignment']['left']['buttons'], $buttonsurvey_preview_dropdown);
} else {
    $buttons[$title] = [
        'url' => $this->createAbsoluteUrl(
            "survey/index",
            array(
                'sid' => $sid,
                'newtest' => "Y",
            )
        ),
        'name' => $name,
        'icon' => 'fa fa-cog',
        'iconclass' => 'fa fa-external-link',
        'class' => ' external',
        'target' => '_blank'
    ];

    array_push($topbar['alignment']['left']['buttons'], $buttons[$title]);
}

// Preview Questiongroup Button
$title = 'preview_questiongroup';
$name = gT('Preview current group');

if (($hasReadPermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'update'))) {
    if (count($languages) > 1) {
        foreach ($languages as $language) {
            $questiongroup_preview_buttons[$title.'_'.$language] = [
                'url' => $this->createAbsoluteUrl(
                    "survey/index/action/previewgroup",
                    array(
                        'sid' => $sid,
                        'gid' => $gid,
                        'lang' => $language
                    )
                ),
                'id' => $title.'_'.$language,
                'icon' => 'fa fa-cog',
                'iconclass' => 'fa fa-external-link',
                'name' => $name.' ('.getLanguageNameFromCode($language, false).')',
                'class' => ' external',
                'target' => '_blank'
            ];
        }

        $buttongroup_preview_dropdown = [
            'class' => 'btn-group',
            'id' => 'preview_questiongroup_dropdown',
            'main_button' => [
                'class' => 'dropdown-toggle',
                'datatoggle' => 'dropdown',
                'ariahaspopup' => true,
                'ariaexpanded' => false,
                'icon' => 'fa fa-cog',
                'name' => $name,
                'iconclass' => 'caret',
            ],
            'dropdown' => [
                'class' => 'dropdown-menu',
                'items' => $questiongroup_preview_buttons,
            ],
        ];
        array_push($topbar['alignment']['left']['buttons'], $buttongroup_preview_dropdown);
    } else {
        $buttons[$title] = [
            'url' => $this->createAbsoluteUrl(
                "survey/index/action/previewgroup",
                array(
                    'sid' => $sid,
                    'gid' => $gid,
                )
            ),
            'id' => $title,
            'name' => $name,
            'icon' => 'fa fa-cog',
            'iconclass' => 'fa fa-external-link',
            'class' => ' external',
            'target' => '_blank'
        ];

        array_push($topbar['alignment']['left']['buttons'], $buttons[$title]);
    }
    if ($oSurvey->active === 'N') {
        // survey inactive
        $import_group_button = [
            'id'    => 'import',
            'url'   => $this->createUrl("questionGroupsAdministration/importview/surveyid/$sid"),
            'icon'  => 'icon-import',
            'name'  => gT("Import group"),
            'class' => ' btn-default ',
        ];
    } else {
        // survey active
        $import_group_button = [
            'title' => gT("You can not import groups because the survey is currently active."),
            'id'    => 'import',
            'icon'  => 'icon-import',
            'name'  => gT("Import group"),
            'class' => ' btn-default readonly ',
        ];
    }
    array_push($topbar['alignment']['left']['buttons'], $import_group_button);
}

$hasExportPermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'export');
if ($hasExportPermission) {
    $permissions['update'] = ['export' => $hasExportPermission];

    $buttons['export'] = [
        'id' => 'export',
        'url' => $this->createUrl("admin/export/sa/group/surveyid/$sid/gid/$gid"),
        'icon' => 'icon-export',
        'name' => gT("Export current group"),
        'class' => ' btn-default ',
    ];

    array_push($topbar['alignment']['left']['buttons'], $buttons['export']);
}

$hasUpdatePermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'update');
if ($hasReadPermission) {
    // Check Survey Logic Button
    $buttons['check_survey_logic'] = [
        'id' => 'check_survey_logic',
        'url' => $this->createUrl("admin/expressions/sa/survey_logic_file/sid/{$sid}/gid/{$gid}/"),
        'name' => gT("Check logic for current group"),
        'icon' => 'icon-expressionmanagercheck',
        'class' => ' ',
    ];

    array_push($topbar['alignment']['left']['buttons'], $buttons['check_survey_logic']);
}

$hasDeletePermission = Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'delete');
if ($hasDeletePermission) {
    $permissions['delete'] = ['delete' => $hasDeletePermission];

    if ($activated != "Y") {
        // has question
        if (empty($condarray)) {
            // can delete group and question
            $buttons['delete_current_question_group'] = [
                'id' => 'delete_current_question_group',
                'url' => '#',
                'dataurl' => $this->createUrl("questionGroupsAdministration/delete/", ["asJson" => true]),
                'postdata' => json_encode(['gid' => $gid]),
                'type' => 'confirm',
                'message' => gT("Deleting this group will also delete any questions and answers it contains. Are you sure you want to continue?", "js"),
                'icon' => 'fa fa-trash',
                'name' => gT("Delete current group"),
                'class' => ' btn-danger ',
            ];
        } else {
            // there is at least one question having a condition on its content
            $buttons['delete_current_question_group'] = [
                'id' => 'delete_current_question_group',
                'url' => '',
                'title' => gT("Impossible to delete this group because there is at least one question having a condition on its content"),
                'icon' => 'fa fa-trash',
                'name' => gT("Delete current group"),
                'class' => ' btn-danger readonly ',
            ];
        }
    } else {
        // Activated
        $buttons['delete_current_question_group'] = [
            'id' => 'delete_current_question_group',
            'title' => gT("You can not delete this question group because the survey is currently active."),
            'icon' => 'fa fa-trash',
            'name' => gT("Delete current group"),
            'class' => ' btn-danger readonly ',
        ];
    }
}
array_push($topbar['alignment']['left']['buttons'], $buttons['delete_current_question_group']);

// Save and Close Button
if ($ownsSaveButton == true) {
    // TODO: Not used?
    $saveAndNewLink = $this->createUrl("questionGroupsAdministration/add/", ["surveyid" => $sid]);

    $paramArray = $gid != null ? [ "surveyid" => $sid, 'gid' => $gid] : [ "surveyid" => $sid ];
    // TODO: Not used?
    $saveAndAddQuestionLink = $this->createUrl("questionAdministration/view/", $paramArray);

    $saveButton = [
        'name' => gT('Save'),
        'icon' => 'fa fa-floppy-o',
        'url' => '#',
        'id' => 'save-button',
        'isSaveButton' => true,
        'class' => 'btn-success',
    ];
    array_push($topbar['alignment']['right']['buttons'], $saveButton);
    array_push($topbarextended['alignment']['right']['buttons'], $saveButton);

    $closeButton = [
        'name' => gT('Close'),
        'icon' => 'fa fa-close',
        'url' => '#',
        'id' => 'close-button',
        'isCloseButton' => true,
        'class' => 'btn-danger',
    ];
    array_push($topbar['alignment']['right']['buttons'], $closeButton);
    array_push($topbarextended['alignment']['right']['buttons'], $closeButton);

    /*$button_save_and_add_question_group = [
        'id' => 'save-and-new-button',
        'name' => gT('Save and add group'),
        'icon' => 'fa fa-plus-square',
        'scenario' => 'save-and-new',
        'isSaveButton' => true,
        'class' => 'btn-default',
    ];
    array_push($topbarextended['alignment']['right']['buttons'], $button_save_and_add_question_group);*/

    /*$button_save_and_add_new_question = [
        'id' => 'save-and-new-question-button',
        'icon' => 'fa fa-plus',
        'name' => gT('Save and add question'),
        'scenario' => 'save-and-new-question',
        'isSaveButton' => true,
        'class' => 'btn-default',
    ];
    array_push($topbarextended['alignment']['right']['buttons'], $button_save_and_add_new_question);*/

}

$finalJSON = [
    'permission' => $permissions,
    'topbar' => $topbar,
    'topbarextended' => $topbarextended,
];

header("Content-Type: application/json");
echo json_encode($finalJSON);
