<?php
/** @var  User $oUser */

$modalTitle = $oUser->isNewRecord ? gT('Add user') : gT('Edit user');
Yii::app()->getController()->renderPartial(
    '/layouts/partial_modals/modal_header',
    ['modalTitle' => $modalTitle]
);
?>

<?php $form = $this->beginWidget('TbActiveForm', array(
    'id' => 'UserManagement--modalform',
    'action' => App()->createUrl('userManagement/applyedit'),
    'enableAjaxValidation'=>false,
    'enableClientValidation'=>false,
));?>

<div class="modal-body">
    <div class="container-center">

        <?=$form->hiddenField($oUser, 'uid', ['uid' => 'User_Form_users_id'])?>
        <div class="row ls-space margin top-5 bottom-5 hidden" id="UserManagement--errors">

        </div>
        <div class="row ls-space margin top-5">
            <?php echo $form->labelEx($oUser, 'users_name', ['for' => 'User_Form_users_name']); ?>
            <?php
                if ($oUser->isNewRecord) {
                   echo $form->textField($oUser, 'users_name', ['id' => 'User_Form_users_name', 'required' => 'required']);
                } else {
                    echo '<input class="form-control" type="text" name="usernameshim" value="'.$oUser->users_name.'" disabled="true" />';
                }
            ?>

            <?php echo $form->error($oUser, 'users_name'); ?>
        </div>
        <div class="row ls-space margin top-5">
            <?php echo $form->labelEx($oUser, 'full_name', ['for'=>'User_Form_full_name']); ?>
            <?php echo $form->textField($oUser, 'full_name', ['id'=>'User_Form_full_name']); ?>
            <?php echo $form->error($oUser, 'full_name'); ?>
        </div>
        <div class="row ls-space margin top-5">
            <?php echo $form->labelEx($oUser, 'email', ['for'=>'User_Form_email']); ?>
            <?php echo $form->emailField($oUser, 'email', ['id'=>'User_Form_email', 'required' => 'required']); ?>
            <?php echo $form->error($oUser, 'email'); ?>
        </div>
        <?php if (!$oUser->isNewRecord) { ?>
        <div class="row ls-space margin top-10">
            <div class="col-xs-12">
                <input type="checkbox" id="utility_change_password">
                <label for="utility_change_password"><?=gT("Change password?")?></label>
            </div>
        </div>
        <?php } else { ?>
            <div class="row ls-space margin top-10" id="utility_set_password">
                <div class="col-xs-6" >
                    <label><?=gT("Set password now?")?></label>
                </div>
                <div class="btn-group col-xs-6" data-toggle="buttons">
                    <label for="utility_set_password_yes" class="btn btn-default col-xs-6">
                        <input type="radio" id="utility_set_password_yes" name="preset_password" value="1">
                        <?=gT("Yes")?>
                    </label>
                    <label for="utility_set_password_no" class="btn btn-default col-xs-6 active">
                        <input type="radio" id="utility_set_password_no" checked="checked" name="preset_password" value="0">
                        <?=gT("No")?>
                    </label>
                </div>
            </div>
        <?php } ?>        

        <div class="row ls-space margin top-5 hidden" id="utility_change_password_container">
            <div class="row ls-space margin top-5">
                <span class="text-warning"><?= gT('If you set a password here, no email will be sent to the new user.')?></span><br><br>
                <?php echo $form->labelEx($oUser,'password', ['for'=>'User_Form_password']); ?>
                <?php echo $form->passwordField(
                    $oUser,
                    'password', 
                    ($oUser->isNewRecord 
                        ? ['id'=>'User_Form_password', 'value' => '', 'placeholder' => '********']
                        : ['id'=>'User_Form_password', 'value' => '', 'placeholder' => '********', "disabled" => "disabled"]
                    )
                ); ?>
                <?php echo $form->error($oUser,'password'); ?>
            </div>
            <div class="row ls-space margin top-5">
                <label for="password_repeat" class="required" required><?=gT("Password safety")?> <span class="required">*</span></label>            
                <input name="password_repeat" placeholder='********' <?=($oUser->isNewRecord ? '' :'disabled="disabled"')?> id="password_repeat" class="form-control" type="password">
            </div>
            <?php if($oUser->isNewRecord) { ?> 
            <div class="row ls-space margin top-5">
                <label class="control-label">
                    <?=gT('Random password (suggestion):')?>
                </label> 
                <input type="text" class="form-control" readonly name="random_example_password" value="<?=htmlspecialchars($randomPassword)?>"/>
            </div>
            <?php } ?>
        </div>

    </div>
</div>

<div class="modal-footer modal-footer-buttons" style="margin-top: 15px;" >
    <button class="btn btn-cancel" id="exitForm"><?=gT('Cancel')?></button>
    <button class="btn btn-success" id="submitForm"><?=gT('Add')?></button>
</div>
<?php $this->endWidget(); ?>
