<?php
$pageSize = Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']);
?>

<div class="ls-flex-column">
    <div class="col-12 h1"><?php eT('Survey menu') ?></div>
    <div class="ls-flex-row">
        <div class="col-12 ls-flex-item">
            <?php
            $this->widget(
                'bootstrap.widgets.TbGridView',
                [
                    'dataProvider'  => $model->search(),
                    'id'            => 'surveymenu-shortlist-grid',
                    'columns'       => $model->getShortListColumns(),
                    'emptyText'     => gT('No customizable entries found.'),
                    'htmlOptions'   => ['class' => 'table-responsive grid-view-ls'],
                    'template'      => "{items}\n<div id='surveymenushortlistListPager'><div class=\"col-sm-4\" id=\"massive-action-container\"></div><div class=\"col-sm-4 pager-container ls-ba \">{pager}</div><div class=\"col-sm-4 summary-container\">{summary}</div></div>",
                    'summaryText'   => gT('Displaying {start}-{end} of {count} result(s).') . ' ' . sprintf(
                        gT('%s rows per page'),
                        CHtml::dropDownList(
                            'surveymenushortlistPageSize',
                            $pageSize,
                            Yii::app()->params['pageSizeOptions'],
                            ['class' => 'changePageSize form-control', 'style' => 'display: inline; width: auto']
                        )
                    ),
                   'ajaxUpdate' => 'surveymenu-shortlist-grid'
                ]
            );
            ?>
        </div>
    </div>
</div>

<!-- update rows with pagination -->
<script type="text/javascript">
    jQuery(function ($) {
        $(document).on("change", '#surveymenushortlistPageSize', function () {
            $.fn.yiiGridView.update('surveymenu-shortlist-grid', {data: {pageSize: $(this).val()}});
        });
    });
</script>

