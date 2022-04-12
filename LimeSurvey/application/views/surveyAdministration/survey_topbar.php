<?php
$permissions = [];
$buttonsgroup = [];
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
$oSurvey = Survey::model()->findByPk($sid);
$isActive  = $oSurvey->active == 'Y';

// Left Buttons for Survey Summary
// TODO: SurveyBar Activation Buttons
// views/admin/survey/surveybar_activation.php
// Survey Activation
if (!$isActive) {
    $hasUpdatePermission = Permission::model()->hasSurveyPermission($sid, 'surveyactivation', 'update');
    // activate
    if ($canactivate) {
        $buttons['activate_survey'] = [
            'url' => $this->createUrl("surveyAdministration/activate/", ['iSurveyID' => $sid]),
            'name' => gT('Activate this survey'),
            'id' => 'ls-activate-survey',
            'class' => 'btn-success',
        ];
        array_push($topbar['alignment']['left']['buttons'], $buttons['activate_survey']);

        // cant activate
    } else if ($hasUpdatePermission) {
        $permissions['update'] = ['update' => $hasUpdatePermission];
        // TODO: ToolTip for cant activate survey
    }
} else {
    // activate expired survey
    if ($expired) {
        // TODO: ToolTip for expired survey
    } else if ($notstarted) {
        // TODO: ToolTip for not started survey
    }

    // <!-- Stop survey -->
    if ($canactivate) {
        $buttons['stop_survey'] = [
            'url' => $this->createUrl("surveyAdministration/deactivate/", ['iSurveyID' => $sid]),
            'class' => 'btn-danger btntooltip',
            'icon' => 'fa fa-stop-circle',
            'id' => 'ls-stop-survey',
            'name' => gT("Stop this survey"),
        ];
        array_push($topbar['alignment']['left']['buttons'], $buttons['stop_survey']);
    }
}

if ($hasSurveyContentPermission) {
    // Preview Survey Button
    $title = (!$isActive) ? 'preview_survey' : 'execute_survey';
    $name = (!$isActive) ? gT('Preview survey') : gT('Run survey');

    if (safecount($oSurvey->allLanguages) > 1) {
        $preview_buttons = [];
        foreach ($oSurvey->allLanguages as $language) {
            $preview_buttons[$title.'_'.$language] = [
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
                'id' => $title.'_'.$language,
                'name' => $name.' ('.getLanguageNameFromCode($language, false).')',
                'class' => ' external',
                'target' => '_blank'
            ];
        }

        $buttonsurvey_preview_dropdown = [
            'class' => 'btn-group',
            'id' => $title,
            'main_button' => [
                'class' => 'dropdown-toggle',
                'datatoggle' => 'dropdown',
                'ariahaspopup' => true,
                'ariaexpanded' => false,
                'icon' => 'fa fa-cog',
                'name' => $name,
                'iconclass' => 'caret',
                'id' => $title.'_button',
            ],
            'dropdown' => [
                'class' => 'dropdown-menu',
                'items' => $preview_buttons,
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
            'id' => $title.'_button',
            'name' => $name,
            'icon' => 'fa fa-cog',
            'iconclass' => 'fa fa-external-link',
            'class' => ' external',
            'target' => '_blank'
        ];

        array_push($topbar['alignment']['left']['buttons'], $buttons[$title]);
    }

}

// tools
// views/admin/surveybar_tools.php
$buttonsgroup['tools'] = [
    'class' => 'btn-group hidden-xs',
    'id' => 'tools_dropdown',
    'main_button' => [
        'id' => 'ls-tools-button',
        'class' => 'dropdown-toggle',
        'datatoggle' => 'dropdown',
        'ariahaspopup' => 'true',
        'ariaexpanded' => 'false',
        'icon' => 'icon-tools',
        'iconclass' => 'caret',
        'name' => gT('Tools'),
        'id' => 'tools_button',
    ],
    'dropdown' => [
        'class' => 'dropdown-menu',
        'arialabelledby' => 'ls-tools-button',
        'items' => [],
    ],
];

if ($hasDeletePermission) {
    $buttons['delete_survey'] = [
        'url' => $this->createUrl("surveyAdministration/delete/" , ['iSurveyID' => $sid]),
        'icon' => 'fa fa-trash',
        'name' => gT('Delete survey'),
        'id' => 'delete_button',
    ];
    array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['delete_survey']);
}

if ($hasSurveyTranslatePermission) {
    if ($hasAdditionalLanguages) {
        // Quick-translation
        $buttons['quick_translation'] = [
            'url' => $this->createUrl("admin/translate/sa/index/surveyid/{$sid}"),
            'icon' => 'fa fa-language',
            'name' => gT('Quick-translation'),
            'id' => 'quick_translation_button',
        ];
        array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['quick_translation']);
    }
}

if ($hasSurveyContentPermission) {
    if ($conditionsCount > 0) {
        // Condition
        $buttons['reset_conditions'] = [
            'url' => $this->createUrl("/admin/conditions/sa/index/subaction/resetsurveylogic/surveyid/{$sid}"),
            'icon' => 'icon-resetsurveylogic',
            'name' => gT("Reset conditions"),
            'id' => 'reset_conditions_button',
        ];
        array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['reset_conditions']);
    }
}

// TODO: menues from database

if ($hasSurveyReadPermission) {
    // Check Logic Button
    if (safecount($oSurvey->allLanguages) > 1) {
        $buttons_check_logic = [];
        foreach ($oSurvey->allLanguages as $language) {
            $buttons_check_logic[$language] = [
                'url' => $this->createAbsoluteUrl(
                    "admin/expressions/sa/survey_logic_file", 
                    array(
                        'sid' => $sid,
                        'lang' => $language
                    )
                ),
                'id' => 'check_logic_'.$language,
                'icon' => 'icon-expressionmanagercheck',
                'iconclass' => '',
                'name' => getLanguageNameFromCode($language, false),
                'class' => ' btn-default',
            ];
        }
    
        $buttonsgroup_check_logic = [
            'class' => 'btn-group',
            'id' => 'check_logic_dropdown',
            'main_button' => [
                'class' => 'dropdown-toggle',
                'datatoggle' => 'dropdown',
                'ariahaspopup' => true,
                'ariaexpanded' => false,
                'id' => 'check_logic_button',
                'icon' => 'icon-expressionmanagercheck',
                'name' => gT("Check logic"),
                'iconclass' => 'chevron-right',
            ],
            'dropdown' => [
                'class' => 'dropdown-menu',
                'items' => $buttons_check_logic,
            ],
        ];
        array_push($buttonsgroup['tools']['dropdown']['items'], $buttonsgroup_check_logic);
    } else {
        $buttons_check_logic = [
            'url' => $this->createAbsoluteUrl(
                "admin/expressions/sa/survey_logic_file", 
                    array(
                        'sid' => $sid,
                    )
            ),
            'id' => 'check_logic_button',
            'name' => gT("Check logic"),
            'icon' => 'icon-expressionmanagercheck',
            'class' => ' btn-default',
        ];
        array_push($buttonsgroup['tools']['dropdown']['items'], $buttons_check_logic);
    }
}

if (!$isActive && $hasSurveyContentPermission) {
    // Divider
    $buttons['divider'] = [
        'role' => 'seperator',
        'class' => 'divider',
        'id' => 'divider---1'
    ];
    array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['divider']);

    // Regenerate question codes
    $buttons['question_codes'] = [
        'class' => 'dropdown-header',
        'name' => gT('Regenerate question codes'),
        'id' => 'question_codes'
    ];
    array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['question_codes']);

    // Straight
    $buttons['straight'] = [
        'url' => $this->createUrl("/surveyAdministration/regenerateQuestionCodes/surveyid/{$sid}/subaction/straight"),
        'icon' => 'icon-resetsurveylogic',
        'name' => gT('Straight'),
        'id' => 'straight'
    ];
    array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['straight']);

    // By Question Group
    $buttons['by_question_group'] = [
        'url' => $this->createUrl("/surveyAdministration/regenerateQuestionCodes/surveyid/{$sid}/subaction/bygroup"),
        'name' => gT('By question group'),
        'icon' => 'icon-resetsurveylogic',
        'id' => 'by_question_group'
    ];

    array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['by_question_group']);
}

if (!empty($extraToolsMenuItems)) {
    foreach ($extraToolsMenuItems as $i => $menuItem) {
        if ($menuItem->isDivider()) {
            // Divider
            $buttons['divider' . $i] = [
                'role' => 'seperator',
                'class' => 'divider',
                'id' => 'divider---1' . $i
            ];
            array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['divider' . $i]);
        } elseif ($menuItem->isSmallText()) {
            // Regenerate question codes
            $buttons['smalltext' . $i] = [
                'class' => 'dropdown-header',
                'name' => $menuItem->getLabel()
            ];
            array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['smalltext' . $i]);
        } else {
            $buttons['plugin_button' . $i] = [
                'url' => $menuItem->getHref(),
                'icon' => $menuItem->getIconClass(),
                'name' => $menuItem->getLabel(),
                'id' => 'plugin_button' . $i
            ];
            array_push($buttonsgroup['tools']['dropdown']['items'], $buttons['plugin_button' . $i]);
        }
    }
}

array_push($topbar['alignment']['left']['buttons'], $buttonsgroup['tools']);

// Token
if ($hasSurveyTokensPermission) {
    $buttons['survey_participants'] = [
        'url' => $this->createUrl("admin/tokens/sa/index/surveyid/$sid"),
        'class' => 'pjax btntooltip',
        'icon' => 'fa fa-user',
        'name' => gT('Survey participants'),
        'id' => 'survey_participants'
    ];
    array_push($topbar['alignment']['left']['buttons'], $buttons['survey_participants']);
}

// Statistics
if ($isActive) {
    $buttonsgroup['statistics'] = [
        'class' => 'btn-group hidden-xs',
        'id' => 'statistics_dropdown',
        'main_button' => [
            'class' => 'dropdown-toggle',
            'datatoggle' => 'dropdown',
            'ariahaspopup' => 'true',
            'ariaexpanded' => 'false',
            'icon' => 'icon-responses',
            'name' => gT('Responses'),
            'iconclass' => 'caret',
            'id' => 'statistics_button',
        ],
        'dropdown' => [
            'class' => 'dropdown-menu',
            'arialabelledby' => 'statistics_button',
            'items' => [],
        ],
    ];

    // Responses & statistics
    if ($hasResponsesStatisticsReadPermission && $isActive) {
        $buttons['responses_statistics'] = [
            'class' => 'pjax',
            'url' => $this->createUrl("responses/index/", ['surveyId' => $sid]),
            'icon' => 'icon-browse',
            'name' => gT('Responses & statistics', 'js'),
            'id' => 'responses_statistics',
        ];

        array_push($buttonsgroup['statistics']['dropdown']['items'], $buttons['responses_statistics']);
    }

    // Data Entry Screen
    if ($hasResponsesCreatePermission && $isActive) {
        $buttons['data_entry_screen'] = [
            'url' => $this->createUrl("admin/dataentry/sa/view/surveyid/$sid"),
            'icon' => 'fa fa-keyboard-o',
            'name' => gT('Data entry screen'),
            'id' => 'data_entry_screen',
        ];

        array_push($buttonsgroup['statistics']['dropdown']['items'], $buttons['data_entry_screen']);
    }

    // Partial (saved) Responses
    if ($hasResponsesReadPermission && $isActive) {
        $buttons['partial_saved_responses'] = [
            'url' => $this->createUrl("admin/saved/sa/view/surveyid/$sid"),
            'icon' => 'icon-saved',
            'name' => gT('Partial (saved) responses'),
            'id' => 'partial_saved_responses',
        ];

        array_push($buttonsgroup['statistics']['dropdown']['items'], $buttons['partial_saved_responses']);
    }
    array_push($topbar['alignment']['left']['buttons'], $buttonsgroup['statistics']);
} else {
    $button_statistics = [
        'class' => 'readonly',
        'id' => 'statistics_ro',
        'url' => '#',
        'name' => gT('Responses'),
        'icon' => 'icon-responses',
        'datatoggle' => 'tooltip',
        'dataplacement' => 'bottom',
        'title' => gT('This survey is not active - no responses are available.')
    ];

    array_push($topbar['alignment']['left']['buttons'], $button_statistics);
}

if (!empty($beforeSurveyBarRender)) {
    foreach ($beforeSurveyBarRender as $i => $menu) {
        if ($menu->isDropDown()) {
            $dropdown = [
                'class' => 'btn-group hidden-xs',
                'id' => 'plugin_dropdown' . $i,
                'main_button' => [
                    'class' => 'dropdown-toggle',
                    'datatoggle' => 'dropdown',
                    'ariahaspopup' => 'true',
                    'ariaexpanded' => 'false',
                    'icon' => $menu->getIconClass(),
                    'name' => $menu->getLabel(),
                    'iconclass' => 'caret',
                    'id' => 'plugin_dropdown' . $i
                ],
                'dropdown' => [
                    'class' => 'dropdown-menu',
                    'arialabelledby' => 'plugin_dropdown' . $i,
                    'items' => [],
                ],
            ];
            foreach ($menu->getMenuItems() as $j => $item) {
                // TODO: Code duplication.
                if ($item->isDivider()) {
                    // Divider
                    $item = [
                        'role' => 'seperator',
                        'class' => 'divider',
                        'id' => 'divider---1' . $i
                    ];
                } elseif ($item->isSmallText()) {
                    // Regenerate question codes
                    $item = [
                        'class' => 'dropdown-header',
                        'name' => $item->getLabel()
                    ];
                } else {
                    $item = [
                        'url' => $item->getHref(),
                        'icon' => $item->getIconClass(),
                        'name' => $item->getLabel(),
                        'id' => 'plugin_button' . $i
                    ];
                }
                array_push($dropdown['dropdown']['items'], $item);
            }
            array_push($topbar['alignment']['left']['buttons'], $dropdown);
        } else {
            $button = [
                'class' => 'pjax',
                'id' => 'plugin_menu' . $i,
                'url' => $menu->getHref(),
                'name' => $menu->getLabel(),
                'icon' => $menu->getIconClass()
            ];
            array_push($topbar['alignment']['left']['buttons'], $button);
        }
    }
}

$buttons['save'] = [
    'name' => gT('Save'),
    'id' => 'save-button',
    'class' => 'btn-success',
    'icon' => 'fa fa-floppy-o',
    'url' => '#',
    'isSaveButton' => true
];
array_push($topbar['alignment']['right']['buttons'], $buttons['save']);

$buttons['close'] = [
    'name' => gT('Close'),
    'id' => 'close-button',
    'class' => 'btn-danger',
    'icon' => 'fa fa-times',
    'url' => '#',
    'isCloseButton' => true
];
array_push($topbar['alignment']['right']['buttons'], $buttons['close']);


$finalJSON = [
    'debug' => [
        'sid' => $sid,
        'canactivate' => $canactivate,
        'expired' => $expired,
        'notstarted' => $notstarted,
        'context' => $context,
        'contextbutton' => $contextbutton,
        'language' => $language,
        'hasSurveyContentPermission' => $hasSurveyContentPermission,
        'countLanguage' => $countLanguage,
        'hasDeletePermission' => $hasDeletePermission,
        'hasSurveyTranslatePermission' => $hasSurveyTranslatePermission,
        'hasAdditionalLanguages' => $hasAdditionalLanguages,
        'conditionsCount' => $conditionsCount,
        'hasSurveyReadPermission' => $hasSurveyReadPermission,
        'oneLanguage' => $oneLanguage,
        'sumcount' => $sumcount,
        'hasSurveyTokensPermission'    => $hasSurveyTokensPermission,
        'hasResponsesCreatePermission' => $hasResponsesCreatePermission,
        'hasResponsesReadPermission'   => $hasResponsesReadPermission,
        'hasSurveyActivationPermission'   => $hasSurveyActivationPermission,
        'addSaveButton'  => $addSaveButton,
    ],
    'permissions' => $permissions,
    'topbar' => $topbar,
];

header('Content-Type: application/json');
echo json_encode($finalJSON);
