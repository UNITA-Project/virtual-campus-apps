<?php
/**
 * @var string       $sSurveyName
 * @var int          $iSurveyId
 * @var SavedControl $model
 * @var int          $savedResponsesPageSize
 */
?>

<div class='side-body <?php echo getSideBodyClass(true); ?>'>
    <h3>
        <?php eT('Saved responses'); ?>
        <small><?php echo flattenText($sSurveyName) . ' ' . sprintf(gT('ID: %s'), $iSurveyId); ?></small>
    </h3>

    <div class="row">
        <div class="col-lg-12 content-right">
            <?php
            $this->widget('bootstrap.widgets.TbGridView', array(
                    'id'           => 'saved-grid',
                    'ajaxUpdate'   => 'saved-grid',
                    'dataProvider' => $model->search(),
                    'columns'      => $model->columns,
                    'filter'       => $model,
                    'ajaxType'     => 'POST',
                    'htmlOptions'  => ['class' => 'table-responsive grid-view-ls'],
                    'template'     => "{items}\n<div id='savedListPager'><div class=\"col-sm-4\" id=\"massive-action-container\"></div><div class=\"col-sm-4 pager-container ls-ba \">{pager}</div><div class=\"col-sm-4 summary-container\">{summary}</div></div>",
                    'emptyText'    => gT('No customizable entries found.'),
                    'summaryText'  => gT('Displaying {start}-{end} of {count} result(s).') . ' ' . sprintf(gT('%s rows per page'),
                        CHtml::dropDownList(
                            'savedResponsesPageSize',
                            $savedResponsesPageSize,
                            App()->params['pageSizeOptions'],
                            array(
                                'class'    => 'changePageSize form-control',
                                'style'    => 'display: inline; width: auto',
                                'onchange' => "$.fn.yiiGridView.update('saved-grid',{ data:{ savedResponsesPageSize: $(this).val() }});"
                            )
                        )
                    ),
                )
            );
            ?>
        </div>
    </div>
</div>
