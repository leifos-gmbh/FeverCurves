<#1>
<?php
if (!$ilDB->tableExists('skl_profile_fever'))
{
    $ilDB->createTable('skl_profile_fever', array(
        'profile_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'active' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        )
    ));
    $ilDB->addPrimaryKey('skl_profile_fever',array('profile_id'));
}
?>