<?php
/* Copyright (c) Anuko International Ltd. https://www.anuko.com
License: See license.txt */

require_once('initialize.php');
import('form.Form');
import('ttUser');
import('ttUserHelper');

if ($auth->isPasswordExternal()) {
  header('Location: login.php');
  exit();
}

$cl_login = $request->getParameter('login');

$form = new Form('resetPasswordForm');
$form->addInput(array('type'=>'text','maxlength'=>'80','name'=>'login','value'=>$cl_login));
$form->addInput(array('type'=>'submit','name'=>'btn_submit','value'=>$i18n->get('button.reset_password')));

if ($request->isPost()) {
  // Validate user input.
  if (!ttValidString($cl_login)) $err->add($i18n->get('error.field'), $i18n->get('label.login'));

  if ($err->no()) {
    if (!ttUserHelper::getUserByLogin($cl_login)) {
      // User with a specified login was not found.
      // In this case, if login looks like email, try finding user by email.
      if (ttValidEmail($cl_login)) {
        $login = ttUserHelper::getUserByEmail($cl_login);
        if ($login)
          $cl_login = $login;
        else
          $err->add($i18n->get('error.no_login'));
      } else
        $err->add($i18n->get('error.no_login'));
    }
  }

  if ($err->no()) {
    $user = new ttUser($cl_login); // Note: reusing $user from initialize.php here.

    // Protection against flooding user mailbox with too many password reset emails.
    if (ttUserHelper::recentRefExists($user->id)) $err->add($i18n->get('error.access_denied'));
  }

  if ($err->no()) {
    // Prepare and save a temporary reference for user.
    $cryptographically_strong = true;
    $random_bytes = openssl_random_pseudo_bytes(16, $cryptographically_strong);
    if ($random_bytes === false) die ("openssl_random_pseudo_bytes function call failed...");
    $temp_ref = bin2hex($random_bytes);
    ttUserHelper::saveTmpRef($temp_ref, $user->id);

    $user_i18n = null;
    if ($user->lang != $i18n->lang) {
      $user_i18n = new I18n();
      $user_i18n->load($user->lang);
    } else
      $user_i18n = &$i18n;

    // Where do we email to?
    $receiver = null;
    if ($user->email)
      $receiver = $user->email;
    else {
      if (ttValidEmail($cl_login))
        $receiver = $cl_login;
      else
        $err->add($i18n->get('error.no_email'));
    }

    if ($receiver) {
      $subject = $user_i18n->get('form.reset_password.email_subject');
      $path = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
      $pass_edit_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://". $_SERVER["HTTP_HOST"] . $path . "/password_change.php?ref=".$temp_ref;
      $body = sprintf($user_i18n->get('form.reset_password.email_body'), $_SERVER['REMOTE_ADDR'], $pass_edit_url);

      if (!send_mail($receiver, $user->name, $subject, $body)) {
        $err->add($i18n->get('error.mail_send'));
      }
      else {
        $msg->add($i18n->get('form.reset_password.message'));
      }
    }

  }
} // isPost

$smarty->assign('forms', array($form->getName()=>$form->toArray()));
$smarty->assign('onload', 'onload="document.resetPasswordForm.login.focus()"');
$smarty->assign('title', $i18n->get('title.reset_password'));
$smarty->assign('content_page_name', 'password_reset.tpl');
$smarty->display('index.tpl');
