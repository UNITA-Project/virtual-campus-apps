<?php
/**
 * This view render the main menu bar, with configuration menu
 * @var $sitename
 * @var $activesurveyscount
 * @var $dataForConfigMenu
 */
?>

<!-- admin menu bar -->
<nav class="navbar">
  <div class="navbar-header">
      <button class="navbar-toggle hidden-md hidden-lg" type="button" data-toggle="collapse" data-target="#small-screens-menus">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>


        <a class="navbar-brand" href="<?php echo $this->createUrl("/admin/"); ?>">
            <?php echo $sitename; ?>
        </a>
    </div>


    <!-- Only on xs screens -->
    <div class="collapse navbar-collapse pull-left hidden-sm  hidden-md hidden-lg" id="small-screens-menus">
        <ul class="nav navbar-nav hidden-sm  hidden-md hidden-lg small-screens-menus">

            <li><br/><br/></li>

            <!-- active surveys -->
            <?php if ($activesurveyscount > 0): ?>
                <li>
                    <a href="<?php echo $this->createUrl('surveyAdministration/listsurveys/active/Y');?>">
                        <?php eT("Active surveys");?> <span class="badge badge-success"><?php echo $activesurveyscount ?></span>
                    </a>
                </li>
            <?php endif;?>

            <!-- List surveys -->
            <li>
                <a href="<?php echo $this->createUrl("surveyAdministration/listsurveys"); ?>">
                    <?php eT("List surveys");?>
                </a>
            </li>

            <!-- Logout -->
            <li>
                <a href="<?php echo $this->createUrl("admin/authentication/sa/logout"); ?>">
                    <?php eT("Logout");?>
                </a>
            </li>
        </ul>
    </div>

    <div class="collapse navbar-collapse js-navbar-collapse pull-right ls--selector--configuration-menu">
        <ul class="nav navbar-nav navbar-right">

            <li>
                <a  href="<?php echo $this->createUrl("surveyAdministration/newSurvey"); ?>" >
                    <span class="icon-add" ></span>
                    <?php eT("Create survey");?>
                </a>
            </li>

            <!-- Surveys menus -->
            <li class="dropdown-split-left">
                <a style="" href="<?php echo $this->createUrl("surveyAdministration/listsurveys"); ?>">
                    <span class="fa fa-list" ></span>
                    <?php eT("Surveys");?>
                </a>
            </li>

            <!-- Help menu -->
            <?php $this->renderPartial( "/admin/super/_help_menu", []); ?>
            
            <!-- Configuration menu -->
            <?php $this->renderPartial( "/admin/super/_configuration_menu", $dataForConfigMenu ); ?>


            <!-- user menu -->
            <!-- active surveys -->
            <?php if ($activesurveyscount > 0): ?>
                <li>
                    <a href="<?php echo $this->createUrl('surveyAdministration/listsurveys/active/Y');?>">
                        <?php eT("Active surveys");?> <span class="badge badge-success"> <?php echo $activesurveyscount ?> </span>
                    </a>
                </li>
            <?php endif;?>

            <!-- Extra menus from plugins -->
            <?php // TODO: This views should be in same module as ExtraMenu and ExtraMenuItem classes (not plugin) ?>
            <?php foreach ($extraMenus as $menu): ?>
                <li class="dropdown">
                    <?php if ($menu->isDropDown()): ?>
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                          <?php echo $menu->getLabel(); ?>
                          &nbsp;
                          <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <?php foreach ($menu->getMenuItems() as $menuItem): ?>
                                <?php if ($menuItem->isDivider()): ?>
                                    <li class="divider"></li>
                                <?php elseif ($menuItem->isSmallText()): ?>
                                    <li class="dropdown-header"><?php echo $menuItem->getLabel();?></li>
                                <?php else: ?>
                                    <li>
                                        <a href="<?php echo $menuItem->getHref(); ?>">
                                            <!-- Spit out icon if present -->
                                            <?php if ($menuItem->getIconClass() != ''): ?>
                                              <span class="<?php echo $menuItem->getIconClass(); ?>">&nbsp;</span>
                                            <?php endif; ?>
                                            <?php echo $menuItem->getLabel(); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <a href="<?php echo $menu->getHref(); ?>"><?php echo $menu->getLabel(); ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>

            <!-- Admin notification system -->
            <?php echo $adminNotifications; ?>

            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" ><span class="icon-user" ></span> <?php echo Yii::app()->session['user'];?> <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                    <li>
                        <a href="<?php echo $this->createUrl("/admin/user/sa/personalsettings"); ?>"><?php eT("My account");?></a>
                    </li>

                    <li class="divider"></li>

                    <!-- Logout -->
                    <li>
                        <a href="<?php echo $this->createUrl("admin/authentication/sa/logout"); ?>">
                            <?php eT("Logout");?>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </div><!-- /.nav-collapse -->

    <!-- Maintenance mode -->
    <?php $sMaintenanceMode = getGlobalSetting('maintenancemode');
        if ($sMaintenanceMode == 'hard' || $sMaintenanceMode == 'soft'){ ?>
            <div class="collapse navbar-collapse js-navbar-collapse pull-right">
                <ul class="nav navbar-nav navbar-right">
                    <li>
                    <a class="text-warning" href="<?php echo $this->createUrl("admin/globalsettings"); ?>" title="<?php eT("Click here to change maintenance mode setting."); ?>" >
                            <span class="fa fa-warning" ></span>
                            <?php eT("Maintenance mode is active!");?>
                        </a>
                    </li>
                </ul>
            </div>
        <?php } ?>
</nav>
<script type="text/javascript">
    //show tooltips 
    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });
</script>
