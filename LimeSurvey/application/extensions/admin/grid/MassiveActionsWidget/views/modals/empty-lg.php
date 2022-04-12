<!-- Modal confirmation for <?php echo $aAction['action'];?> -->
<div id="massive-actions-modal-<?php echo $this->gridid;?>-<?php echo $aAction['action'];?>-<?php echo $key; ?>" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="modal-title h4"><?php echo $aAction['sModalTitle']; ?></div>
            </div>

            <div class="modal-body">
                <?php echo $aAction['htmlModalBody']; ?>
            </div>

            <div class="modal-footer modal-footer-buttons">
                <?php foreach ($aAction['htmlFooterButtons'] as $buttonHtml): ?>
                    <?php echo $buttonHtml; ?>
                <?php endforeach;?>
            </div>
        </div>
    </div>
</div>
