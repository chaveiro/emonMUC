<?php

    $domain = "messages";
    bindtextdomain($domain, "Modules/feed/locale");
    bind_textdomain_codeset($domain, 'UTF-8');
    
    $menu_dropdown_config[] = array('name'=> dgettext($domain, "Controllers"), 'icon'=>'icon-cog', 'path'=>"muc/view", 'session'=>"admin", 'order' => 33 );
