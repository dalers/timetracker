<?php
/* Copyright (c) Anuko International Ltd. https://www.anuko.com
License: See license.txt */

require_once('initialize.php');
require_once(APP_PLUGINS_DIR . '/CustomFields.class.php');
import('form.Form');

// Access checks.
if (!ttAccessAllowed('manage_custom_fields')) {
  header('Location: access_denied.php');
  exit();
}
if (!$user->isPluginEnabled('cf')) {
  header('Location: feature_disabled.php');
  exit();
}
// End of access checks.

if ($request->isPost()) {
  $cl_field_name = is_null($request->getParameter('name')) ? '' : trim($request->getParameter('name'));
  $cl_entity_type = (int)$request->getParameter('entity');
  $cl_field_type = (int)$request->getParameter('type');
  $cl_required = (int)$request->getParameter('required');
}

$form = new Form('fieldForm');
$form->addInput(array('type'=>'text','maxlength'=>'100','name'=>'name','value'=>''));
$form->addInput(array('type'=>'combobox','name'=>'entity',
  'data'=>array(CustomFields::ENTITY_TIME=>$i18n->get('entity.time'),
                CustomFields::ENTITY_USER=>$i18n->get('entity.user'),
                CustomFields::ENTITY_PROJECT=>$i18n->get('entity.project'))
));
$form->addInput(array('type'=>'combobox','name'=>'type',
  'data'=>array(CustomFields::TYPE_TEXT=>$i18n->get('label.type_text'),
                CustomFields::TYPE_DROPDOWN=>$i18n->get('label.type_dropdown'))
));
$form->addInput(array('type'=>'checkbox','name'=>'required'));
$form->addInput(array('type'=>'submit','name'=>'btn_add','value'=>$i18n->get('button.add')));

if ($request->isPost()) {
  // Validate user input.
  if (!ttValidString($cl_field_name)) $err->add($i18n->get('error.field'), $i18n->get('label.thing_name'));
  if (CustomFields::getFieldByName($cl_field_name, $cl_entity_type) != null) $err->add($i18n->get('error.object_exists'));

  if ($err->no()) {
    $res = CustomFields::insertField($cl_field_name, $cl_entity_type, $cl_field_type, $cl_required);
    if ($res) {
      header('Location: cf_custom_fields.php');
      exit();
    } else
      $err->add($i18n->get('error.db'));
  }
} // isPost

$smarty->assign('forms', array($form->getName()=>$form->toArray()));
$smarty->assign('onload', 'onload="document.fieldForm.name.focus()"');
$smarty->assign('title', $i18n->get('title.cf_add_custom_field'));
$smarty->assign('content_page_name', 'cf_custom_field_add.tpl');
$smarty->display('index.tpl');
