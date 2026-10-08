<?php
function e($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

function redirect($path){
  header("Location: $path");
  exit();
}
