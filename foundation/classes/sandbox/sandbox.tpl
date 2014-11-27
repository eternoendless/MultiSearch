<!DOCTYPE html>
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <title>Sandbox</title>
  <style type="text/css">
  pre {margin:0}
  html {}
  body {font-family: arial; font-size:12px; margin: 0}
  form {display: float; padding: 10px}
  #codeText {width: 100%}
  #buttons {float: left; padding-left: 10px;}
  #run {width:300px; height:90px; font-size:40px}
  #textareaWrapper {float: left }
	textarea {font-size: 12px; font-family: monospace; height: 310px; width: 800px}
  #outputWrapper {display: block; position: absolute; bottom: 0; top: 350px; left: 0; right: 0; border: 1px solid #ccc; margin: 0 10px 10px; }
  #outputFrame {border: none; width: 100%; height: 100%; overflow: auto}
  </style>
  <script type="text/javascript">
  function $(id) {
    return document.getElementById(id);
  }
	function doSubmit(){
		$('theForm').submit();
	}
  </script>
  </head>
  <body>
    <form method="post" action="{%RUN_URL%}" id="theForm" target="outputFrame">
      Code:<br>
      <div id="textareaWrapper"><textarea name="code"></textarea></div>
      <div id="buttons"><button onclick="doSubmit();" id="run">Run</button> <br><br><input type="checkbox" name="html" id="html"><label for="html">Show output as HTML</label></div>
      <div style="clear:left"></div>
    </form>
    <div id="outputWrapper">
      <iframe id="outputFrame" src="about:blank"></iframe>
    </div>
  </body>
</html>
