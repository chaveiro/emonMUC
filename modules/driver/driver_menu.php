<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/driver/locale");
    bind_textdomain_codeset($domain, 'UTF-8');

    $menu_dropdown_config[] = array('name'=> dgettext($domain, "Drivers"), 'icon'=>'icon-edit', 'path'=>"driver/view" , 'session'=>"write", 'order' => 32 );
