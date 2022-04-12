<?php

/**
 * RenderClass for Boilerplate Question
 *  * The ia Array contains the following
 *  0 => string qid
 *  1 => string sgqa
 *  2 => string questioncode
 *  3 => string question
 *  4 => string type
 *  5 => string gid
 *  6 => string mandatory,
 *  7 => string conditionsexist,
 *  8 => string usedinconditions
 *  0 => string used in group.php for question count
 * 10 => string new group id for question in randomization group (GroupbyGroup Mode)
 *
 */
class RenderArrayFlexibleRow extends QuestionBaseRenderer
{
    private $aMandatoryViolationSubQ;
    private $repeatheadings;
    private $minrepeatheadings;
    private $defaultWidth;
    private $columnswidth;
    private $answerwidth;
    private $cellwidth;
    private $sHeaders;
    
    private $rightExists;
    private $bUseDropdownLayout = false;

    private $inputnames = [];

    public $sCoreClass = "ls-answers subquestion-list questions-list";

    public function __construct($aFieldArray, $bRenderDirect = false)
    {
        parent::__construct($aFieldArray, $bRenderDirect);

        $aLastMoveResult         = LimeExpressionManager::GetLastMoveResult();
        $this->aMandatoryViolationSubQ = ($aLastMoveResult['mandViolation'] && $this->oQuestion->mandatory == 'Y') ? explode("|", $aLastMoveResult['unansweredSQs']) : [];
        
        $this->repeatheadings    = Yii::app()->getConfig("repeatheadings");
        $this->minrepeatheadings = Yii::app()->getConfig("minrepeatheadings");

        if (ctype_digit($this->repeatheadings) && !empty($this->repeatheadings)) {
            $this->repeatheadings    = intval($this->getQuestionAttribute('repeat_headings'));
            $this->minrepeatheadings = 0;
        }
    
    
        if ($this->getQuestionAttribute('use_dropdown') == 1) {
            $this->bUseDropdownLayout = true;
            $this->sCoreClass .= " dropdown-array";
            // I suppose this is irrelevant and if not, why t** f*** is there hardcoded text in the renderer function?
            //$caption           = gT("A table with a subquestion on each row. You have to select your answer.");
        } else {
            $this->bUseDropdownLayout = false;
            $this->sCoreClass .= " radio-array";
            // I suppose this is irrelevant and if not, why t** f*** is there hardcoded text in the renderer function?
            //$caption           = gT("A table with a subquestion on each row. The answer options are contained in the table header.");
        }
        
        $this->setSubquestions();
        $this->setAnsweroptions();

        $iCount = array_reduce($this->aSubQuestions[0], function ($combined, $oSubQuestions) {
            if (preg_match("/^[^|]+\|[^|]+$/", $oSubQuestions->questionl10ns[$this->sLanguage]->question)) {
                $combined++;
            }
            return $combined;
        }, 0);
        // $right_exists is a flag to find out if there are any right hand answer parts.
        // If there arent we can leave out the right td column
        $this->rightExists = ($iCount > 0);

        if (ctype_digit(trim($this->getQuestionAttribute('answer_width')))) {
            $this->answerwidth  = trim($this->getQuestionAttribute('answer_width'));
            $this->defaultWidth = false;
        } else {
            $this->answerwidth = 33;
            $this->defaultWidth = true;
        }

        $this->columnswidth = 100 - $this->answerwidth;

        if ($this->rightExists) {
        /* put the right answer to same width : take place in answer width only if it's not default */
            if ($this->defaultWidth) {
                $this->columnswidth -= $this->answerwidth;
            } else {
                $this->answerwidth = $this->answerwidth / 2;
            }
        }
        if ($this->getQuestionCount() > 0) {
            $this->cellwidth = round(($this->columnswidth / $this->getAnswerCount()), 1);
        }
        $this->setHeaders();
    }

    public function getMainView($forTwig = false)
    {
        return $this->bUseDropdownLayout
            ? '/survey/questions/answer/arrays/array/dropdown'
            : '/survey/questions/answer/arrays/array/no_dropdown';
    }

    public function setHeaders()
    {
        $sHeader = '';
        if ($this->bUseDropdownLayout) {
            $this->sHeaders =  $sHeader;
            return;
        }

        $sHeader  .= Yii::app()->twigRenderer->renderQuestion(
            $this->getMainView() . '/rows/cells/header_information',
            [
                'class'   => '',
                'content' => '',
            ]
        );

        foreach ($this->aAnswerOptions[0] as $oAnswer) {
            $sHeader  .= Yii::app()->twigRenderer->renderQuestion(
                $this->getMainView() . '/rows/cells/header_answer',
                [
                    'class'   => "answer-text",
                    'content' => $oAnswer->answerl10ns[$this->sLanguage]->answer,
                ]
            );
        }

        if ($this->rightExists) {
            $sHeader  .= Yii::app()->twigRenderer->renderQuestion(
                $this->getMainView() . '/rows/cells/header_information',
                [
                    'class'   => '',
                    'content' => '',
                ]
            );
        }

        if (($this->oQuestion->mandatory != 'Y' && SHOW_NO_ANSWER == 1)) {
            //Question is not mandatory and we can show "no answer"
            $sHeader  .= Yii::app()->twigRenderer->renderQuestion(
                $this->getMainView() . '/rows/cells/header_answer',
                [
                    'class'   => 'answer-text noanswer-text',
                    'content' => gT('No answer'),
                ]
            );
        }

        $this->sHeaders =  $sHeader;
    }

    public function getDropdownRows()
    {
        
        // $labels[] = array(
        //     'code'   => $aAnswer->code,
        //     'answer' => $aAnswer->answerl10ns[$sSurveyLanguage]->answer
        // );

        //$aAnswer->answerl10ns[$sSurveyLanguage]->answer
        $aRows = [];
        foreach ($this->aSubQuestions[0] as $i => $oQuestion) {
            $myfname        = $this->sSGQA . $oQuestion->title;
            $answertext     = $oQuestion->questionl10ns[$this->sLanguage]['question'];
            // Check the mandatory sub Q violation
            $error = (in_array($myfname, $this->aMandatoryViolationSubQ));
            $value = $this->getFromSurveySession($myfname);

            if ($this->rightExists && (strpos($oQuestion->questionl10ns[$this->sLanguage]['question'], '|') !== false)) {
                $aAnswertextArray = explode('|', $oQuestion->questionl10ns[$this->sLanguage]['question']);
                $answertextright = $aAnswertextArray[1];
                $answertext = $aAnswertextArray[0];
            } else {
                $answertextright = null;
            }

            $options = [];

            // Dropdown representation : first choice (activated) must be Please choose... if there are no actual answer
            $showNoAnswer = ($this->oQuestion->mandatory != 'Y' && SHOW_NO_ANSWER == 1); // Tag if we must show no-answer
            if ($value === '') {
                $options[] = array(
                    'text' => gT('Please choose...'),
                    'value' => '',
                    'selected' => ''
                );
                $showNoAnswer = false;
            }
            // Real options
            foreach ($this->aAnswerOptions[0] as $i => $oAnswer) {
                $options[] = array(
                    'value' => $oAnswer->code,
                    'selected' => ($value == $oAnswer->code) ? SELECTED : '',
                    'text' => $oAnswer->answerl10ns[$this->sLanguage]->answer
                );
            }
            // Add the now answer if needed
            if ($showNoAnswer) {
                $options[] = array(
                    'text' => gT('No answer'),
                    'value' => '',
                    'selected' => ($value == '') ?  SELECTED : '',
                );
            }
            unset($showNoAnswer);
            $aRows[] = array(
                'myfname'                => $myfname,
                'answertext'             => $answertext,
                'answerwidth'            => $this->answerwidth,
                'value'                  => $value,
                'error'                  => $error,
                'checkconditionFunction' => 'checkconditions',
                'right_exists'           => $this->rightExists,
                'answertextright'        => $answertextright,
                'options'                => $options,
                'odd'                    => ($i % 2), // true for odd, false for even
            );

            $this->inputnames[] = $myfname;
        }
        return $aRows;
    }

    public function getNonDropdownRows()
    {
        $aRows = [];
        foreach ($this->aSubQuestions[0] as $i => $oQuestion) {
            if (($this->repeatheadings > 0) && ($i > 0) && ($i % $this->repeatheadings == 0)) {
                if (($this->getQuestionCount() - $i + 1) >= $this->minrepeatheadings) {
                    // Close actual body and open another one
                    $aRows[] = [
                        'template' => '/survey/questions/answer/arrays/array/no_dropdown/rows/repeat_header.twig',
                        'content' => array(
                            'sHeaders' => $this->sHeaders
                        )
                    ];
                }
            }

            $myfname        = $this->sSGQA . $oQuestion->title;
            $answertext     = $oQuestion->questionl10ns[$this->sLanguage]->question;
            $answertext     = (strpos($answertext, '|') !== false) ? substr($answertext, 0, strpos($answertext, '|')) : $answertext;

            if ($this->rightExists && strpos($oQuestion->questionl10ns[$this->sLanguage]->question, '|') !== false) {
                $answertextright = substr($oQuestion->questionl10ns[$this->sLanguage]->question, strpos($oQuestion->questionl10ns[$this->sLanguage]->question, '|') + 1);
            } else {
                $answertextright = '';
            }

            $error          = (in_array($myfname, $this->aMandatoryViolationSubQ)); /* Check the mandatory sub Q violation */
            $value          = $this->getFromSurveySession($myfname);
            $aAnswerColumns = [];

            foreach ($this->aAnswerOptions[0] as $oAnswer) {
                $aAnswerColumns[] = array(
                    'myfname' => $myfname,
                    'ld' => $oAnswer->code,
                    'label' => $oAnswer->answerl10ns[$this->sLanguage]->answer,
                    'CHECKED' => ($this->getFromSurveySession($myfname) == $oAnswer->code) ? 'CHECKED' : '',
                    'checkconditionFunction' => 'checkconditions',
                    );
            }

            $aNoAnswerColumn = [];
            if (($this->oQuestion->mandatory != 'Y' && SHOW_NO_ANSWER == 1)) {
                $CHECKED = (!isset($_SESSION['survey_' . Yii::app()->getConfig('surveyID')][$myfname]) || $_SESSION['survey_' . Yii::app()->getConfig('surveyID')][$myfname] == '') ? 'CHECKED' : '';
                $aNoAnswerColumn = array(
                    'myfname'                => $myfname,
                    'ld'                     => '',
                    'label'                  => gT('No answer'),
                    'CHECKED'                => $CHECKED,
                    'checkconditionFunction' => 'checkconditions',
                );
            }

            $aRows[] = [
                "template" => "survey/questions/answer/arrays/array/no_dropdown/rows/answer_row.twig",
                "content" => array(
                    'aAnswerColumns' => $aAnswerColumns,
                    'aNoAnswerColumn' => $aNoAnswerColumn,
                    'myfname'    => $myfname,
                    'answertext' => $answertext,
                    'answerwidth' => $this->answerwidth,
                    'answertextright' => $answertextright,
                    'right_exists' => intval($this->rightExists),
                    'value'      => $value,
                    'error'      => $error,
                    'odd'        => ($i % 2), // true for odd, false for even
                )
                ];

            $this->inputnames[] = $myfname;
        }

        return $aRows;
    }

    public function getColumns()
    {
        $aColumns = [];
        $oddEven = false;
        foreach ($this->aAnswerOptions[0] as $oAnswer) {
            $aColumns[] = array(
                'class'     => $oddEven ? 'ls-col-even' : 'ls-col-odd',
                'cellwidth' => $this->cellwidth,
            );
                $oddEven = !$oddEven;
        }

        if ($this->rightExists) {
            $aColumns[] = array(
                'class'     => 'answertextright ' . ($oddEven ? 'ls-col-even' : 'ls-col-odd'),
                'cellwidth' => $this->answerwidth,
            );
            $oddEven = !$oddEven;
        }

        if (($this->oQuestion->mandatory != 'Y' && SHOW_NO_ANSWER == 1)) {
            //Question is not mandatory
            $aColumns[] = array(
                'class'     => 'col-no-answer ' . ($oddEven ? 'ls-col-even' : 'ls-col-odd'),
                'cellwidth' => $this->cellwidth,
            );
            $oddEven = !$oddEven;
        }
        return $aColumns;
    }


    public function getRows()
    {
        //return;
        return $this->bUseDropdownLayout
            ? $this->getDropdownRows()
            : $this->getNonDropdownRows();
    }

    public function render($sCoreClasses = '')
    {

        //return @do_array($this->aFieldArray);
       
        $answer = '';

        $answer .=  Yii::app()->twigRenderer->renderQuestion($this->getMainView() . '/answer', array(
            'anscount'   => $this->getQuestionCount(),
            'aRows'      => $this->getRows(),
            'aColumns'   => $this->getColumns(),
            'basename'   => $this->sSGQA,
            'answerwidth' => $this->answerwidth,
            'columnswidth' => $this->columnswidth,
            'right_exists' => $this->rightExists,
            'coreClass'  => $this->sCoreClass,
            'sHeaders'   => $this->sHeaders,
            ), true);

        $this->registerAssets();
        return array($answer, $this->inputnames);
    }

    
    protected function getAnswerCount($iScaleId = 0)
    {
        // Getting answerrcount
        $anscount  = count($this->aAnswerOptions[0]);
        $anscount  = ($this->oQuestion->other == 'Y') ? $anscount + 1 : $anscount; //COUNT OTHER AS AN ANSWER FOR MANDATORY CHECKING!
        $anscount  = ($this->oQuestion->mandatory != 'Y' && SHOW_NO_ANSWER == 1) ? $anscount + 1 : $anscount; //Count up if "No answer" is showing
        return $anscount;
    }
}
