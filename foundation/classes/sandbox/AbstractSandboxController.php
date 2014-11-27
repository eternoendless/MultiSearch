<?php

abstract class AbstractSandboxController extends AbstractController {

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  
  protected $sandboxName = 'sandbox';

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE ATTRIBUTES
  /////////////////////////////////////////////////////////////////////////////
  

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  public function defaultAction() {
    return $this->showSandbox();
  }
  
  public function run() {
    return $this->runSandbox();
  }

  /////////////////////////////////////////////////////////////////////////////
  // PRIVATE METHODS
  /////////////////////////////////////////////////////////////////////////////
  
  protected function runSandbox() {
    $showHtml = isset($_POST['html']);
    $code = ifExists($_POST, 'code', '');
    $output = '';
    
    if (!empty($code)) {
      ob_start();
      try {
        eval($_POST['code']);
      }
      catch(Exception $e) {
        if (isset($e->xdebug_message))
          echo "<table>".$e->xdebug_message."</table>";
        else
          var_dump($e);
      }
      $output = ob_get_contents();
      ob_end_clean();
      if (!$showHtml)
        $output = '<pre>' . htmlspecialchars($output) . '</pre>';
    }
    return new HtmlResponse($output);
  }

  private function showSandbox() {
    $tpl = file_get_contents(__DIR__.'/sandbox.tpl');
    $replace = array(
      '{%RUN_URL%}' => dirname($_SERVER['REQUEST_URI']) . '/' . $this->sandboxName  . '/run'
    );
    $parsed = str_replace(array_keys($replace), array_values($replace), $tpl);
    return new HtmlResponse($parsed);
  }

}