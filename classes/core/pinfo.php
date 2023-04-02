<?php

/**
 *
 * @author  2021 Marc Burchart <marc.burchart@tu-dortmund.de> , Kooperative Systeme, FernUniversität Hagen
 *
 */

namespace mod_hypercast\core;

defined('MOODLE_INTERNAL') || die();

class pinfo {

    public static function getData(){
        $d = new \stdClass();
        //$d->gen = basename(dirname(dirname(dirname(__DIR__)))).'_'.basename(dirname(dirname(__DIR__))).'\\'.basename(__DIR__);
        $d->basedir = strtolower(dirname(dirname(__DIR__)));
        $d->name = basename($d->basedir);
        $d->type = basename(dirname($d->basedir));
        $d->fullName = "{$d->type}_{$d->name}";
        $d->moodlePath = "/{$d->type}/{$d->name}";
        return $d;
    }

}
