<?php
/**
 * This file render the list of groups
 */

/** @var QuestionAdministrationController $this */
/** @var Survey $oSurvey */
/** @var Question $model */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('surveyListQuestions');
$baseLanguage = $oSurvey->language;
?>
<?php $pageSize = App()->user->getState('pageSize', App()->params['defaultPageSize']);?>

<div class='side-body <?php echo getSideBodyClass(true); ?>'>
    <h3 class="ls-flex ls-flex-row">
        <?php if(App()->request->getParam('group_name')!=''):?>
            <div class="ls-flex-item text-left"><?php eT('Questions in group: '); ?> <em><?php echo App()->request->getParam('group_name'); ?></em></div>
        <?php else:?>
            <div class="ls-flex-item text-left"><?php eT('Questions in this survey'); ?></div>
        <?php endif;?>
    </h3>


    <div class="row">
        <div class="col-lg-12 content-right">

            <!-- Search Box -->
            <div class="row">
                <div class="col-lg-12 ls-flex ls-flex-row">
                    <div class="ls-flex-item text-left">
                        <?php App()->getController()->renderPartial(
                            '/admin/survey/surveybar_addgroupquestion',
                            [
                                'surveybar'      => $surveybar,
                                'oSurvey'        => $oSurvey,
                                'surveyHasGroup' => isset($surveyHasGroup) ? $surveyHasGroup : false
                            ]
                        ); ?>
                    </div>
                    <div class="ls-flex-item form  text-right">
                        <!-- Begin Form -->
                        <?php $form=$this->beginWidget('CActiveForm', array(
                            'action' => App()->createUrl(
                                'questionAdministration/listquestions',
                                ['surveyid'=>$oSurvey->primaryKey]
                            ),
                            'method' => 'get',
                                'htmlOptions'=>array(
                                    'class'=>'form-inline',
                                ),
                            )); ?>

                            <!-- search input -->
                            <div class="form-group">
                                <?php echo $form->label(
                                    $model,
                                    'search',
                                    array('label'=>gT('Search:'),'class'=>'control-label' )
                                ); ?>
                                <?php echo $form->textField($model, 'title', array('class'=>'form-control')); ?>
                            </div>

                            <!-- select group -->
                            <div class="form-group">
                                <?php echo $form->label($model, 'group', array('label'=>gT('Group:'),'class'=>'control-label')); ?>
                                    <select name="gid" class="form-control">
                                        <option value=""><?php eT('(Any group)');?></option>
                                        <?php foreach($oSurvey->groups as $group): ?>
                                            <option value="<?php echo $group->gid;?>" <?php if( $group->gid == $model->gid){echo 'selected';} ?>>
                                                <?php echo flattenText($group->questiongroupl10ns[$oSurvey->language]->group_name);?>
                                            </option>
                                        <?php endforeach?>
                                    </select>
                            </div>

                            <?php echo CHtml::submitButton(
                                gT('Search', 'unescaped'),
                                array('class'=>'btn btn-success')
                            ); ?>
                            <a href="<?php echo App()->createUrl(
                                'questionAdministration/listquestions',
                                ['surveyid'=>$oSurvey->primaryKey]
                            );?>" class="btn btn-warning"><?php eT('Reset');?></a>

                        <?php $this->endWidget(); ?>
                    </div><!-- form -->
                </div>
            </div>
            <hr/>
            <!-- Grid -->
            <div class="row ls-space margin top-10">
                <div class="col-lg-12">
                    <?php
                        $massiveAction = Yii::app()->getController()->renderPartial(
                            '/admin/survey/Question/massive_actions/_selector',
                            array('model'=>$model, 'oSurvey'=>$oSurvey),
                            true,
                            false
                        );
                        $this->widget('ext.LimeGridView.LimeGridView', array(
                            'dataProvider' => $model->search(),
                            'id'           => 'question-grid',
                            'emptyText'    => gT('No questions found.'),
                            'htmlOptions'  => ['class' => 'table-responsive grid-view-ls'],
                            'template'     => "{items}\n<div id='questionListPager'><div class=\"col-sm-4\" id=\"massive-action-container\">$massiveAction</div><div class=\"col-sm-4 pager-container ls-ba \">{pager}</div><div class=\"col-sm-4 summary-container\">{summary}</div></div>",
                            'summaryText'  => gT('Displaying {start}-{end} of {count} result(s).') . ' '
                                . sprintf(
                                    gT('%s rows per page'),
                                    CHtml::dropDownList(
                                        'pageSize',
                                        $pageSize,
                                        App()->params['pageSizeOptions'],
                                        array(
                                            'class' => 'changePageSize form-control',
                                            'style' => 'display: inline; width: auto'
                                        )
                                    )
                                ),
                            'columns' => $model->questionListColumns,
                            'ajaxUpdate' => 'question-grid',
                            'afterAjaxUpdate' => "bindPageSizeChange"
                        ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="question-preview" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?php eT("Question preview");?></h4>
      </div>
      <div class="modal-body">
          <iframe id="frame-question-preview" src="" style="zoom:0.60" width="99.6%" height="600" frameborder="0"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php eT("Close");?></button>
      </div>
    </div>
  </div>
</div>

<!-- To update rows per page via ajax -->
<?php App()->getClientScript()->registerScript("ListQuestions-pagination", "
        var bindPageSizeChange = function(){
            $('#pageSize').on('change', function(){
                $.fn.yiiGridView.update('question-grid',{ data:{ pageSize: $(this).val() }});
            });
            $(document).trigger('actions-updated');
        };
    ", LSYii_ClientScript::POS_BEGIN); ?>
    
<?php App()->getClientScript()->registerScript("ListQuestions-run-pagination", "bindPageSizeChange(); ", LSYii_ClientScript::POS_POSTSCRIPT); ?>
