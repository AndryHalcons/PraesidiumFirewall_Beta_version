<?php
/*
#############################################################################
   Compatibilidad temporal para la antigua ruta settings.php
   Temporary compatibility wrapper for the old settings.php route

   La página real del módulo vive en /system/logging/system_logging.php para
   respetar el patrón carpeta/modulo/endpoints del WebGUI.

   The real module page lives at /system/logging/system_logging.php to follow
   the WebGUI folder/module/endpoints pattern.
#############################################################################
*/
require_once __DIR__ . '/system/logging/system_logging.php';
