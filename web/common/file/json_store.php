<?php
/*
#############################################################################
#############################################################################
#############################################################################
   Escritura segura de archivos JSON
   Safe JSON file writing

   Este archivo centraliza escrituras JSON con bloqueo y reemplazo atómico.
   This file centralizes JSON writes with locking and atomic replacement.
#############################################################################
#############################################################################
#############################################################################
*/

/*
#############################################################################
   Escribe contenido raw usando lock + archivo temporal + rename atómico
   Writes raw content using lock + temporary file + atomic rename
#############################################################################
*/
function json_store_write_raw(string $path, string $content) {
    $directory = dirname($path);

    if (!is_dir($directory) || !is_writable($directory)) {
        return false;
    }

    $lockPath = $path . '.lock';
    $lockHandle = fopen($lockPath, 'c');
    if ($lockHandle === false) {
        return false;
    }

    try {
        if (!flock($lockHandle, LOCK_EX)) {
            return false;
        }

        $tmpPath = tempnam($directory, basename($path) . '.tmp.');
        if ($tmpPath === false) {
            return false;
        }

        $bytes = file_put_contents($tmpPath, $content, LOCK_EX);
        if ($bytes === false) {
            @unlink($tmpPath);
            return false;
        }

        $readBack = file_get_contents($tmpPath);
        if ($readBack === false || json_decode($readBack, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            @unlink($tmpPath);
            return false;
        }

        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            return false;
        }

        return $bytes;
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

/*
#############################################################################
   Codifica datos como JSON y los escribe de forma segura
   Encodes data as JSON and writes it safely
#############################################################################
*/
function json_store_write(string $path, $data, int $flags = 0) {
    $json = json_encode($data, $flags);

    if ($json === false) {
        return false;
    }

    return json_store_write_raw($path, $json);
}
