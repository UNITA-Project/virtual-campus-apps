<!-- Rendering massive action widget -->
<?php

$aActionsArray = array(
    'pk'          => 'selectedRole',
    'gridid'      => 'RoleControl--identity-gridPanel',
    'dropupId'    => 'RoleControl--actions',
    'dropUpText'  => gT('Selected role(s)...'),

    'aActions'    => array(
        // Delete
        array(
            'type'        => 'action',
            'action'      => 'delete',
            'url'         =>  App()->createUrl('userRole/batchDelete'),
            'iconClasses' => 'fa fa-trash text-danger',
            'text'        =>  gT('Delete'),
            'grid-reload' => 'yes',
            'actionType'    => 'modal',
            'modalType'     => 'cancel-delete',
            'keepopen'      => 'yes',
            'sModalTitle'   => gT('Delete roles'),
            'htmlModalBody' => gT('Are you sure you want to delete the selected role(s)?'),
        ),
        // Export
        array(
            'type' => 'action',
            'action' => 'batchExport',
            'url' => App()->createUrl('userRole/batchExport/sItems').'/',
            'iconClasses' => 'fa fa-download',
            'text' => gT('Bulk export roles'),
            'grid-reload' => 'no',
            'actionType'    => 'window-location-href',
        ),
        
    )
);

$this->widget('ext.admin.grid.MassiveActionsWidget.MassiveActionsWidget', $aActionsArray);
